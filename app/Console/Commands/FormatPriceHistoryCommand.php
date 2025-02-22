<?php

namespace App\Console\Commands;

use App\Services\PriceHistoryService;
use Illuminate\Console\Command;

class FormatPriceHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:format-price-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild/truncate historical price data';

    /**
     * Execute the console command.
     */
    public function handle(PriceHistoryService $service)
    {
        $results = $service->loadPersistedPrices();

        $message = $results ? count($results) . " Price(s) created ✅" : 'No prices missing';

        $this->output->writeln($message);
    }
}
