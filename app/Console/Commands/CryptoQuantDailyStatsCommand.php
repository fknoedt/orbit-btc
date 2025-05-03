<?php

namespace App\Console\Commands;

use App\Clients\CurlCryptoQuantClient;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Exception\RuntimeException;

class CryptoQuantDailyStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:crypto-quant-daily-stats {metrics?} {--force} {--ignore-errors} {--from-file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads from CryptoQuant\'s API and populate the given metrics';

    protected $help = "Add arguments list and instructions (csm) here";

    /**
     * Execute the console command.
     */
    public function handle(DailyStatsService $service)
    {
        $metrics = $this->argument('metrics');

        $allEndpoints = CurlCryptoQuantClient::METRICS_TO_ENDPOINT;
        $client = new CurlCryptoQuantClient();

        $this->output->writeln('<info>Upserting daily_prices with CryptoQuant data</info>');

        if (empty($metrics)) {
            // default to all endpoints
            $endpoints = $allEndpoints;
        } else {
            $endpoints = [];
            foreach (explode(',', $metrics) as $metric) {
                if (! isset($allEndpoints[$metric])) {
                    throw new RuntimeException(
                        "invalid metric `{$metric}` -- valid: " .
                        implode(",", array_keys($allEndpoints))
                    );
                }
                $endpoints[$metric] = $allEndpoints[$metric];
            }
        }

        $newData = [];
        try {
            if ($this->option('from-file')) {
                if (isset($endpoints['average_fee'])) {
                    $this->output->info('Importing CQ daily stats data from cq-btc-average-fee-live.json into daily_prices...');
                    $averageFeeData = json_decode(
                        file_get_contents(
                            database_path() . DIRECTORY_SEPARATOR . 'raw-data' . DIRECTORY_SEPARATOR .
                            'cq-btc-average-fee-live.json' // in .gitignore
                        ),
                        true
                    );
                    $this->output->info(count($averageFeeData['result']['data']) . ' record(s) found. Parsing...');
                    foreach ($averageFeeData['result']['data'] as $day) {
                        $dateTime = Carbon::createFromTimestamp($day[0] / 1000);
                        if (empty($day[1]) || $dateTime->format('Y-m') < '2025-04') {
                            continue;
                        }
                        $newData[$dateTime->format('Y-m-d')]['average_fee'] = $day[1];
                    }

                    $this->output->writeln('<info>Running upsertStats for ' . count($newData) . ' days</info>');
                    $recordsSaved = $service->fillStats($newData, $this->option('force'));
                    $this->output->writeln('<info>' . $recordsSaved . ' record(s) saved</info>');
                }

                if (isset($endpoints['exchanges_reserve'])) {
                    $this->output->info('Importing CQ daily stats data from cq-btc-exchanges-balance-live.json into daily_prices...');
                    $exchangesReserveData = json_decode(
                        file_get_contents(
                            database_path() . DIRECTORY_SEPARATOR . 'raw-data' . DIRECTORY_SEPARATOR .
                            'cq-btc-exchanges-balance-live.json' // in .gitignore
                        ),
                        true
                    );
                    $this->output->info(count($exchangesReserveData['result']['data']) . ' record(s) found. Parsing...');
                    foreach ($exchangesReserveData['result']['data'] as $day) {
                        $dateTime = Carbon::createFromTimestamp($day[0] / 1000);
                        if (empty($day[1]) || $dateTime->format('Y-m') < '2012-01') {
                            continue;
                        }
                        if (! isset($newData[$dateTime->format('Y-m-d')])) {
                            $newData[$dateTime->format('Y-m-d')] = [];
                        }
                        $newData[$dateTime->format('Y-m-d')]['exchanges_reserve'] = $day[1];
                    }

                    $this->output->writeln('<info>Running upsertStats for ' . count($newData) . ' days</info>');
                    $recordsSaved = $service->fillStats($newData, $this->option('force'));
                    $this->output->writeln('<info>' . $recordsSaved . ' record(s) saved</info>');
                }
                $this->output->writeln('<info>Done ✅</info>');
            } else {
                foreach ($endpoints as $metric => $endpoint) {
                    $response = $client->curlRequest($endpoint);
                    $this->output->writeln("<info>{$metric} data fetched</info>");
                    foreach ($response['result']['data'] as $day) {
                        $dateTime = Carbon::createFromTimestamp($day[0] / 1000);
                        $newData[$dateTime->format('Y-m-d')][$metric] = $day[1];
                    }
                }
                $this->output->writeln('<info>Running upsertStats for ' . count($newData) . ' days</info>');
                $recordsSaved = $service->fillStats($newData, $this->option('force'));
                $this->output->writeln('<info>' . $recordsSaved . ' record(s) saved</info>');
                $this->output->writeln('<info>Done ✅</info>');
            }
        } catch (\Throwable $e) {
            if (! $this->option('ignore-errors')) {
                throw $e;
            }
            report($e);
        }
    }
}
