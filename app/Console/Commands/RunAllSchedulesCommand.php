<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class RunAllSchedulesCommand extends Command
{
    protected $signature = 'schedule:run-all';
    protected $description = 'Run all scheduled tasks immediately, bypassing time checks';

    public function handle(Schedule $schedule)
    {
        foreach ($schedule->events() as $event) {
            $this->info($event->getSummaryForDisplay());
            $event->run(app());
        }
        $this->info('All scheduled tasks have been executed. Check your email or mailpit.');
    }
}
