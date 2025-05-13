<?php

namespace App\Console\Commands;

use App\Clients\BgeometricsClient;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BgeometricsDailyStatsCommand extends Command
{
    public const int SINCE_DAYS_AGO = 15;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:bgeometrics-daily-stats {--from-start} {--from-file} {metrics?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate `daily_prices` stats with BGeometrics data';

    /**
     * Execute the console command.
     */
    public function handle(BgeometricsClient $client, DailyStatsService $dailyStatsService)
    {
        $since = $this->option('from-start') ?
            '2009-01-01' :
            Carbon::now()->subDays(self::SINCE_DAYS_AGO)->format('Y-m-d');

        $metrics = $this->argument('metrics');

        $fromFile = $this->option('from-file');

        // default to all endpoints
        $allEndpoints = BgeometricsClient::ENDPOINTS;
        $endpoints = [];

        if (empty($metrics)) {
            $endpoints = $allEndpoints;
        } else {
            foreach (explode(',', $metrics) as $metric) {
                if (! isset($allEndpoints[$metric])) {
                    throw new \InvalidArgumentException(
                        "invalid metric `{$metric}` -- valid: " .
                        implode(",", array_keys($allEndpoints))
                    );
                }
                $endpoints[$metric] = $allEndpoints[$metric];
            }
        }

        $this->output->info("Running daily stats since {$since} to " . implode(', ', array_keys($endpoints)));

        $totalRecordsUpdated = 0;
        foreach ($endpoints as $endpoint => $resultField) {
            $this->output->writeln(" - {$endpoint}");

            $result = $client->getEndpoint($endpoint, ['startday' => $since], $fromFile);

            $recordsUpdated = $dailyStatsService->fillStats($result);
            $totalRecordsUpdated += $recordsUpdated;

            $columnName = $client->getEndpointToColumnName($endpoint);

            $this->info("{$recordsUpdated} daily_prices.{$columnName} updated");

            $this->info('Filling forward...');

            $filledForward = $dailyStatsService->fillForward($columnName, $since);

            $this->info("{$filledForward} daily_prices.{$columnName} filled forward");
        }

        $this->output->success("{$totalRecordsUpdated} records updated. All done ✅");
    }
}
