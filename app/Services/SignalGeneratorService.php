<?php

namespace App\Services;

use App\Models\Frequency;
use App\Models\Metric;
use App\Models\UserSignal;
use App\Models\UserSignalMetric;
use Carbon\Carbon;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Facades\DB;

class SignalGeneratorService
{
    protected const int UPDATE_METRICS_SINCE_DAYS_AGO = 720;

    protected const int TESTS_PER_METRIC = 96; // 4 frequencies * 2 operators * 3 time_horizons * 2 buy_sell * 2 thresholds

    protected DailyPriceService $dailyPriceService;
    protected MetricService $metricService;
    protected UserSignalService $userSignalService;

    protected array $createdSignals = [];
    protected int $totalSignalsCalculated = 0;

    public function __construct(
        DailyPriceService $dailyPriceService,
        MetricService $metricService,
        UserSignalService $userSignalService
    ) {
        $this->dailyPriceService = $dailyPriceService;
        $this->metricService = $metricService;
        $this->userSignalService = $userSignalService;
    }

    public function updateMetricsMedianChange(): int
    {
        $metrics = Metric::orderBy('column_name')->pluck('column_name', 'id')->toArray();

        $metricsUpdated = 0;
        $dailyStats = array_fill_keys(array_keys($metrics), []);
        $lastDailyPrice = null;

        foreach (
            $this->dailyPriceService->getDailyPriceByDays(
                Carbon::now()->subDays(self::UPDATE_METRICS_SINCE_DAYS_AGO),
                Carbon::now()
            ) as $date => $dailyPrice
        ) {
            foreach ($metrics as $metricId => $metric) {
                if (empty($dailyPrice[$metric])) {
                    if (is_null($lastDailyPrice) || is_null($lastDailyPrice[$metric])) {
                        continue;
                    }
                    $dailyPrice[$metric] = $lastDailyPrice[$metric];
                }
                $dailyStats[$metricId][$date] = $dailyPrice[$metric];
                $lastDailyPrice = $dailyPrice;
            }
        }

        foreach ($dailyStats as $metricId => $metricStats) {
            $median = $this->medianChange($metricStats);
            $highChanges = $this->highChanges($metricStats);
            $lowChanges = $this->lowChanges($metricStats);
            $averageChange = $this->averageChange($metricStats);

            $updates = array_filter([
                'median_change' => $median,
                'high_changes' => $highChanges,
                'low_changes' => $lowChanges,
                'average_change' => $averageChange,
            ], fn($value) => !is_null($value));

            if (!empty($updates)) {
                Metric::where('id', $metricId)->update($updates);
                $metricsUpdated++;
            }
        }

        return $metricsUpdated;
    }

    public function medianChange(array $values): ?float
    {
        ksort($values);
        $prices = array_values($values);

        $changes = [];
        for ($i = 1; $i < count($prices); $i++) {
            if ($prices[$i - 1] != 0) {
                $change = (($prices[$i] - $prices[$i - 1]) / $prices[$i - 1]) * 100;
                $changes[] = $change;
            }
        }

        if (empty($changes)) {
            return null;
        }

        sort($changes);
        $count = count($changes);
        $half = (int) ($count / 2);

        if ($count % 2 === 0) {
            return ($changes[$half - 1] + $changes[$half]) / 2.0;
        }

        return $changes[$half];
    }

    public function averageChange(array $values): ?float
    {
        ksort($values);
        $prices = array_values($values);

        $changes = [];
        for ($i = 1; $i < count($prices); $i++) {
            if ($prices[$i - 1] != 0) {
                $change = (($prices[$i] - $prices[$i - 1]) / $prices[$i - 1]) * 100;
                $changes[] = $change;
            }
        }

        if (empty($changes)) {
            return null;
        }

        return array_sum($changes) / count($changes);
    }

    public function highChanges(array $values): ?float
    {
        ksort($values);
        $prices = array_values($values);

        $changes = [];
        for ($i = 1; $i < count($prices); $i++) {
            if ($prices[$i - 1] != 0) {
                $change = (($prices[$i] - $prices[$i - 1]) / $prices[$i - 1]) * 100;
                $changes[] = $change;
            }
        }

        if (empty($changes)) {
            return null;
        }

        sort($changes);
        $count = count($changes);
        $index = (int) ($count * 0.97); // 95th percentile to capture true highs
        $highChange = $changes[$index] ?? end($changes);

        // Cap at 100% to handle data anomalies
        return min($highChange, 100.0);
    }

    public function lowChanges(array $values): ?float
    {
        ksort($values);
        $prices = array_values($values);

        $changes = [];
        for ($i = 1; $i < count($prices); $i++) {
            if ($prices[$i - 1] != 0) {
                $change = (($prices[$i] - $prices[$i - 1]) / $prices[$i - 1]) * 100;
                $changes[] = $change;
            }
        }

        if (empty($changes)) {
            return null;
        }

        sort($changes);
        $count = count($changes);
        $index = (int) ($count * 0.03); // 5th percentile to capture true lows
        $lowChange = $changes[$index] ?? $changes[0];

        // Cap at -100% to handle data anomalies
        return max($lowChange, -100.0);
    }

