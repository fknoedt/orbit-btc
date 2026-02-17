<?php

namespace App\Console\Commands;

use App\Clients\BgeometricsClient;
use App\Clients\BitcoinIsDataClient;
use App\Exceptions\AdapterException;
use App\Exceptions\DailyPriceStatsException;
use App\Exceptions\ExternalApiException;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Laravel\Octane\Exceptions\DdException;

class BitcoinIsDataDailyStatsCommand extends Command
{
    public const int SINCE_DAYS_AGO = 15;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:bitcoinisdata-daily-stats {--since=} {--from-file} {metrics?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate `daily_prices` stats with BitcoinIsData data (currently from file only)';

    /**
     * Execute the console command.
     * @param DailyStatsService $dailyStatsService
     * @param BitcoinIsDataClient $client
     * @throws DailyPriceStatsException
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     * @throws DdException
     */
    public function handle(DailyStatsService $dailyStatsService, BitcoinIsDataClient $client)
    {
        if (! $since = $this->option('since')) {
            $since = Carbon::now()->subDays(self::SINCE_DAYS_AGO)->format('Y-m-d');
        }

        // currently only m2 works
        if ($this->option('from-file')) {
            $metrics = 'm2';

            $this->output->info("Updating daily_stats.m2 with BID data since {$since}");

            // in .gitignore
            $filepath = database_path() . DIRECTORY_SEPARATOR . 'local-data' . DIRECTORY_SEPARATOR . "bid-{$metrics}.csv";

            $numRows = 0;
            $data = [];

            if (($handle = fopen($filepath, "r")) !== FALSE) {
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $numRows++;
                    // header
                    if ($numRows === 1) {
                        continue;
                    }
                    $data[$row[1]] = [$metrics => $row[2]];
                }
                fclose($handle);
            } else {
                throw new \RuntimeException('Unable to open ' . $filepath);
            }

            // header doesn't count
            $numRows--;

            ksort($data);

            $this->info("{$numRows} loaded from csv file. Updating...");
        } else {
            // see @todo on BitcoinIsDataClient
            throw new \RuntimeException('Currently only --from-file is supported');
        }

        $recordsUpdated = $dailyStatsService->fillStats($data, true);

        $this->info("{$recordsUpdated} daily_prices.{$metrics} updated");

        $this->output->success("All done ✅");
    }
}
