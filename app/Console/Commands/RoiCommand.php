<?php

namespace App\Console\Commands;

use App\Services\PriceHistoryService;
use Illuminate\Console\Command;

class RoiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:update-roi {--since=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and update daily_prices.roi_30d and daily_prices.roi_1yr from the daily close';

    /**
     * Execute the console command.
     */
    public function handle(PriceHistoryService $service)
    {
        $since = $this->option('since') ?? null;

        $this->output->writeln(
            'Updating ROI since ' . ($since ?? 'last available day')
        );

        $pricesUpdated = $service->updateRoi($since);

        $this->output->writeln(
            $pricesUpdated ? "{$pricesUpdated} daily_prices updated ✅" : 'No daily_prices updated'
        );
    }
}
