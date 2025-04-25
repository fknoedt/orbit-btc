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
    protected $signature = 'btc:update-all-user-signal-scores';

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
        $this->output->info('Recalculating and updating all UserSignals and UserSignalMetrics');
        $since = Carbon::now()->subDays(UserSignalService::MAX_DAYS_BACK)->format('Y-m-d');
        $this->output->info('UserSignalDailyScore will be refreshed for every User Signal with data since ' . $since);

        $stats = $userSignalService->updateDailyScores();

        $this->output->success(print_r($stats, true));
    }
}
