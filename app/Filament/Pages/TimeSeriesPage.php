<?php

namespace App\Filament\Pages;

use App\Services\PriceService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TimeSeriesPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.time-series-page';

    protected static ?string $title = 'Time Series';

    public string $selectedPeriod = '365d';
    public array $selectedMetric = ['close'];
    public ?string $startDateViewed = null;
    public ?string $endDateViewed = null;
    public string $dateLabel = '';

    public array $chartData = [];
    public array $additionalCharts = [];

    public function mount(): void
    {
        $chartData = $this->generateChartData($this->selectedPeriod, $this->selectedMetric);
        $this->chartData = $chartData;
        $this->startDateViewed = $chartData['startDate'];
        $this->endDateViewed = $chartData['endDate'];
        $this->updateDateLabel();
    }

    public function updatedSelectedPeriod(): void
    {
        $chartData = $this->generateChartData($this->selectedPeriod, $this->selectedMetric);
        $this->chartData = $chartData;
        $this->startDateViewed = $chartData['startDate'];
        $this->endDateViewed = $chartData['endDate'];
        $this->updateDateLabel();
        $this->dispatchChartUpdate();
    }

    public function updatedSelectedMetric(): void
    {
        if (count($this->selectedMetric) > 2) {
            $this->selectedMetric = array_slice($this->selectedMetric, 0, 2);
        }
        $chartData = $this->generateChartData($this->selectedPeriod, $this->selectedMetric);
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
            $this->dateLabel = "{$this->startDateViewed} to {$this->endDateViewed}";
        } else {
            $this->dateLabel = '';
        }
    }

    protected function dispatchChartUpdate(): void
    {
        $dispatchData = [
            'chartId' => 'chart-btc-price',
            'options' => $this->chartData['options'] ?? [],
        ];
        $this->dispatch('refresh-chart', $dispatchData);
    }

    protected function generateChartData(string $period, array $metrics, ?string $startDate = null, ?string $endDate = null): array
    {
        $priceService = new PriceService();
        $endDate = $endDate ? Carbon::parse($endDate) : now();

        if ($startDate) {
            $startDate = Carbon::parse($startDate);
        }
        else {
            if ($period === '0d') {
                $startDate = Carbon::parse(config('btc.first_cmc_available_date'));
            } else {
                $days = (int)str_replace('d', '', $period);
                $startDate = $endDate->copy()->subDays($days);
            }
        }

        $dailyPrices = $priceService->getDailyPriceByDays($startDate, $endDate, true);

        $series = [];
        $colors = [];
        $yaxis = [];
        $metricOptions = [
            'market_cap' => ['name' => 'Market Cap (USD)', 'yTitle' => 'Market Cap (USD)', 'color' => '#4CAF50'],
            'total_volume' => ['name' => 'Total Volume Traded (USD)', 'yTitle' => 'Volume (USD)', 'color' => '#2196F3'],
            'close' => ['name' => 'BTC Price (USD)', 'yTitle' => 'Price (USD)', 'color' => '#FF9800'],
            'average_fee' => ['name' => 'Average BTC Fee', 'yTitle' => 'Fee (BTC)', 'color' => '#9C27B0'],
            'exchanges_reserve' => ['name' => 'Exchanges Reserve', 'yTitle' => 'Reserve (BTC)', 'color' => '#FF5722'],
            'fear_and_greed' => ['name' => 'Fear & Greed Index', 'yTitle' => 'Index', 'color' => '#607D8B'],
            'mayer_multiple' => ['name' => 'Mayer Multiple', 'yTitle' => 'Multiple', 'color' => '#E91E63'],
        ];

        foreach ($metrics as $index => $metric) {
            $firstValue = reset($dailyPrices)[$metric] ?? 0;
            $lastValue = $dailyPrices[array_key_last($dailyPrices)][$metric] ?? 0;
            if (count($metrics) === 1) {
                $areaColor = $firstValue > $lastValue ? '#CA2E2E' : ($firstValue < $lastValue ? '#32B000' : '#1968E7');
            } else {
                $areaColor = $metricOptions[$metric]['color'] ?? '#2196F3';
            }

            $series[] = [
                'name' => $metricOptions[$metric]['name'] ?? 'BTC Price (USD)',
                'data' => array_map(fn($date, $item) => [$date, $item[$metric]], array_keys($dailyPrices), $dailyPrices),
            ];
            $colors[] = $areaColor;

            $yaxis[] = [
                'seriesName' => $metricOptions[$metric]['name'] ?? 'BTC Price (USD)',
                'opposite' => $index === 1,
                'title' => [
                    'text' => $metricOptions[$metric]['yTitle'] ?? 'Price (USD)',
                ],
                'decimalsInFloat' => $metric === 'mayer_multiple' ? 2 : 0,
                'tickAmount' => 10,
            ];
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
                'curve' => 'straight',
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
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => $colors,
                    'inverseColors' => true,
                    'opacityFrom' => 0.5,
                    'opacityTo' => 0.0,
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

        // Generate additional chart for the immediate previous period
        $previousEndDate = $startDate->copy()->subDay();
        $previousStartDate = $previousEndDate->copy()->subDays($daysDiff);

        $additionalChartData = $this->generateChartData(
            $this->selectedPeriod,
            $this->selectedMetric,
            $previousStartDate->toDateString(),
            $previousEndDate->toDateString()
        );
        $this->additionalCharts[] = $additionalChartData['options'];

        // Generate two more charts for earlier periods
        for ($i = 1; $i <= 2; $i++) {
            $shiftedEndDate = $startDate->copy()->subDays($i * ($daysDiff + 1));
            $shiftedStartDate = $shiftedEndDate->copy()->subDays($daysDiff);
            $chartData = $this->generateChartData(
                $this->selectedPeriod,
                $this->selectedMetric,
                $shiftedStartDate->toDateString(),
                $shiftedEndDate->toDateString()
            );
            $this->additionalCharts[] = $chartData['options'];
        }

        $this->dispatch('additional-chart-added');
    }
}
