<?php

namespace App\Filament\Charts;

use App\Models\DailyPrice;
use App\Models\UserModel;
use App\Models\UserModelDailyScore;
use App\Services\PriceService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

trait UserModelChart
{
    public const int MONTHS_BACK = 3;
    public ?string $selectedDate = null;
    protected array $fullDates = [];

    #[On('open-chart-modal')]
    public function handleChartModal($date = null)
    {
        $date = is_array($date) ? ($date['date'] ?? null) : $date;
        if ($date) {
            $this->selectedDate = $date;
            $this->mountAction('chartDetailModal');
            $this->dispatch('open-modal', id: 'chartDetailModal');
        }
    }

    private function getChartOptions(int $userModelId = null): array
    {
        if (! $userModelId) {
            return [];
        }
        $service = new PriceService();
        $since = (new Carbon())->subMonths(self::MONTHS_BACK);
        $dailyPrices = $service->getAllDailyPricesKeyByDate($since, null, false)->toArray();
        $dailyScores = UserModelDailyScore::where('user_model_id', $userModelId)
            ->where('date', '>=', $since->format('Y-m-d'))
            ->get()
            ->toArray();

        $labels = array_map(function ($item) {
            return Carbon::parse($item)->format('d M');
        }, array_keys($dailyPrices));
        $prices = array_column($dailyPrices, 'close');
        $scores = array_column($dailyScores, 'score');

        $this->fullDates = array_keys($dailyPrices);

        $userModel = UserModel::findOrFail($userModelId);
        $threshold = $userModel->threshold ?? 0;
        $maxPrice = round(max($prices), -3);
        $minPrice = $maxPrice / 2;

        return [
            'series' => [
                [
                    'name' => 'Model Score',
                    'type' => 'column',
                    'data' => $scores,
                ],
                [
                    'name' => 'BTC Price',
                    'type' => 'area',
                    'data' => $prices,
                ]
            ],
            'chart' => [
                'height' => 300,
                'stacked' => false,
                'toolbar' => [
                    'show' => true,
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
                ],
            ],
            'stroke' => [
                'width' => [0, 2]
            ],
            'title' => [
                'text' => 'BTC Price x Model Score per day since ' . Carbon::now()->subMonths(self::MONTHS_BACK)->format('d M Y'),
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'labels' => $labels,
            'xaxis' => [
                'type' => 'category',
                'tickAmount' => 10,
                'labels' => [
                    'show' => true,
                    'hideOverlappingLabels' => true,
                    'rotate' => -45,
                    'rotateAlways' => false,
                    'style' => [
                        'fontSize' => '12px',
                    ]
                ],
            ],
            'yaxis' => [
                [
                    'title' => [
                        'text' => 'Model Score',
                    ],
                    'decimalsInFloat' => 0,
                    'tickAmount' => 10,
                ],
                [
                    'opposite' => true,
                    'title' => [
                        'text' => 'BTC Price',
                    ],
                    'min' => $minPrice,
                    'max' => $maxPrice,
                    'decimalsInFloat' => 0,
                    'tickAmount' => 10,
                ],
            ],
            'tooltip' => [
                'theme' => 'dark',
            ],
            'grid' => [
                'yaxis' => [
                    'lines' => [
                        'show' => false,
                    ]
                ],
                'borderColor' => '#e7e7e7',
                'strokeDashArray' => 0,
            ],
            'colors' => [
                '#897F7F',
                '#FF9800'
            ],
            'fill' => [
                'type' => ['solid', 'gradient'],
                'gradient' => [
                    'shade' => 'light',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => ['#FF9800'], // End color (bottom)
                    'inverseColors' => true,
                    'opacityFrom' => 0.7, // Bottom opacity (fades to transparent)
                    'opacityTo' => 0.2, // Top opacity (solid orange)
                    'stops' => [0, 100] // Gradient stops from top (0%) to bottom (100%)
                ]
            ],
            'plotOptions' => [
                'bar' => [
                    'columnWidth' => '50%',
                    'colors' => [
                        'ranges' => [
                            ['from' => 0, 'to' => $threshold - 1, 'color' => '#897F7F'],
                            ['from' => $threshold, 'to' => $threshold, 'color' => '#FF3F2B'],
                            ['from' => $threshold + 1, 'to' => $scores ? max($scores) : $threshold + 1, 'color' => '#F04444'],
                        ],
                    ],
                    'dataLabels' => [
                        'enabled' => false,
                    ],
                ],
            ]
        ];
    }

    public function getChartData(): array
    {
        $userModel = UserModel::where('user_id', auth()->id())->first();
        $options = $this->getChartOptions($userModel?->id);

        return [
            'options' => $options,
            'fullDates' => $this->fullDates,
            'extraJsOptions' => $this->getExtraJsOptions(),
        ];
    }

    private function getExtraJsOptions(): string
    {
        return Str::of(<<<'JS'
            {
                chart: {
                    events: {
                        dataPointSelection: function(event, chartContext, config) {
                            if (config.seriesIndex !== undefined && config.dataPointIndex !== undefined) {
                                const fullDates = FULL_DATES_PLACEHOLDER;
                                const clickedDate = fullDates[config.dataPointIndex];
                                document.dispatchEvent(new CustomEvent("open-chart-modal", { detail: { date: clickedDate } }));
                            }
                        }
                    }
                }
            }
        JS)
            ->replace('FULL_DATES_PLACEHOLDER', json_encode($this->fullDates))
            ->toString();
    }

    protected function getChartActions(): array
    {
        $date = $this->selectedDate;
        // TODO: see Card #76
        /*$cacheKey = 'daily_price_by_date_' . $date;
        $dailyPrice = Cache::remember($cacheKey, now()->endOfDay(), function () use ($date) {
            return DailyPrice::where('date', $date)->first();
        });
        $cacheKey = 'model_score_by_date_' . $date;
        $dailyScore = Cache::remember($cacheKey, now()->endOfDay(), function () use ($date) {
            return UserModelDailyScore::where('date', $date)->first();
        });*/
        $dailyPrice = null;
        $dailyScore = null;

        return [
            Action::make('chartDetailModal')
                ->modalContent(function () use ($dailyPrice, $dailyScore) {
                    return view('filament.modals.chart-detail', [
                        'date' => $this->selectedDate,
                        'dailyPrice' => $dailyPrice,
                        'dailyScore' => $dailyScore,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalWidth('sm')
                ->extraAttributes(['class' => 'hidden'])
        ];
    }
}
