<?php

namespace App\Console\Commands;

use App\Services\DailyStatsService;
use Illuminate\Console\Command;

class FillDailyPricesInCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:fill-daily-prices-in {columns?} {--since=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill null intervals in daily_prices';

    /**
     * Execute the console command.
     */
    public function handle(DailyStatsService $service)
    {
        ini_set('memory_limit', '4096M');
        $since = $this->option('since') ?? null;
        $columns = $this->argument('columns');
        $allowedColumns = $service::FILL_FORWARD_ALLOWED_COLUMNS;
        $chosenColumns = [];

        if (empty($columns)) {
            $chosenColumns = $allowedColumns;
        } else {
            foreach (explode(',', $columns) as $column) {
                if (! in_array($column, $allowedColumns)) {
                    throw new \InvalidArgumentException(
                        "invalid column `{$column}` -- valid: " .
                        implode(",", array_keys($allowedColumns))
                    );
                }
                $chosenColumns[] = $column;
            }
        }

        foreach ($chosenColumns as $column) {
            $this->output->info("Filling in column {$column} since " . ($since ?? 'last available day'));
            $pricesUpdated = $service->fillForward($column, $since);
            $this->output->success(
                $pricesUpdated ? "{$pricesUpdated} daily_prices updated ✅" : 'No daily_prices updated'
            );
        }

        $this->output->success('All Done ✅');
    }
}
