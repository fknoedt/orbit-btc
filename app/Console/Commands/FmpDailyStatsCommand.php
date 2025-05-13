<?php

namespace App\Console\Commands;

use App\Clients\FmpClient;
use App\Exceptions\DailyPriceStatsException;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FmpDailyStatsCommand extends Command
{
    public const int SINCE_DAYS_AGO = 15;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:fmp-daily-stats {metrics?} {--from=} {--to=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate `daily_prices` stats with BGeometrics data';

    /**
     * Execute the console command.
     * @throws DailyPriceStatsException
     */
    public function handle(FmpClient $client, DailyStatsService $dailyStatsService)
    {
        $from = $this->option('from') ?? Carbon::now()->subDays(self::SINCE_DAYS_AGO)->format('Y-m-d');
        $to = $this->option('to') ?? Carbon::now()->format('Y-m-d');
        $metrics = $this->argument('metrics');

        $endpoints = FmpClient::METRICS;

        if (empty($metrics)) {
            $metrics = $endpoints;
        } else {
            $tempMetrics = [];
            foreach (explode(',', $metrics) as $metric) {
                if (! isset($endpoints[$metric])) {
                    throw new \InvalidArgumentException(
                        "invalid metric `{$metric}` -- valid: " .
                        implode(",", $endpoints)
                    );
                }
                $tempMetrics[$metric] = $endpoints[$metric];
            }
            $metrics = $tempMetrics;
        }

        $this->output->info("Running daily stats since {$from} to " . implode(', ', $metrics));

        $totalRecordsUpdated = 0;
        foreach ($endpoints as $metric => $method) {
            $this->output->writeln("{$metric} - {$method}");

            $result = $client->$method($from, $to);

            $data = $client->parseHistoricalResult($result, $metric);

            $recordsUpdated = $dailyStatsService->fillStats($data);
            $totalRecordsUpdated += $recordsUpdated;

            $this->info("{$recordsUpdated} daily_prices.{$metric} updated");

            $this->info('Filling forward...');

            $filledForward = $dailyStatsService->fillForward($metric, $from);

            $this->info("{$filledForward} daily_prices.{$metric} filled forward");
        }

        $this->output->success("{$totalRecordsUpdated} records updated. All done ✅");
    }
}
