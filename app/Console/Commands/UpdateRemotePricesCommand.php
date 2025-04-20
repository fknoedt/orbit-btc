<?php

namespace App\Console\Commands;

use App\Clients\OrbitBtcClient;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateRemotePricesCommand extends Command
{
    protected const int DEFAULT_NUMBER_OF_DAYS_AGO = 3;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:update-remote-prices {--since=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update daily_prices in a remote Orbit instance based on local data. Use --since=YYYY-MM-DD to bypass default number of days';

    /**
     * Execute the console command.
     */
    public function handle(OrbitBtcClient $client): void
    {
        $since = $this->option('since') ?? null;

        if (! $since) {
            $since = Carbon::now()->subDays(self::DEFAULT_NUMBER_OF_DAYS_AGO)->format('Y-m-d');
        } else {
            $sinceDate = Carbon::parse($since);
            if (! $sinceDate) {
                throw new \InvalidArgumentException('Invalid --since value. use YYYY-MM-DD.');
            }
            $since = $sinceDate->format('Y-m-d');
        }

        $this->output->writeln("Updating remote daily_prices since {$since}");

        $pricesUpdated = $client->updateRemotePrices($since);

        $message = $pricesUpdated ? "{$pricesUpdated} Price(s) updated ✅" : 'No prices updated';

        $this->output->writeln($message);
    }
}
