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
    protected $signature = 'btc:mempool-daily-stats {--initial-data}';

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
    public function handle(DailyStatsService $service)
    {
        $client = new MempoolClient();

        if ($this->option('initial-data')) {
            $this->info('Loading initial static mempool hashrate & difficulty data');
            $totalRowsUpdated = $client->loadInitialDailyPricesData();
            $since = null;
        } else {
            $this->info(
                'Fetching hashrate & difficulty from Mempool\'s API (last ' . MempoolClient::HASHRATE_TIME_PERIOD . ')'
            );
            $parsedData = $client->getParsedHistoricalHashrate();
            $totalRowsUpdated = $service->fillStats($parsedData, true);
            $since = $client->getHashrateTimePeriodDate();
        }

        $this->info("{$totalRowsUpdated} daily_prices updated");

        $this->info('Filling forward...');

        $filledForward = $service->fillForward('difficulty', $since);

        $this->info("{$filledForward} daily_prices.difficulty filled forward");
    }
}
