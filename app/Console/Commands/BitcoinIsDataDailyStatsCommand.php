<?php

namespace App\Console\Commands;

use App\Clients\BgeometricsClient;
use App\Exceptions\DailyPriceStatsException;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BitcoinIsDataDailyStatsCommand extends Command
{
    // TODO: move to BitcoinIsDataClient when it is created
    protected const array ALLOWED_METRICS = [
        'm2'
    ];

    public const int SINCE_DAYS_AGO = 15;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:bitcoinisdata-daily-stats {--from-start} {--from-file} {metrics?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate `daily_prices` stats with BitcoinIsData data (currently from file only)';

    /**
     * Execute the console command.
     * @throws DailyPriceStatsException
     */
    public function handle(DailyStatsService $dailyStatsService)
    {
        $since = $this->option('from-start') ?
            '2009-01-01' :
            Carbon::now()->subDays(self::SINCE_DAYS_AGO)->format('Y-m-d');

        // currently only working from-file and for m2
        $fromFile = $this->option('from-file');
        $metric = 'm2';


        if (! $fromFile) {
            throw new \InvalidArgumentException('from-file option is required');
        }

        $this->output->info("Updating daily_stats.m2 with BID data since {$since}");

        // in .gitignore
        $filepath = database_path() . DIRECTORY_SEPARATOR . 'local-data' . DIRECTORY_SEPARATOR . "bid-{$metric}.csv";

        $numRows = 0;
        $data = [];

        if (($handle = fopen($filepath, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $numRows++;
                // header
                if ($numRows === 1) {
                    continue;
                }
                $data[$row[1]] = [$metric => $row[2]];
            }
            fclose($handle);
        } else {
            throw new \RuntimeException('Unable to open ' . $filepath);
        }

        // header doesn't count
        $numRows--;

        ksort($data);

        $this->info("{$numRows} loaded from csv file. Updating...");

        $recordsUpdated = $dailyStatsService->fillStats($data);

        $this->info("{$recordsUpdated} daily_prices.{$metric} updated");

        $this->info('Filling forward...');

        $filledForward = $dailyStatsService->fillForward($metric, $since);

        $this->info("{$filledForward} daily_prices.{$metric} filled forward");

        $this->output->success("All done ✅");
    }
}
