<?php

namespace App\Console\Commands;

use App\Services\UserSignalService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UserSignalUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:update-all-user-signal-scores {--user-signal-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(UserSignalService $userSignalService)
    {
        if ($userSignalId = $this->option('user-signal-id')) {
            $message = "Recalculating and updating User Signal #{$userSignalId}";
        } else {
            $message = 'Recalculating and updating all UserSignals and UserSignalMetrics';
        }
        $this->output->info($message);
        $since = Carbon::now()->subDays(UserSignalService::MAX_DAYS_BACK)->format('Y-m-d');
        $this->output->info('UserSignalDailyScore will be refreshed for every User Signal with data since ' . $since);

        $stats = $userSignalService->updateDailyScores($userSignalId);

        $this->output->success(print_r($stats, true));
    }
}
