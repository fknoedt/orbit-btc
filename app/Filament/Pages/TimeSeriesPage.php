<?php

namespace App\Filament\Pages;

use App\Services\PriceService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TimeSeriesPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string $view = 'filament.pages.time-series-page';

    protected static ?string $title = 'Time Series';

    public string $selectedPeriod = '365d';
    public array $selectedMetric = ['close'];
    public ?string $startDateViewed = null;
    public ?string $endDateViewed = null;

    public array $chartData = [];

    public function mount(): void
    {
        $this->updateChartData();
    }

    public function updatedSelectedPeriod(): void
    {
        // Do not reset viewed dates; let the chart handle persistence via window.selectedDates
        $this->updateChartData();
        $this->dispatchChartUpdate();
    }

    public function updatedSelectedMetric(): void
    {
        if (count($this->selectedMetric) > 2) {
            $this->selectedMetric = array_slice($this->selectedMetric, 0, 2);
        }
        $this->updateChartData();
        $this->dispatchChartUpdate();
    }

    protected function dispatchChartUpdate(): void
    {
        $dispatchData = [
            'chartId' => 'chart-btc-price',
            'options' => $this->chartData['options'] ?? [],
        ];
        $this->dispatch('refresh-chart', $dispatchData);
    }

    protected function updateChartData(): void
    {
        $priceService = new PriceService();
        $endDate = now();

        if ($this->selectedPeriod === '0d') {
            $startDate = Carbon::parse(config('btc.first_cmc_available_date'));
        } else {
            $days = (int)str_replace('d', '', $this->selectedPeriod);
            $startDate = $endDate->copy()->subDays($days);
        }

        $dailyPrices = $priceService->getDailyPriceByDays($startDate, $endDate, true);

        $series = [];
        $colors = [];
        $yaxis = [];
        $metricOptions = [
            'market_cap' => ['name' => 'Market Cap (USD)', 'yTitle' => 'Market Cap (USD)', 'color' => '#4CAF50'],
            'total_volume' => ['name' => 'Total Volume Traded (USD)', 'yTitle' => 'Volume (USD)', 'color' => '#FF9800'],
            'close' => ['name' => 'BTC Price (USD)', 'yTitle' => 'Price (USD)', 'color' => '#2196F3'],
            'average_fee' => ['name' => 'Average BTC Fee', 'yTitle' => 'Fee (BTC)', 'color' => '#9C27B0'],
            'exchanges_reserve' => ['name' => 'Exchanges Reserve', 'yTitle' => 'Reserve (BTC)', 'color' => '#FF5722'],
            'fear_and_greed' => ['name' => 'Fear & Greed Index', 'yTitle' => 'Index', 'color' => '#607D8B'],
            'mayer_multiple' => ['name' => 'Mayer Multiple', 'yTitle' => 'Multiple', 'color' => '#E91E63'],
        ];

        foreach ($this->selectedMetric as $index => $metric) {
            $firstValue = reset($dailyPrices)[$metric] ?? 0;
            $lastValue = $dailyPrices[array_key_last($dailyPrices)][$metric] ?? 0;
            if (count($this->selectedMetric) === 1) {
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
                'type' => count($this->selectedMetric) === 1 ? 'area' : 'line',
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
                'events' => [
                    'selection' => 'function(chartContext, { xaxis }) {
                        if (xaxis.min && xaxis.max) {
                            const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                            const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                            window.selectedDates = { start: startDate, end: endDate };
                            document.dispatchEvent(new CustomEvent("selectionUpdated", {
                                detail: { start: startDate, end: endDate }
                            }));
                        }
                    }',
                    'zoomed' => 'function(chartContext, { xaxis }) {
                        if (xaxis.min && xaxis.max) {
                            const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                            const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                            window.selectedDates = { start: startDate, end: endDate };
                            document.dispatchEvent(new CustomEvent("selectionUpdated", {
                                detail: { start: startDate, end: endDate }
                            }));
                        }
                    }',
                    'updated' => 'function(chartContext) {
                        const xaxis = chartContext.w.globals.initialConfig.xaxis;
                        if (xaxis.min && xaxis.max) {
                            const minDate = new Date(xaxis.min).toISOString().split("T")[0];
                            const maxDate = new Date(xaxis.max).toISOString().split("T")[0];
                            window.selectedDates = { start: minDate, end: maxDate };
                            document.dispatchEvent(new CustomEvent("selectionUpdated", {
                                detail: { start: minDate, end: maxDate }
                            }));
                        }
                    }',
                ],
            ],
            'series' => $series,
            'colors' => $colors,
            'xaxis' => [
                'type' => 'datetime',
                'labels' => [
                    'format' => 'MMM dd yy',
                ],
                'min' => $this->startDateViewed ? Carbon::parse($this->startDateViewed)->timestamp * 1000 : null,
                'max' => $this->endDateViewed ? Carbon::parse($this->endDateViewed)->timestamp * 1000 : null,
            ],
            'yaxis' => $yaxis,
            'fill' => [
                'type' => count($this->selectedMetric) === 1 ? ['gradient'] : ['solid'],
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

        $this->chartData = [
            'options' => $options,
            'extraJsOptions' => [
                'fullDates' => array_keys($dailyPrices),
            ],
        ];

        if (!$this->startDateViewed || !$this->endDateViewed) {
            $this->startDateViewed = $startDate->toDateString();
            $this->endDateViewed = $endDate->toDateString();
        }
    }

    public function searchSimilar(): void
    {
        $start = $this->startDateViewed;
        $end = $this->endDateViewed;
        $limit = config('btc.time_series_pattern_max_days');

        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $daysDiff = $startDate->diffInDays($endDate);

        if ($daysDiff > $limit) {
            Notification::make()
                ->title('Error')
                ->body("Chosen period ($daysDiff days) exceeds the maximum allowed ($limit days)")
                ->danger()
                ->send();
            return;
        }

        // Placeholder for search logic; currently logs the selection
        \Log::info("Viewed Dates - Start: $start, End: $end, Metrics: " . implode(', ', $this->selectedMetric));
    }
}
