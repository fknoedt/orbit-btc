<?php

namespace App\Console\Commands;

use App\Clients\CoinMetricsClient;
use App\Exceptions\AdapterException;
use App\Exceptions\DailyPriceStatsException;
use App\Exceptions\ExternalApiException;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class CoinmetricsDailyStatsCommand extends Command
{
    public const int SINCE_DAYS_AGO = 15;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:coinmetrics-daily-stats {metrics?} {--from=} {--to=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate `daily_prices` stats with BGeometrics data';

    /**
     * Execute the console command.
     * @param CoinMetricsClient $client
     * @param DailyStatsService $dailyStatsService
     * @throws DailyPriceStatsException
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function handle(CoinMetricsClient $client, DailyStatsService $dailyStatsService)
    {
        $from = $this->option('from') ?? Carbon::now()->subDays(self::SINCE_DAYS_AGO)->format('Y-m-d');
        $to = $this->option('to') ?? Carbon::now()->format('Y-m-d');
        $metricsInput = $this->argument('metrics');
        $force = $this->option('force');

        $endpoints = array_keys(CoinMetricsClient::METRIC_TO_COLUMN_NAME);

        if ($metricsInput) {
            $endpoints = array_intersect(explode(',', $metricsInput), $endpoints);
        }

        $this->output->info("Fetching data from {$from} to {$to}: " . implode(', ', $endpoints));
        $result = $client->getMetrics($endpoints, $from, $to);

        if (! $data = $result['data'] ?? null) {
            throw new \RuntimeException("Malformed/empty response: " . json_encode($result));
        }

        $dailyData = [];
        foreach ($data as $day) {
            $date = Carbon::parse($day['time'])->format('Y-m-d');

            foreach ($endpoints as $metric) {
                $columnName = CoinMetricsClient::METRIC_TO_COLUMN_NAME[$metric];
                $dailyData[$date][$columnName] = $day[$metric];
            }
        }

        $recordsUpdated = $dailyStatsService->fillStats($dailyData, $force);

        $this->info("{$recordsUpdated} daily_prices updated. All done ✅");
    }
}
