<?php

namespace App\Console\Commands;

use App\Services\UserModelService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UserModelUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:update-all-user-model-scores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(UserModelService $userModelService)
    {
        $this->output->info('Recalculating and updating all UserModels and UserModelMetrics');
        $since = Carbon::now()->subDays(UserModelService::MAX_DAYS_BACK)->format('Y-m-d');
        $this->output->info('UserModelDailyScore will be refreshed for every User Model with data since ' . $since);

        $dailyScoreCreated = $userModelService->updateDailyScores();

        $this->output->success("{$dailyScoreCreated} days processed");
    }
}
