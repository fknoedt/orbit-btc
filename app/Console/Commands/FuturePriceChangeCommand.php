<?php

namespace App\Console\Commands;

use App\Services\PriceHistoryService;
use Illuminate\Console\Command;

class FuturePriceChangeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:update-future-price-change {--since=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and update daily_prices.price_change_[1,3,5,10,14,30]d';

    /**
     * Execute the console command.
     */
    public function handle(PriceHistoryService $service)
    {
        ini_set('memory_limit', '4096M');
        $since = $this->option('since') ?? null;
        $dryRun = $this->option('dry-run');

        $this->output->info('Updating Future Price Changes since ' . ($since ?? 'last available day'));

        $pricesUpdated = $service->updateFuturePriceChange($since, $this->output, $dryRun);

        $this->output->success(
            $pricesUpdated ? "{$pricesUpdated} daily_prices updated ✅" : 'No daily_prices updated'
        );
    }
}
