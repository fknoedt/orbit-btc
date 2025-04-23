<?php

namespace App\Console\Commands;

use App\Adapters\CryptoCompareApiAdapter;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CryptoCompareDailyStatsCommand extends Command
{
    public const int FETCH_DAYS_AGO = 60;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:cryptocompare-daily-stats {--force} {--since=} {--ignore-errors}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads from CryptoCompare\'s API and populate the given metrics';

    protected $help = "Add arguments list and instructions (csm) here";

    /**
     * Execute the console command.
     */
    public function handle(CryptoCompareApiAdapter $adapter, DailyStatsService $service)
    {
        $since = $this->option('since');

        if ($since) {
            if (! $sinceDate = \DateTime::createFromFormat('Y-m-d', $since)) {
                throw new \RuntimeException('Invalid --since');
            }
            $daysAgo = Carbon::now()->diffInDays($sinceDate);
            if ($daysAgo > CryptoCompareApiAdapter::MAX_LIMIT) {
                throw new \RuntimeException('Maximum days reached: ' . CryptoCompareApiAdapter::MAX_LIMIT);
            }
        } else {
            $daysAgo = self::FETCH_DAYS_AGO;
        }

        $this->output->info(
            "Updating daily_prices with CryptoCompare data starting {$daysAgo} day(s) ago"
        );
        $newData = [];
        try {
            $response = $adapter->getOnChainDailyHistory($daysAgo);
            $this->output->info(count($response) . ' days of on-chain data fetched');

            foreach ($response as $day) {
                $dateTime = Carbon::createFromTimestamp($day['time'])->format('Y-m-d');
                $newData[$dateTime] = [
                    'date' => $dateTime,
                    'transaction_count' => $day['transaction_count'],
                    'large_transaction_count' => $day['large_transaction_count'],
                    'average_transaction_value' => $day['average_transaction_value'],
                    'new_addresses' => $day['new_addresses'],
                    'block_size' => $day['block_size'],
                ];
            }

            $response = $adapter->getExchangeVolume($daysAgo);
            $this->output->info(count($response) . ' days of exchanges volume fetched');

            foreach ($response as $day) {
                $dateTime = Carbon::createFromTimestamp($day['time'])->format('Y-m-d');
                if (! isset($newData[$dateTime])) {
                    $newData[$dateTime] = ['date' => $dateTime];
                }
                $newData[$dateTime]['exchanges_volume'] = $day['volume'];
            }

            ksort($newData);

            $this->output->info('Running fillStats for ' . count($newData) . ' records');
            $recordsSaved = $service->fillStats($newData, $this->option('force'));
            $this->output->info("{$recordsSaved} record(s) saved");
            $this->output->info('Done ✅');
        } catch (\Throwable $e) {
            if (! $this->option('ignore-errors')) {
                throw $e;
            }
            $this->output->error("{$e->getMessage()} @ {$e->getFile()}:{$e->getLine()}");
            \Log::error("Command (" . $this->signature . '): ' . $e->getMessage());
        }
    }
}
