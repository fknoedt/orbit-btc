<?php

namespace App\Console\Commands;

use App\Services\LevelingMoveService;
use Illuminate\Console\Command;

class LevelingMoveCommand extends Command
{
    protected $signature = 'btc:update-leveling-moves {--dry-run}';

    protected $description = 'Refresh the percent each metric usually moves, used only to suggest a leveling multiplier';

    /** Refreshes the stored percents. A dry run counts the rows and does not write them. */
    public function handle(LevelingMoveService $service): void
    {
        ini_set('memory_limit', '4096M');
        $dryRun = (bool) $this->option('dry-run');

        $this->output->info(
            $dryRun
                ? 'Checking leveling percents (dry run)'
                : 'Updating leveling percents'
        );

        $result = $service->update($dryRun);

        $this->output->success(
            "{$result['updated']} windows " . ($dryRun ? 'would update' : 'updated')
            . ", {$result['short']} too short, {$result['kept']} kept, {$result['skipped']} skipped ✅"
        );
    }
}
