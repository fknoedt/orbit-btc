<?php

namespace App\Console\Commands;

use App\Clients\MempoolClient;
use App\Exceptions\AdapterException;
use App\Exceptions\DailyPriceStatsException;
use App\Exceptions\ExternalApiException;
use App\Services\DailyStatsService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class MempoolDailyStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:mempool-daily-stats {--initial-data} {--time-period=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads from Mempool\'s REST API and populate the given metrics';

    protected $help = "Add arguments list and instructions (csm) here";

    /**
     * Execute the console command.
     * @throws DailyPriceStatsException
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function handle(DailyStatsService $service, MempoolClient $client)
    {
        if ($this->option('initial-data')) {
            $this->info('Loading initial static mempool hashrate & difficulty data');
            $totalRowsUpdated = $client->loadInitialDailyPricesData();
            $since = 'initial data';
        } else {
            $timePeriod = $this->option('time-period') ?? MempoolClient::HASHRATE_TIME_PERIOD;
            $this->info(
                "Fetching hashrate & difficulty from Mempool's API (time-period: {$timePeriod})"
            );

            $rawData = $client->getHistoricalHashrate($timePeriod);

            $hashrateData = $client->parseHashrate($rawData['hashrates'] ?? []);
            $difficultyData = $client->parseDifficulty($rawData['difficulty'] ?? []);

            $this->info('Filling hashrate stats...');
            $hashrateRows = $service->fillStats($hashrateData, true);

            $this->info('Filling difficulty stats...');
            $difficultyRows = $service->fillStats($difficultyData, true);

            $totalRowsUpdated = $hashrateRows + $difficultyRows;
            $since = $client->getHashrateTimePeriodDate($timePeriod);
        }

        $this->info("{$totalRowsUpdated} daily_prices updated since {$since}");

        $this->info('Filling difficulty forward...');

        $filledForward = $service->fillForward('difficulty', $since);

        $this->info("{$filledForward} daily_prices.difficulty filled forward");
    }
}
