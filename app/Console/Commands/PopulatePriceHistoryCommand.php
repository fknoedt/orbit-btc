<?php

namespace App\Console\Commands;

use App\Services\PriceHistoryService;
use Illuminate\Console\Command;

class PopulatePriceHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:populate-price-history {--since=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and save BTC price since genesis or --since (use `format-price-history` first to rebuild)';

    /**
     * Execute the console command.
     */
    public function handle(PriceHistoryService $service)
    {
        $since = $this->option('since') ?? null;

        if ($since) {
            $results = $service->fillMissingPricesSince($this->output, $since);
        } else {
            $results = $service->fillMissingPricesFromInitialDay($this->output);
        }

        $message = $results ? "{$results} Price(s) created ✅" : 'No prices missing';

        $this->output->writeln($message);
    }
}
