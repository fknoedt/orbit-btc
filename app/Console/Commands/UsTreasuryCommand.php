<?php

namespace App\Console\Commands;

use App\Clients\UsTreasuryClient;
use App\Exceptions\AdapterException;
use App\Exceptions\DailyPriceStatsException;
use App\Exceptions\ExternalApiException;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class UsTreasuryCommand extends Command
{
    public const int SINCE_MONTHS_AGO = 24;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:update-us-treasury-stats {--from=} {--to=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate `daily_prices` with US Treasury T-Bill daily net issuance and normalized QE (from DTS data)';

    /**
     * Execute the console command.
     * @param UsTreasuryClient $client
     * @param DailyStatsService $dailyStatsService
     * @throws DailyPriceStatsException
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function handle(UsTreasuryClient $client, DailyStatsService $dailyStatsService)
    {
        $from = $this->option('from') ?? Carbon::now()->subMonths(self::SINCE_MONTHS_AGO)->format('Y-m-d');
        $to = $this->option('to') ?? Carbon::yesterday()->format('Y-m-d'); // Adjust for 1-day lag
        $force = true;

        $this->output->info("Fetching US Treasury T-Bill daily net issuance data from {$from} to {$to}");
        $result = $client->getTBillDailyTransactions($from, $to);

        if (! $data = $result['data'] ?? null) {
            throw new \RuntimeException("Malformed/empty response: " . json_encode($result));
        }

        // Group by date and compute net issuance (issues - redemptions) for T-Bills
        $dailyNetIssuance = [];
        foreach ($data as $record) {
            $date = Carbon::parse($record['record_date'])->format('Y-m-d');
            $type = $record['transaction_type'];
            $amount = (float) $record['transaction_today_amt'];

            if (!isset($dailyNetIssuance[$date])) {
                $dailyNetIssuance[$date] = 0;
            }

            if ($type === 'Issues') {
                $dailyNetIssuance[$date] += $amount;
            } elseif ($type === 'Redemptions') {
                $dailyNetIssuance[$date] -= $amount;
            }
        }

        // Fill in zeros for missing dates (non-business days)
        $currentDate = Carbon::parse($from);
        $endDate = Carbon::parse($to);
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            if (!isset($dailyNetIssuance[$dateStr])) {
                $dailyNetIssuance[$dateStr] = 0;
            }
            $currentDate->addDay();
        }

        ksort($dailyNetIssuance);

        // Compute normalized 12-mo QE (z-score of rolling 12-mo change)
        $normalizedQE = $this->computeNormalizedQE($dailyNetIssuance);

        // Store both in daily_prices
        $dailyData = [];
        foreach ($dailyNetIssuance as $date => $netIssuance) {
            $dailyData[$date]['us_tbill_net_issuance'] = $netIssuance;
            $dailyData[$date]['us_tbill_normalized_qe'] = $normalizedQE[$date] ?? null;  // Null if not enough history
        }

        $recordsUpdated = $dailyStatsService->fillStats($dailyData, $force);

        $this->info("{$recordsUpdated} daily_prices updated with T-Bill net issuance and normalized QE. All done ✅");
    }

    /**
     * Compute normalized 12-mo rolling QE from daily net issuance.
     * @param array $dailyNetIssuance Sorted array of date => net_issuance
     * @return array date => normalized_value (forward-filled monthly to daily)
     */
    private function computeNormalizedQE(array $dailyNetIssuance): array
    {
        // Step 1: Compute daily cumulative outstanding (assume start at 0; for relative changes it's fine)
        $outstanding = [];
        $cumsum = 0;
        foreach ($dailyNetIssuance as $date => $net) {
            $cumsum += $net;
            $outstanding[$date] = $cumsum;
        }

        // Step 2: Get monthly end outstanding (last day of each month)
        $monthlyOutstanding = [];
        $prevMonth = null;
        foreach ($outstanding as $date => $value) {
            $carbonDate = Carbon::parse($date);
            $monthKey = $carbonDate->format('Y-m');
            if ($monthKey !== $prevMonth) {
                $prevMonth = $monthKey;
            }
            $monthlyOutstanding[$monthKey] = $value;  // Overwrite with last day's value
        }

        // Step 3: Compute monthly net change (diff)
        $monthlyNet = [];
        $prevValue = null;
        foreach ($monthlyOutstanding as $monthKey => $value) {
            if ($prevValue !== null) {
                $monthlyNet[$monthKey] = $value - $prevValue;
            }
            $prevValue = $value;
        }

        // Step 4: Compute rolling 12-month sum
        $rolling12Mo = [];
        $netKeys = array_keys($monthlyNet);
        for ($i = 11; $i < count($netKeys); $i++) {
            $sum = 0;
            for ($j = $i - 11; $j <= $i; $j++) {
                $sum += $monthlyNet[$netKeys[$j]];
            }
            $rolling12Mo[$netKeys[$i]] = $sum;
        }

        // Step 5: Normalize as z-score
        $values = array_values($rolling12Mo);
        if (count($values) > 0) {
            $mean = array_sum($values) / count($values);
            $variance = 0;
            foreach ($values as $val) {
                $variance += pow($val - $mean, 2);
            }
            $std = sqrt($variance / count($values));
            $normalizedMonthly = [];
            foreach ($rolling12Mo as $monthKey => $val) {
                $normalizedMonthly[$monthKey] = ($std != 0) ? ($val - $mean) / $std : 0;
            }
        } else {
            $normalizedMonthly = [];
        }

        // Step 6: Forward-fill monthly normalized to daily dates
        $normalizedDaily = [];
        $currentNormalized = null;
        foreach ($dailyNetIssuance as $date => $net) {
            $monthKey = Carbon::parse($date)->format('Y-m');
            if (isset($normalizedMonthly[$monthKey])) {
                $currentNormalized = $normalizedMonthly[$monthKey];
            }
            $normalizedDaily[$date] = $currentNormalized;
        }

        return $normalizedDaily;
    }
}
