<?php

namespace App\Filament\Pages;

use App\Services\PriceService;
use Carbon\Carbon;
use Filament\Pages\Page;

class TimeSeriesPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string $view = 'filament.pages.time-series-page';

    protected static ?string $title = 'Time Series';

    public string $selectedPeriod = '365d'; // Default to 1 year
    public string $selectedMetric = 'close'; // Default to BTC Price

    public array $chartData = [];

    public function mount(): void
    {
        $this->updateChartData();
    }

    public function updatedSelectedPeriod(): void
    {
        $this->updateChartData();
        $dispatchData = [
            'chartId' => 'chart-btc-price',
            'options' => $this->chartData['options'] ?? [],
        ];
        $this->dispatch('refresh-chart', $dispatchData);
    }

    public function updatedSelectedMetric(): void
    {
        $this->updateChartData();
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

        // don't use $shortDates or it will break the chart when 1+ year (overlapping)
        $dailyPrices = $priceService->getDailyPriceByDays($startDate, $endDate, true);

        $firstValue = reset($dailyPrices)[$this->selectedMetric] ?? 0;
        $lastValue = $dailyPrices[array_key_last($dailyPrices)][$this->selectedMetric] ?? 0;

        $areaColor = $firstValue > $lastValue ?
            '#CA2E2E' : // red
            (
            $firstValue < $lastValue ?
                '#32B000' : // green
                '#1968E7' // blue -- no change 🤯
            );

        $options = [
            'chart' => [
                'type' => 'area',
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
                    'autoScaleYaxis' => true,
                ],
                'events' => [
                    'selection' => 'function(chartContext, { xaxis, yaxis }) {
                        if (xaxis.min && xaxis.max) {
                            const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                            const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                            window.selectedDates = { start: startDate, end: endDate };
                            document.dispatchEvent(new CustomEvent("selectionUpdated", {
                                detail: { start: startDate, end: endDate }
                            }));
                        }
                    }',
                    'zoomed' => 'function(chartContext, { xaxis, yaxis }) {
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
            'series' => [
                [
                    'name' => match ($this->selectedMetric) {
                        'market_cap' => 'Market Cap (USD)',
                        'total_volume' => 'Total Volume Traded (USD)',
                        'close' => 'BTC Price (USD)',
                        'average_fee' => 'Average BTC Fee',
                        'exchanges_reserve' => 'Exchanges Reserve',
                        'fear_and_greed' => 'Fear & Greed Index',
                        'mayer_multiple' => 'Mayer Multiple',
                        default => 'BTC Price (USD)',
                    },
                    'data' => array_map(fn($date, $item) => [
                        $date,
                        $item[$this->selectedMetric]],
                        array_keys($dailyPrices),
                        $dailyPrices
                    ),
                ],
            ],
            'colors' => [
                $areaColor
            ],
            'xaxis' => [
                'type' => 'datetime',
                'labels' => [
                    'format' => 'MMM dd yy',
                ],
            ],
            'yaxis' => [
                [
                    'title' => [
                        'text' => match ($this->selectedMetric) {
                            'market_cap' => 'Market Cap (USD)',
                            'total_volume' => 'Volume (USD)',
                            'close' => 'Price (USD)',
                            'average_fee' => 'Fee (BTC)',
                            'exchanges_reserve' => 'Reserve (BTC)',
                            'fear_and_greed' => 'Index',
                            'mayer_multiple' => 'Multiple',
                            default => 'Price (USD)',
                        },
                    ],
                    'decimalsInFloat' => $this->selectedMetric === 'mayer_multiple' ? 2 : 0,
                    'tickAmount' => 10,
                ],
            ],
            'fill' => [
                'type' => ['gradient'],
                'gradient' => [
                    'shade' => 'light',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => [$areaColor],
                    'inverseColors' => true,
                    'opacityFrom' => 0.5,
                    'opacityTo' => 0.0,
                    'stops' => [0, 100]
                ]
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

        $dispatchData = [
            'chartId' => 'chart-btc-price',
            'options' => $this->chartData['options'] ?? [],
        ];
        $this->dispatch('refresh-chart', $dispatchData);
    }
}