    /**
     * Generate and test UserSignals for all metrics, persisting the top performer per metric.
     */
    public function generateSignalForAllMetrics(?OutputInterface $output = null): int
    {
        $metrics = Metric::all();
        // 1, 5, 14 30 days interval
        $frequencies = Frequency::whereNotIn('number_of_days', [3, 7])->pluck('id')->toArray();
        $operators = ['+', '-'];
        $buySellTypes = ['buy', 'sell'];
        $timeHorizons = [1, 5, 15];

        // Reset counters for this run
        $this->createdSignals = [];
        $this->totalSignalsCalculated = 0;

        $output?->writeln('<info>Starting signal generation...</info>');

        foreach ($metrics as $metric) {
            $output?->writeln("<info>Processing metric: {$metric->column_name}</info>");

            // Delete existing system user signals for this metric
            UserSignal::where('user_id', config('data.system_user_id'))
                ->whereExists(function ($query) use ($metric) {
                    $query->select(DB::raw(1))
                        ->from('user_signal_metrics')
                        ->whereColumn('user_signal_metrics.user_signal_id', 'user_signals.id')
                        ->where('metric_id', $metric->id);
                })
                ->delete();

            // Initialize progress bar
            $progressBar = $output ? new ProgressBar($output, self::TESTS_PER_METRIC) : null;
            $progressBar?->start();

            try {
                // Wrap each metric's processing in a transaction
                DB::transaction(function () use ($metric, $frequencies, $operators, $buySellTypes, $timeHorizons, $output, $progressBar) {
                    $bestSignal = null;
                    $bestPerformance = PHP_INT_MIN;

                    foreach ($frequencies as $frequency) {
                        foreach ($operators as $operator) {
                            foreach ($timeHorizons as $timeHorizon) {
                                foreach ($buySellTypes as $buySell) {
                                    $initialThreshold = $operator === '+' ? $metric->high_changes : abs($metric->low_changes);
                                    if (is_null($initialThreshold)) continue;

                                    // Test two thresholds, staying very close to extremes
                                    $thresholds = [
                                        $initialThreshold,          // Full high_changes or |low_changes|
                                        $initialThreshold * 0.9,    // 90% of the extreme
                                    ];

                                    // Test first threshold
                                    $signal1 = $this->createAndTestSignal($metric, $frequency, $operator, $thresholds[0], $buySell, $timeHorizon);
                                    $performance1 = $signal1['performance'];
                                    $progressBar?->advance();

                                    // Test second threshold
                                    $signal2 = $this->createAndTestSignal($metric, $frequency, $operator, $thresholds[1], $buySell, $timeHorizon);
                                    $performance2 = $signal2['performance'];
                                    $progressBar?->advance();

                                    // Keep the best signal
                                    $signals = [
                                        ['signal' => $signal1['signal'], 'performance' => $performance1],
                                        ['signal' => $signal2['signal'], 'performance' => $performance2],
                                    ];

                                    foreach ($signals as $sig) {
                                        if ($sig['performance'] > $bestPerformance) {
                                            if ($bestSignal) $bestSignal->delete();
                                            $bestPerformance = $sig['performance'];
                                            $bestSignal = $sig['signal'];
                                        } else {
                                            $sig['signal']->delete();
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($bestSignal) {
                        $output?->writeln(PHP_EOL . "<info>Best signal for {$metric->column_name}: {$bestSignal->name} (Score: {$bestPerformance})</info>");
                    } else {
                        $output?->writeln(PHP_EOL . "<warning>No valid signals found for {$metric->column_name}</warning>");
                    }
                });
            } catch (\Throwable $exception) {
                report($exception);
                $output?->writeln("<error>Error processing {$metric->column_name}: {$exception->getMessage()}</error>");
            }

            $progressBar?->finish();
            $output?->writeln('');
        }

        $output?->writeln('<info>Signal generation completed.</info>');
        return $this->totalSignalsCalculated;
    }

    /**
     * Create and test a UserSignal with given parameters.
     */
    private function createAndTestSignal(
        Metric $metric,
        int $frequency,
        string $operator,
        float $threshold,
        string $buySell,
        int $timeHorizon
    ): array {
        $name = "🤖 {$metric->name}: {$frequency}|{$operator}|{$threshold}|{$buySell}|{$timeHorizon}";
        if (isset($this->createdSignals[$name])) {
            return $this->createdSignals[$name];
        }
        $signal = new UserSignal();
        $signal->user_id = config('data.system_user_id');
        $signal->name = $name;
        $signal->description = "Top Signal among " . self::TESTS_PER_METRIC . " auto-generated Signals for Metric {$metric->name}";
        $signal->threshold = $threshold;
        $signal->buy_or_sell = $buySell;
        $signal->time_horizon = $timeHorizon;
        $signal->save();

        $signalMetric = new UserSignalMetric();
        $signalMetric->user_signal_id = $signal->id;
        $signalMetric->metric_id = $metric->id;
        $signalMetric->operator = $operator;
        $signalMetric->weight = 1;
        $signalMetric->frequency_id = $frequency;
        $signalMetric->save();

        // Test the temporary UserSignal against history and get its total score
        $stats = $this->userSignalService->updateDailyScores(
            $signal->id,
            null, // Can be null or system user ID
            Carbon::now()->subDays(self::UPDATE_METRICS_SINCE_DAYS_AGO),
            $this->metricService,
            $this->dailyPriceService
        );
        $performance = $stats['lastTotalSignalValue'] ?? 0;

        $this->createdSignals[$name] = ['signal' => $signal, 'performance' => $performance];
        $this->totalSignalsCalculated++;

        return $this->createdSignals[$name];
    }
}
