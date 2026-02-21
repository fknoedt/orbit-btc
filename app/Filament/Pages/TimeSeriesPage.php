<?php

namespace App\Filament\Pages;

use App\Helpers\NumberHelper;
use App\Models\Metric;
use App\Models\UserActivityLog;
use App\Services\DailyPriceService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class TimeSeriesPage extends Page
{
    /** number of charts that will load when searching for pattern matching time series */
    protected const int MAX_MATCHING_TIME_SERIES = 5;

    /** how many extra-days (not highlighted) on each side of a similar pattern chart */
    protected const int MATCHED_TIME_SERIES_MARGIN = 60;

    /** percentage to increase y-axis' $maxValue range (up) */
    protected const int YAXIS_MARGIN_TOP = 5;

    /** when Y axis magnitude is below this value, use manual magnitude/scaling */
    protected const int YAXIS_MIN_MAGNITUDE = 5;

    /** percentage to decrease y-axis' $minValue range (down) */
    protected const int YAXIS_MARGIN_BOTTOM = 5;

    protected const string DEFAULT_METRIC = 'close';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.time-series-page';

    protected static ?string $title = 'Time Series';

    public string $selectedPeriod = '365d';
    public array $selectedMetrics = [];
    public ?string $startDateViewed = null;
    public ?string $endDateViewed = null;
    public string $dateLabel = '';
    public string $metricDescriptionLabel = 'Select up to two metrics to compare. Choose a time range to search for similar patterns.';
    public array $metrics = []; // New property to store all metrics

    public array $chartData = [];
    public array $additionalCharts = [];

    protected array $decimalsOnYAxis = [
        'mayer_multiple',
        'nupl'
    ];

    public function mount(): void
    {
        // Load all metrics from the Metric model
        $this->metrics = Metric::orderBy('name')->get()->map(fn($metric) => [
            'id' => $metric->id,
            'column_name' => $metric->column_name,
            'name' => $metric->name,
        ])->toArray();

        // Load selectedMetrics from GET parameter if set
        if (request()->has('selectedMetrics')) {
            $this->selectedMetrics = Metric::whereIn('id', explode(',', request()->input('selectedMetrics')))
                ->pluck('column_name')->toArray();
        } else {
            $this->selectedMetrics = [self::DEFAULT_METRIC]; // Default to 'close' if not set
        }

        $chartData = $this->generateChartData($this->selectedPeriod, $this->selectedMetrics);
        $this->chartData = $chartData;
        $this->startDateViewed = $chartData['startDate'];
        $this->endDateViewed = $chartData['endDate'];
        $this->updateDateLabel();
        $this->updateMetricDescriptionLabel(); // Initialize label
    }

    public function updatedSelectedPeriod(): void
    {
        $this->additionalCharts = [];
        $this->updateChartData();
    }

    public function updatedSelectedMetrics(): void
    {
        if (count($this->selectedMetrics) > 2) {
            $this->selectedMetrics = array_slice($this->selectedMetrics, 0, 2);
        }
        $this->additionalCharts = [];
        $this->updateChartData();
        $this->updateMetricDescriptionLabel(); // Update label when metrics change

        // Server-side: Inject JS to update URL with metric IDs
        $metricIds = Metric::whereIn('column_name', $this->selectedMetrics)->pluck('id')->implode(',');
        $jsCode = "
            const params = new URLSearchParams(window.location.search);
            if ('{$metricIds}') {
                params.set('selectedMetrics', '{$metricIds}');
            } else {
                params.delete('selectedMetrics');
            }
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            history.pushState({}, '', newUrl);
        ";
        $this->js($jsCode);
    }

    protected function updateChartData(): void
    {
        $chartData = $this->generateChartData($this->selectedPeriod, $this->selectedMetrics);
        $this->chartData = $chartData;
        $this->startDateViewed = $chartData['startDate'];
        $this->endDateViewed = $chartData['endDate'];
        $this->updateDateLabel();
        $this->dispatchChartUpdate();
    }

    public function updateDateRange(string $start, string $end): void
    {
        if ($this->startDateViewed !== $start || $this->endDateViewed !== $end) {
            $this->startDateViewed = $start;
            $this->endDateViewed = $end;
            $this->updateDateLabel();
        }
    }

    protected function updateDateLabel(): void
    {
        if ($this->startDateViewed && $this->endDateViewed) {
            $startDate = Carbon::parse($this->startDateViewed)->format('D, d M Y');
            $endDate = Carbon::parse($this->endDateViewed)->format('D, d M Y');
            $this->dateLabel = "{$startDate} to {$endDate}";
        } else {
            $this->dateLabel = '';
        }
    }

    // New method to update the metric description label
    protected function updateMetricDescriptionLabel(): void
    {
        if (empty($this->selectedMetrics)) {
            $this->metricDescriptionLabel = 'Select up to two metrics to compare. Choose a time range to search for similar patterns.';
            return;
        }

        $metrics = Metric::whereIn('column_name', $this->selectedMetrics)->get()->keyBy('column_name');

        if (count($this->selectedMetrics) === 1) {
            $metric = $this->selectedMetrics[0];
        } else {
            // If two metrics, prefer the non-default metric's description
            $nonDefaultMetric = array_diff($this->selectedMetrics, [self::DEFAULT_METRIC]);
            $metric = reset($nonDefaultMetric) ?: $this->selectedMetrics[0];
        }
        $this->metricDescriptionLabel = $metrics[$metric]->description ?? 'Select up to two metrics to compare. Choose a time range to search for similar patterns.';
    }

    protected function dispatchChartUpdate(array $extra = []): void
    {
        $dispatchData = array_merge([
            'chartId' => 'chart-btc-price',
            'options' => $this->chartData['options'] ?? [],
        ], $extra);
        $this->dispatch('refresh-chart', $dispatchData);
    }

    protected function generateChartData(
        string  $period,
        array   $metrics,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $startHighlight = null,
        ?string $endHighlight = null,
    ): array {
        $priceService = new DailyPriceService();
        $endDate = $endDate ? Carbon::parse($endDate) : now();

        if ($startDate) {
            $startDate = Carbon::parse($startDate);
        } else {
            if ($period === '0d') {
                $startDate = Carbon::parse(config('btc.first_cmc_available_date'));
            } else {
                $days = (int) str_replace('d', '', $period);
                $startDate = $endDate->copy()->subDays($days);
            }
        }

        $dailyPrices = $priceService->getDailyPriceByDays($startDate, $endDate, true);

        $series = [];
        $colors = [];
        $yaxis = [];

        // Fetch metric options dynamically from the database
        $metricOptions = Metric::all()->keyBy('column_name')->toArray();

        foreach ($metrics as $index => $metric) {
            $minValue = null;
            $maxValue = null;

            $firstValue = reset($dailyPrices)[$metric] ?? 0;
            $lastValue = $dailyPrices[array_key_last($dailyPrices)][$metric] ?? 0;
            if (count($metrics) === 1) {
                $areaColor = $firstValue > $lastValue ? '#CA2E2E' : ($firstValue < $lastValue ? '#32B000' : '#1968E7');
            } else {
                $areaColor = $metricOptions[$metric]['color'] ?? '#2196F3';
            }

            $data = [];
            foreach ($dailyPrices as $date => $dailyPrice) {
                $value = $dailyPrice[$metric];
                $data[] = [$date, $value];
                if (! is_null($value) && (is_null($minValue) || $value < $minValue)) {
                    $minValue = $value;
                }
                if (! is_null($value) && (is_null($maxValue) || $value > $maxValue)) {
                    $maxValue = $value;
                }
            }

            // Handle cases with no data (fallback to 0)
            $minValue = $minValue ?? 0;
            $maxValue = $maxValue ?? 0;

            // Calculate margin of minValue and maxValue
            $minPercentage = $minValue * (self::YAXIS_MARGIN_BOTTOM / 100);
            $maxPercentage = $maxValue * (self::YAXIS_MARGIN_TOP / 100);

            $minValueAdjusted = $minValue - $minPercentage;
            $maxValueAdjusted = $maxValue + $maxPercentage;

            $series[] = [
                'name' => $metricOptions[$metric]['name'] ?? 'BTC Price (USD)',
                'data' => $data,
            ];

            $colors[] = $areaColor;

            $yaxisConfig = [
                'seriesName' => $metricOptions[$metric]['name'] ?? 'BTC Price (USD)',
                'opposite' => $index === 1,
                'title' => [
                    'text' => $metricOptions[$metric]['y_title'] ?? 'Price (USD)',
                ],
                'decimalsInFloat' => $metric === 'mayer_multiple' ? 2 : 0,
                'tickAmount' => 10,
                'min' => $minValueAdjusted,
                'max' => $maxValueAdjusted,
            ];

            // When Y-axis range is too small, round based on magnitude
            if ($maxValueAdjusted - $minValueAdjusted < self::YAXIS_MIN_MAGNITUDE) {
                $minMagnitude = NumberHelper::getFloatMagnitude($minValueAdjusted) - 2;
                $maxMagnitude = NumberHelper::getFloatMagnitude($maxValueAdjusted) - 2;
                $yaxisConfig['min'] = round($minValueAdjusted, $minMagnitude * -1);
                $yaxisConfig['max'] = round($maxValueAdjusted, $maxMagnitude * -1);

                // Don't let min go below 0 if minimum Y value is 0 or positive
                if ($yaxisConfig['min'] < 0 && $minValue >= 0) {
                    $yaxisConfig['min'] = 0;
                }
            }

            $yaxis[] = $yaxisConfig;
        }

        $options = [
            'chart' => [
                'type' => count($metrics) === 1 ? 'area' : 'line',
                'height' => 300,
                'width' => '100%',
                'stacked' => false,
                'toolbar' => [
                    'show' => true,
                    'offsetY' => 0,
                    'tools' => [
                        'zoom' => true,
                        'zoomin' => true,
                        'zoomout' => true,
                        'pan' => true,
                        'reset' => true,
                    ],
                ],
                'zoom' => [
                    'enabled' => true,
                    'type' => 'x',
                    'autoScaleYaxis' => false,
                ],
            ],
            'series' => $series,
            'colors' => $colors,
            'stroke' => [
                'curve' => ['straight', 'smooth'],
                'lineCap' => 'butt',
                'width' => 2,
            ],
            'xaxis' => [
                'type' => 'datetime',
                'labels' => [
                    'format' => 'MMM dd yy',
                ],
                'min' => $startDate->timestamp * 1000,
                'max' => $endDate->timestamp * 1000,
            ],
            'yaxis' => $yaxis,
            'fill' => [
                'type' => count($metrics) === 1 ? ['gradient'] : ['solid'],
                'gradient' => [
                    'shade' => 'light',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.6,
                    'gradientToColors' => $colors,
                    'inverseColors' => true,
                    'opacityFrom' => 0.6,
                    'opacityTo' => 0.1,
                    'stops' => [0, 100]
                ],
                'opacity' => 1,
            ],
            'grid' => [
                'show' => false,
                'padding' => [
                    'top' => 0,
                    'bottom' => 0,
                    'left' => 0,
                    'right' => 0,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'tooltip' => [
                'theme' => 'dark',
            ],
        ];

        if ($startHighlight && $endHighlight) {
            // Apex Chart expects timestamp in milliseconds
            // has to be cast as int or it will be parsed in the javascript
            $start = (int) (Carbon::parse($startHighlight)->timestamp * 1000);
            $end = (int) (Carbon::parse($endHighlight)->timestamp * 1000);
            $options['annotations'] = [
                'xaxis' => [
                    [
                        'x' => $start,
                        'x2' => $end, // Convert to milliseconds
                        'fillColor' => '#CCCCCC',
                        'opacity' => 0.4,
                        'label' => [
                            //'borderColor' => '#B3F7CA',
                            'style' => [
                                'fontSize' => '13px',
                                'color' => '#000000',
                                'background' => '#CCCCCC',
                            ],
                            'offsetY' => -10,
                            'text' => 'Pattern Matched',
                        ],
                    ],
                ],
            ];
        }

        return [
            'options' => $options,
            'extraJsOptions' => [
                'fullDates' => array_keys($dailyPrices),
            ],
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ];
    }

    public function searchSimilar(): void
    {
        $start = $this->startDateViewed;
        $end = $this->endDateViewed;

        if (!$start || !$end) {
            Notification::make()
                ->title('Error')
                ->body('Please select a date range on the chart before searching.')
                ->danger()
                ->send();
            return;
        }

        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $daysDiff = $startDate->diffInDays($endDate);

        $limitMin = config('btc.time_series_pattern_min_days', 7);
        $limitMax = config('btc.time_series_pattern_max_days', 365);

        if ($daysDiff > $limitMax) {
            Notification::make()
                ->title('Error')
                ->body("Chosen period ({$daysDiff} days) exceeds the maximum allowed ({$limitMax} days)")
                ->danger()
                ->send();
            return;
        }

        if ($daysDiff < $limitMin) {
            Notification::make()
                ->title('Error')
                ->body("Choose a period of at least {$limitMin} days")
                ->danger()
                ->send();
            return;
        }

        // Clear existing additional charts before new search
        $this->additionalCharts = [];

        // Add annotation to the main chart for the viewed period
        $this->chartData['options']['annotations'] = [
            'xaxis' => [
                [
                    'x' => (int) ($startDate->timestamp * 1000),
                    'x2' => (int) ($endDate->timestamp * 1000),
                    'fillColor' => '#CCCCCC',
                    'opacity' => 0.4,
                    'label' => [
                        //'borderColor' => '#B3F7CA',
                        'style' => [
                            'fontSize' => '13px',
                            'color' => '#000000',
                            'background' => '#CCCCCC',
                        ],
                        'offsetY' => -10,
                        'text' => 'Searched Period',
                    ],
                ],
            ],
        ];

        // Trigger main chart update
        $this->dispatchChartUpdate(['zoomToAnnotation' => true]);

        // find the top pattern matching time series
        $service = new DailyPriceService();

        $metric = $this->selectedMetrics[0];
        if (count($this->selectedMetrics) > 1) {
            Notification::make()
                ->title('')
                ->body("Only {$metric} was searched by pattern matching")
                ->warning()
                ->send();
        }

        $similarTimeSeries = $service->getPatterMatchingTimeSeries(
            $metric,
            $startDate,
            $endDate,
            self::MAX_MATCHING_TIME_SERIES
        );
        $diffInDays = $startDate->diffInDays($endDate);

        foreach ($similarTimeSeries as $timeSeries) {
            $seriesStart = Carbon::parse($timeSeries['start_date']);
            $seriesEnd = $seriesStart->copy()->addDays($diffInDays);
            $additionalChartData = $this->generateChartData(
                $this->selectedPeriod,
                $this->selectedMetrics,
                $seriesStart->copy()->subDays(self::MATCHED_TIME_SERIES_MARGIN),
                $seriesEnd->copy()->addDays(self::MATCHED_TIME_SERIES_MARGIN),
                $timeSeries['start_date'],
                $seriesEnd->format('Y-m-d'),
            );
            $additionalChartData['distance'] = $timeSeries['distance'];
            $additionalChartData['startDate'] = $seriesStart;
            $additionalChartData['endDate'] = $seriesEnd;
            $this->additionalCharts[] = $additionalChartData;
        }

        UserActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'searched_ts_by_similarity',
            'method' => 'GET',
            'date' => now(),
        ]);

        $this->dispatch('additional-chart-added');
    }
}
