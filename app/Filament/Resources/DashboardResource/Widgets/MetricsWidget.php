<?php

namespace App\Filament\Resources\DashboardResource\Widgets;

use App\Exceptions\MetricsFunctionalException;
use App\Services\DailyPriceService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class MetricsWidget extends Widget
{
    protected const int END_DAY_ATTEMPTS = 3;
    protected const int REFERENCE_DAY_ATTEMPTS = 3;
    protected static ?string $heading = 'Metrics';

    protected static ?string $icon = 'heroicon-o-chart-bar';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.metrics';

    public function getWidgetClasses(): array
    {
        return ['metrics-widget'];
    }

    public function getData(): array
    {
        $metrics = DB::table('metrics as m')
            ->join('frequencies as f', 'm.suggested_frequency_id', '=', 'f.id')
            ->leftJoin('user_signal_metrics as usm', 'usm.metric_id', '=', 'm.id')
            ->leftJoin('user_signals as us', 'usm.user_signal_id', '=', 'us.id')
            ->select([
                'm.id',
                'm.name',
                'm.column_name',
                'm.description',
                'f.number_of_days',
                DB::raw('count(usm.id) as times_used'),
                DB::raw('count(us.user_id = ' . auth()->id() . ') as times_used_by_user')
            ])
            ->groupBy('m.id', 'm.name', 'm.column_name', 'm.description', 'f.number_of_days')
            ->orderBy('times_used_by_user', 'desc')
            ->orderBy('times_used', 'desc')
            ->get()
            ->keyBy('id')
            ->toArray();

        // calculate the first DailyPrice we need
        $oldestDay = 0;
        foreach ($metrics as $metric) {
            if ($metric->number_of_days > $oldestDay) {
                $oldestDay = $metric->number_of_days;
            }
        }

        $service = new DailyPriceService();
        // make sure we have a margin for when the current day is not filled up
        $startDate = Carbon::now()->subDays($oldestDay + 2);
        $today = Carbon::now();
        $dailyPrices = $service->getAllDailyPricesKeyByDate($startDate, $today)->toArray();

        foreach ($metrics as $metricId => $metric) {
            try {
                $metric->chart_url = "/admin/time-series-page?selectedMetrics={$metricId}";
                $endDate = Carbon::parse(array_key_last($dailyPrices));
                $column = $metric->column_name;
                $attempts = 0;
                while (true) {
                    $endDailyPrice = $service->getDailyPrice($endDate->format('Y-m-d'));
                    // metric was set for the day: use it
                    if ($endDailyPrice[$column]) {
                        break;
                    }
                    $attempts++;

                    if ($attempts > self::END_DAY_ATTEMPTS) {
                        throw new MetricsFunctionalException(
                            "Could not find first day for {$column} (attempted {$attempts}x)"
                        );
                    }
                    $endDate->subDay();
                }

                $metric->current_value = $endDailyPrice[$column];
                $metric->current_date = $endDate->format('Y-m-d');

                $attempts = 0;
                $referenceDate = $endDate->subDays($metric->number_of_days);
                while (true) {
                    $referenceDailyPrice = $service->getDailyPrice($referenceDate->format('Y-m-d'));
                    // metric was set for the day: use it
                    if ($referenceDailyPrice[$column]) {
                        break;
                    }
                    $attempts++;

                    if ($attempts > self::REFERENCE_DAY_ATTEMPTS) {
                        throw new MetricsFunctionalException(
                            "Could not find reference day for {$column} (attempted {$attempts}x)"
                        );
                    }
                    $referenceDate->addDay();
                }

                $metric->reference_value = $referenceDailyPrice[$column];
                $metric->reference_date = $referenceDate->format('Y-m-d');
                $metric->change = ($metric->current_value - $metric->reference_value) / $metric->current_value;
            } catch (MetricsFunctionalException $exception) {
                unset($metrics[$metricId]);
                continue;
            }
        }

        return ['metrics' => $metrics];
    }

    public function render(): \Illuminate\View\View
    {
        return view(static::$view, $this->getData());
    }
}
