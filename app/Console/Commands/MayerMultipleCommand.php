<?php

namespace App\Console\Commands;

use App\Models\DailyPrice;
use App\Services\PriceHistoryService;
use Illuminate\Console\Command;

class MayerMultipleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:update-mayer-multiple {--since=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and update daily_prices.mayer_multiple';

    /**
     * Execute the console command.
     */
    public function handle(PriceHistoryService $service)
    {
        $since = $this->option('since') ?? null;

        $this->output->writeln(
            'Updating Mayer Multiple since ' . ($since ?? 'last available day')
        );

        $pricesUpdated = $service->updateMayerMultiple($since);

        $this->output->writeln(
            $pricesUpdated ? "{$pricesUpdated} daily_prices updated ✅" : 'No daily_prices updated'
        );
    }
}
