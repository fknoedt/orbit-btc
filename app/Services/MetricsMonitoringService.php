<?php

namespace App\Services;

use App\Models\Metric;
use App\Traits\OutputBufferTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MetricsMonitoringService
{
    use OutputBufferTrait;

    // minimum 31 to fulfill price_change_30d
    protected const int MONITOR_X_PAST_DAYS = 31;

    protected array $dailyPricesInternalColumns = [
        'id',
        'date',
        'data_source_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected array $disabledColumns = [
        'open',
        'high',
        'low',
        'time_high',
        'time_low',
        'miner_balances',
        'open_interest_futures',
    ];

    protected int $issuesFound = 0;

    public function getIssuesFound(): int
    {
        return $this->issuesFound;
    }

    public function runReport(MetricService $metricService): void
    {
        $metrics = $metricService->getAllMetricsKeyByColumnName(true);

        // add price_change_xd as metrics so it can be validated even if there are no metrics for them
        foreach ([1, 3, 5, 10, 14, 30] as $numberOfDays) {
            $metrics["price_change_{$numberOfDays}d"] = [
                'max_delayed_days' => $numberOfDays,
            ];
        }

        $this->output(count($metrics) . ' Metrics found');

        // get all columns and remove internal ones
        $dailyPricesColumns = Schema::getColumns('daily_prices');

        foreach($dailyPricesColumns as $key => $dailyPriceColumn) {
            if (
                in_array($dailyPriceColumn['name'], $this->dailyPricesInternalColumns) ||
                in_array($dailyPriceColumn['name'], $this->disabledColumns)
            ) {
                unset($dailyPricesColumns[$key]);
            }
        }
        $this->output(count($dailyPricesColumns) . ' daily_prices metric columns found');

        $this->output(
            sprintf('Fetching last %s daily_prices', self::MONITOR_X_PAST_DAYS)
        );

        $lastDailyPrices = DB::table('daily_prices')->orderBy('date', 'desc')->limit(self::MONITOR_X_PAST_DAYS)->get();

        // if last day is not available, this will hold the first available date
        $delayedDailyPrice = null;
        $daysDelayed = 0;
        $invalidColumns = [];
        $deactivatedMetrics = [];
        $metricsStats = [];
        $dailyCounter = 0;
        $currentDate = Carbon::today('America/New_York');
        foreach ($lastDailyPrices as $dailyPrice) {
            $dailyCounter++;
            $dailyPriceDate = Carbon::parse($dailyPrice->date);
            // if first available record is not today's, will report it
            if ($dailyCounter === 1 && $dailyPriceDate < $currentDate) {
                $delayedDailyPrice = $dailyPrice->date;
                $daysDelayed = $currentDate->diffInDays($dailyPriceDate);
                $dailyCounter += $daysDelayed;
            }
            foreach ($dailyPricesColumns as $dailyPriceColumn) {
                $columnName = $dailyPriceColumn['name'];

                if (! empty($metric['deleted_at'])) {
                    echo $metric['deleted_at'] . PHP_EOL;
                    $deactivatedMetrics[$columnName] = true;
                    unset($metrics[$columnName]);
                    continue;
                }

                if ($columnName === 'm2') {
                    dd($metric);
                }

                // daily_prices column doesn't have a related metric
                if (! $metric = $metrics[$columnName] ?? null) {
                    $invalidColumns[$columnName] = true;
                    continue;
                }

                // first value found for metric
                if (! empty($dailyPrice->{$columnName}) && ! isset($metricsStats[$columnName])) {
                    $metricDaysOutdated = $dailyCounter - ($metric['max_delayed_days'] + 1);
                    $metricsStats[$columnName] = [
                        'days_outdated' => $metricDaysOutdated,
                        'last_value' => $dailyPrice->{$columnName},
                    ];
                }
            }
        }

        if ($daysDelayed > 0) {
            $this->output("daily_prices outdated by {$daysDelayed} day(s); first day available: {$delayedDailyPrice}", 'error');
            $this->issuesFound++;
        } else {
            $this->output("daily_prices up to date: {$currentDate->format('Y-m-d')}", 'success');
        }

        if (! empty($invalidColumns)) {
            $this->output('Invalid columns found in daily_prices table: ' . implode(', ', array_keys($invalidColumns)), 'error');
            $this->issuesFound += count($invalidColumns);
        }

        if (! empty($deactivatedMetrics)) {
            $this->output('Deactivated Metrics: ' . implode(', ', array_keys($deactivatedMetrics)), 'note');
        }

        $emptyMetrics = array_diff(array_keys($metrics), array_keys($metricsStats));

        if (! empty($emptyMetrics)) {
            $this->output('Metrics with no prices found in the last ' . self::MONITOR_X_PAST_DAYS . ' days: ' .
                implode(', ', $emptyMetrics), 'error');
            $this->issuesFound += count($emptyMetrics);
        }

        foreach ($metricsStats as $columnName => $metricsStat) {
            if ($metricsStat['days_outdated'] > 0) {
                $this->output(
                    "`{$columnName}` is {$metricsStat['days_outdated']} day(s) outdated: " . round($metricsStat['last_value'],
                        3),
                    'error'
                );
                $this->issuesFound++;
            } else {
                $this->output(
                    "`{$columnName}` is up to date: " . round($metricsStat['last_value'],
                        3
                    ),
                    'success'
                );
            }
            Metric::where('column_name', $columnName)->update(['up_to_date' => !$metricsStat['days_outdated']]);
        }
    }
}
