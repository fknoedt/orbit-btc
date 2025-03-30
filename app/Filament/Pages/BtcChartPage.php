<?php

namespace App\Filament\Pages;

use App\Services\PriceService;
use Carbon\Carbon;
use Filament\Pages\Page;

class BtcChartPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string $view = 'filament.pages.btc-chart-page';

    protected static ?string $title = 'Time Series';

    public string $selectedPeriod = '365d'; // Default to 1 month (30 days)

    public array $chartData = [];

    public function mount(): void
    {
        $this->updateChartData();
    }

    public function updatedSelectedPeriod(): void
    {
        $this->updateChartData();
        // Dispatch refresh-chart event to update the chart in the frontend
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
        $shortDates = false; // isset($days) && $days < 365;
        $prices = $priceService->getClosePriceByDays($startDate, $endDate, true, $shortDates);

        $firstPrice = reset($prices)['close'];
        $lastPrice = $prices[array_key_last($prices)]['close'];
        $areaColor = $firstPrice > $lastPrice ?
            '#CA2E2E' : // red price
            (
            $firstPrice < $lastPrice ?
                '#32B000' : // green price
                '#1968E7' // blue price -- didn't change 🤯
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
                    'name' => 'BTC Price (USD)',
                    'data' => array_map(fn($date, $price) => [$date, $price], array_keys($prices), array_map(fn ($item) => $item['close'], $prices)),
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
                        'text' => 'Price (USD)',
                    ],
                    'decimalsInFloat' => 0,
                    'tickAmount' => 10,
                ],
            ],
            'fill' => [
                'type' => ['gradient'],
                'gradient' => [
                    'shade' => 'light',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => [$areaColor], // Uses the defined color
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
                'fullDates' => array_keys($prices),
            ],
        ];

        // Dispatch refresh-chart event after updating chart data
        $dispatchData = [
            'chartId' => 'chart-btc-price',
            'options' => $this->chartData['options'] ?? [],
        ];
        $this->dispatch('refresh-chart', $dispatchData);
    }
}
