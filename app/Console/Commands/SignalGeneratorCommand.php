<?php

namespace App\Console\Commands;

use App\Services\SignalGeneratorService;
use Illuminate\Console\Command;

class SignalGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:signal-generator {--update-metrics-median}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(SignalGeneratorService $service)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);
        if ($this->option('update-metrics-median')) {
            $this->output->info('Updating Metrics median...');
            $metricsUpdated = $service->updateMetricsMedianChange();
            $this->output->info("{$metricsUpdated} Metrics updated");
        } else {
            $this->output->info('Generating Signals...');
            $signalsCalculated = $service->generateSignalForAllMetrics($this->output);
            $this->output->info("{$signalsCalculated} Signals calculated");
        }
        $this->output->info('All done ✅');
    }
}
