<?php

namespace App\Console\Commands;

use App\Clients\CryptoQuantClient;
use App\Services\DailyStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\ConsoleOutput;

class CryptoQuantDailyStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:crypto-quant-daily-stats {metrics?} {--force} {--ignore-errors}';

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
        $output = new ConsoleOutput();
        $metrics = $this->argument('metrics');

        $allEndpoints = CryptoQuantClient::METRICS_TO_ENDPOINT;
        $client = new CryptoQuantClient();

        $output->writeln('<info>Upserting daily_prices with CryptoQuant data</info>');

        if (empty($metrics)) {
            // default to all endpoints
            $endpoints = $allEndpoints;
        } else {
            $endpoints = [];
            foreach (explode(',', $metrics) as $metric) {
                if (! isset($allEndpoints[$metric])) {
                    throw new RuntimeException(
                        "invalid metric `{$metric}` -- valids: " .
                        implode(",", array_keys($allEndpoints))
                    );
                }
                $endpoints[$metric] = $allEndpoints[$metric];
            }
        }

        $newData = [];
        try {
            foreach ($endpoints as $metric => $endpoint) {
                $response = $client->curlRequest($endpoint);
                $output->writeln("<info>{$metric} data fetched</info>");
                foreach ($response['result']['data'] as $day) {
                    $dateTime = Carbon::createFromTimestamp($day[0] / 1000);
                    $newData[$dateTime->format('Y-m-d')][$metric] = $day[1];
                }
            }

            $output->writeln('<info>Running upsertStats for ' . count($newData) . ' days</info>');
            $recordsSaved = $service->fillStats($newData, $this->option('force'));
            $output->writeln('<info>' . $recordsSaved . ' record(s) saved</info>');
            $output->writeln('<info>Done ✅</info>');
        } catch (\Throwable $e) {
            if (! $this->option('ignore-errors')) {
                throw $e;
            }
            \Log::error("Command (" . $this->signature . '): ' . $e->getMessage());
        }
    }
}
