<?php

namespace App\Console\Commands;

use App\Services\MetricService;
use App\Services\MetricsMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DailyPricesMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:daily-prices-monitoring {--send-email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(MetricsMonitoringService $service, MetricService $metricService): void
    {
        $service->setOutput($this->output);
        $this->info('Running Metrics Monitoring Report on ' . date('Y-m-d') . '...');
        $service->runReport($metricService);

        $mailTo = null;
        if ($this->option('send-email')) {
            if ($service->getIssuesFound()) {
                $mailTo = config('mail.mailers.reports.toAddress');
                $env = config('app.env');
                Mail::send(
                    'emails.metrics-report',
                    ['buffer' => $service->getOutputBuffer(true), 'env' => $env],
                    function ($message) use ($mailTo, $env) {
                        $message->to($mailTo)
                            ->subject("Metrics Monitoring Report [{$env}]")
                            ->from(config('mail.from.address'), config('mail.from.name'));
                    }
                );
            } else {
                $this->info("No issues found - email not sent.");
            }
        }
        $this->output->success($mailTo ? "Email sent to {$mailTo}" : 'Done!');
    }
}
