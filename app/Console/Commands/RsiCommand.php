<?php

namespace App\Console\Commands;

use App\Services\PriceHistoryService;
use Illuminate\Console\Command;

class RsiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:update-rsi {--since=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and update daily_prices.rsi';

    /**
     * Execute the console command.
     */
    public function handle(PriceHistoryService $service)
    {
        $since = $this->option('since') ?? null;

        $this->output->writeln(
            'Updating RSI since ' . ($since ?? 'last available day')
        );

        $pricesUpdated = $service->updateRsi($since);

        $this->output->writeln(
            $pricesUpdated ? "{$pricesUpdated} daily_prices updated ✅" : 'No daily_prices updated'
        );
    }
}
