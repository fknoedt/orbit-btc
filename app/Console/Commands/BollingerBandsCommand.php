<?php

namespace App\Console\Commands;

use App\Services\PriceHistoryService;
use Illuminate\Console\Command;

class BollingerBandsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:update-bollinger-bands {--since=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and update daily_prices Bollinger Bands (bb_upper, bb_middle, bb_lower)';

    /**
     * Execute the console command.
     */
    public function handle(PriceHistoryService $service)
    {
        $since = $this->option('since') ?? null;

        $this->output->writeln(
            'Updating Bollinger Bands since ' . ($since ?? 'last available day')
        );

        $pricesUpdated = $service->updateBollingerBands($since);

        $this->output->writeln(
            $pricesUpdated ? "{$pricesUpdated} daily_prices updated ✅" : 'No daily_prices updated'
        );
    }
}
