<?php

namespace App\Filament\Charts;

use App\Models\DailyPrice;
use App\Models\UserSignal;
use App\Models\UserSignalDailyScore;
use App\Services\DailyPriceService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

trait UserSignalChart
{
    public const int MONTHS_BACK = 3;
    public ?string $selectedDate = null;
    protected array $fullDates = [];
    protected ?int $userSignalId = null;

    #[On('open-chart-modal')]
    public function handleChartModal($date = null)
    {
        $date = is_array($date) ? ($date['date'] ?? null) : $date;

        if ($date) {
            $this->selectedDate = $date;

            // For resource pages (ViewUserSignal, EditUserSignal)
            $actionId = $this->mountAction('chartDetailModal');
            if ($actionId) {
                $this->dispatch('open-modal', id: $actionId);
            }

            $dispatchData = [
                'chartId' => 'daily-score',
                'options' => $this->getChartOptions($this->userSignalId)
            ];
            $this->dispatch('refresh-chart', $dispatchData);
        }
    }

    private function getChartOptions(int $userSignalId = null, int $monthsBack = self::MONTHS_BACK): array
    {
        if (!$userSignalId) {
            return [];
        }
        $service = new DailyPriceService();
        $since = (new Carbon())->subMonths($monthsBack);
        $dailyPrices = $service->getAllDailyPricesKeyByDate($since, null, false)->toArray();
        $dailyScores = UserSignalDailyScore::where('user_signal_id', $userSignalId)
            ->where('date', '>=', $since->format('Y-m-d'))
            ->get()
            ->toArray();

        $this->fullDates = array_keys($dailyPrices);
        $labels = array_map(function ($item) {
            return Carbon::parse($item)->format('d M');
        }, $this->fullDates);

        $prices = array_map(function ($price) {
            return is_numeric($price['close']) ? (float) $price['close'] : 0;
        }, array_values($dailyPrices));

        $scoresByDate = collect($dailyScores)->keyBy('date')->toArray();
        $scores = array_map(function ($date) use ($scoresByDate) {
            return isset($scoresByDate[$date]) && is_numeric($scoresByDate[$date]['score'])
                ? (float) $scoresByDate[$date]['score']
                : 0;
        }, $this->fullDates);

        $userSignal = UserSignal::findOrFail($userSignalId);
        $threshold = $userSignal->threshold ?? 0;
        $maxPrice = !empty($prices) ? round(max($prices), -3) : 1000;
        $minPrice = $maxPrice / 2;

        $maxSignal = !empty($scores) ? round(max($scores), 1) : 0;
        $minSignal = !empty($scores) ? round(min($scores), 1) : 0;

        $firstPrice = $prices[0] ?? 0;
        $lastPrice = $prices[count($prices) - 1] ?? 0;
        $areaColor = $firstPrice > $lastPrice ?
            '#CA2E2E' : // red price
            (
            $firstPrice < $lastPrice ?
                '#32B000' : // green price
                '#1968E7' // blue price -- didn't change 🤯
            );

        return [
            'series' => [
                [
                    'name' => 'Daily Signal',
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
                'height' => 350,
                'width' => '100%',
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
                'text' => 'BTC Price x Signal Score per day since ' . Carbon::now()->subMonths($monthsBack)->format('d M Y'),
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
                        'text' => 'Daily Signal',
                    ],
                    'decimalsInFloat' => 0,
                    'tickAmount' => 10,
                    'min' => $minSignal,
                    'max' => $maxSignal,
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
                $areaColor
            ],
            'fill' => [
                'type' => ['solid', 'gradient'],
                'gradient' => [
                    'shade' => 'light',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => [$areaColor],
                    'inverseColors' => true,
                    'opacityFrom' => 0.3,
                    'opacityTo' => 0.05,
                    'stops' => [0, 100]
                ]
            ],
            'plotOptions' => [
                'bar' => [
                    'columnWidth' => '50%',
                    'colors' => [
                        'ranges' => [
                            ['from' => 0, 'to' => $threshold - 1, 'color' => '#897F7F'],
                            ['from' => $threshold, 'to' => $threshold, 'color' => '#F97315'],
                            ['from' => $threshold + 1, 'to' => $scores ? max($scores) : $threshold + 1, 'color' => '#F97315'],
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
        if (method_exists($this, 'getRecord') && $this->getRecord() instanceof UserSignal) {
            $userSignal = $this->getRecord();
        } else {
            $userSignal = UserSignal::where('user_id', auth()->id())->first();
        }

        $this->userSignalId = $userSignal?->id;
        $options = $this->getChartOptions($userSignal?->id);

        return [
            'options' => $options,
            'fullDates' => $this->fullDates,
            'extraJsOptions' => $this->getExtraJsOptions(),
        ];
    }

    private function getExtraJsOptions(): array
    {
        return [
            'fullDates' => $this->fullDates,
        ];
    }

    protected function getChartActions(): array
    {
        return [
            Action::make('chartDetailModal')
                ->label(isset($this->record) ?
                    'Daily Signal: ' . $this->record->name :
                    'Daily Signal'
                )
                ->modalContent(function () {
                    $cacheKey = "first_daily_price_by_date_{$this->record->id}_{$this->selectedDate}";
                    $dailyPrice = Cache::remember($cacheKey, now()->endOfDay(), function() {
                        return DailyPrice::where('date', $this->selectedDate)->first();
                    });

                    $cacheKey = "first_signal_score_by_date_{$this->record->id}_{$this->selectedDate}";
                    $dailyScore = Cache::remember($cacheKey, now()->endOfDay(), function() {
                        return UserSignalDailyScore::where('date', $this->selectedDate)
                            ->where('user_signal_id', $this->selectedUserSignalId ?? $this->record->id)
                            ->first();
                    });

                    return view('filament.modals.chart-detail', [
                        'date' => $this->selectedDate,
                        'dailyPrice' => $dailyPrice,
                        'dailyScore' => $dailyScore,
                        'userSignal' => $this->record ?? UserSignal::find($this->selectedUserSignalId),
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalWidth('sm')
                ->extraAttributes(['class' => 'hidden'])
        ];
    }
}
