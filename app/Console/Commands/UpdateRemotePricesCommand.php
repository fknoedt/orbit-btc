<?php

namespace App\Console\Commands;

use App\Clients\OrbitBtcClient;
use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class UpdateRemotePricesCommand extends Command
{
    protected const int DEFAULT_NUMBER_OF_DAYS_AGO = 30;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:update-remote-prices {--since=} {--to=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update daily_prices in a remote Orbit instance based on local data. Use --since=YYYY-MM-DD to bypass default number of days';

    /**
     * Execute the console command.
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function handle(OrbitBtcClient $client): void
    {
        $since = $this->option('since') ?? null;
        $to = $this->option('to') ?? null;

        if (! $since) {
            $since = Carbon::now()->subDays(self::DEFAULT_NUMBER_OF_DAYS_AGO)->format('Y-m-d');
        } else {
            $sinceDate = Carbon::parse($since);
            if (! $sinceDate) {
                throw new \InvalidArgumentException('Invalid --since value. use YYYY-MM-DD.');
            }
            $since = $sinceDate->format('Y-m-d');
        }

        if (! $to) {
            $to = Carbon::now()->format('Y-m-d');
        } else {
            $toDate = Carbon::parse($to);
            if (! $toDate) {
                throw new \InvalidArgumentException('Invalid --to value. use YYYY-MM-DD.');
            }
            $to = $toDate->format('Y-m-d');
        }

        $this->output->writeln("Updating remote daily_prices since {$since} to {$to}");

        $pricesUpdated = $client->updateRemotePrices($since, $to);

        $message = $pricesUpdated ? "{$pricesUpdated} Price(s) updated ✅" : 'No prices updated';

        $this->output->writeln($message);
    }
}
