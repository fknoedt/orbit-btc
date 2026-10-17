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

        $userSignal = UserSignal::with([
            'userSignalMetrics.metric' => fn ($query) => $query->withTrashed(),
            'userSignalMetrics.frequency',
        ])->findOrFail($userSignalId);
        $threshold = $userSignal->threshold ?? 0;
        $metricLines = $this->metricLines(
            $userSignal->userSignalMetrics->sortByDesc('weight')->values(),
            $dailyPrices,
            $this->fullDates,
        );
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

        $series = [
            [
                'name' => 'Daily Signal',
                'type' => 'column',
                'data' => $scores,
            ],
            [
                'name' => 'BTC Price',
                'type' => 'area',
                'data' => $prices,
            ],
            ...$metricLines['series'],
        ];
        $strokes = [0, 2, ...$metricLines['strokes']];
        $colors = ['#897F7F', $areaColor, ...$metricLines['colors']];
        $fills = ['solid', 'gradient', ...$metricLines['fills']];

        return [
            'series' => $series,
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
                // One width per series. A shorter list gives later lines a width of 0, so they never appear.
                'width' => $strokes,
            ],
            'title' => [
                'text' => ($metricLines['series'] === []
                    ? 'BTC Price x Signal Score per day since '
                    : 'BTC price, daily signal, and metrics since ')
                    . Carbon::now()->subMonths($monthsBack)->format('d M Y'),
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
                    'decimalsInFloat' => 2,
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
                ...$metricLines['axes'],
            ],
            'tooltip' => [
                'theme' => 'dark',
                'shared' => true,
                'intersect' => false,
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
            'colors' => $colors,
            'legend' => [
                // Each name takes its line color, so the labels stay readable in dark and light.
                'labels' => [
                    'useSeriesColors' => true,
                ],
            ],
            'fill' => [
                'type' => $fills,
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
                            // A score equal to the threshold is a hit. The gray range stops just short of it.
                            ['from' => 0, 'to' => max(0, $threshold - 0.0000001), 'color' => '#897F7F'],
                            ['from' => $threshold, 'to' => max($threshold, $scores ? max($scores) : $threshold), 'color' => '#F97315'],
                        ],
                    ],
                    'dataLabels' => [
                        'enabled' => false,
                    ],
                ],
            ]
        ];
    }

    /**
     * One raw line per metric, each on its own hidden axis.
     *
     * ApexCharts 3.45 maps series[i] to yaxis[i]. One shared axis would draw a metric
     * near 1 as a flat line against the Bitcoin price. seriesName has to equal that
     * series name, or a hidden axis is ignored when the two indexes differ. show false
     * and floating keep the extra axis from narrowing the plot. A missing stroke width
     * or fill type leaves the later line blank, so each series gets its own entry.
     * Null stays null. The default stroke curve is the string "smooth", and that is
     * what breaks the line at a null. An array of curves skips that break, so the
     * curve is left as it is and the price area stays smooth.
     *
     * @param  iterable<int, \App\Models\UserSignalMetric>  $signalMetrics
     * @param  array<string, array<string, mixed>>  $dailyPrices
     * @param  list<string>  $dates
     * @return array{series: list<array>, axes: list<array>, colors: list<string>, strokes: list<int>, fills: list<string>}
     */
    private function metricLines(iterable $signalMetrics, array $dailyPrices, array $dates): array
    {
        $series = [];
        $axes = [];
        $colors = [];
        $strokes = [];
        $fills = [];
        $usedNames = [];
        $palette = ['#38bdf8', '#a78bfa', '#fbbf24', '#34d399', '#fb7185', '#22d3ee'];

        foreach ($signalMetrics as $signalMetric) {
            $metric = $signalMetric->metric;
            if ($metric === null) {
                continue;
            }
            $column = $metric->column_name ?? null;
            if (! is_string($column) || $column === '') {
                continue;
            }

            $label = $metric->name ?: $column;
            $frequency = $signalMetric->frequency?->name;
            if (is_string($frequency) && $frequency !== '') {
                $label .= ' (' . $frequency . ')';
            }
            if (isset($usedNames[$label])) {
                $usedNames[$label]++;
                $label .= ' ' . $usedNames[$label];
            } else {
                $usedNames[$label] = 1;
            }

            $points = [];
            $min = null;
            $max = null;
            foreach ($dates as $date) {
                $raw = $dailyPrices[$date][$column] ?? null;
                if ($raw === null || $raw === '' || ! is_numeric($raw)) {
                    $points[] = null;
                    continue;
                }
                $value = (float) $raw;
                $points[] = $value;
                $min = $min === null ? $value : min($min, $value);
                $max = $max === null ? $value : max($max, $value);
            }

            $series[] = [
                'name' => $label,
                'type' => 'line',
                'data' => $points,
            ];
            $axes[] = $this->metricAxis($label, $min, $max);
            $color = is_string($metric->color ?? null) && preg_match('/^#[0-9A-Fa-f]{6}$/', $metric->color) === 1
                ? $metric->color
                : $palette[count($colors) % count($palette)];
            $colors[] = $color;
            $strokes[] = 2;
            $fills[] = 'solid';
        }

        return compact('series', 'axes', 'colors', 'strokes', 'fills');
    }

    /** Hidden scale for one metric line. The tooltip still shows the real number. */
    private function metricAxis(string $label, ?float $min, ?float $max): array
    {
        $axis = [
            'seriesName' => $label,
            'show' => false,
            'floating' => true,
            'labels' => ['show' => false],
            'axisBorder' => ['show' => false],
            'axisTicks' => ['show' => false],
        ];

        if ($min === null || $max === null) {
            $axis['decimalsInFloat'] = 2;

            return $axis;
        }

        $span = $max - $min;
        $pad = $span > 0 ? $span * 0.08 : (abs($max) * 0.05 ?: 1.0);
        $axis['min'] = $min - $pad;
        $axis['max'] = $max + $pad;
        if ($axis['min'] >= $axis['max']) {
            $axis['max'] = $axis['min'] + 1;
        }

        $scale = max(abs($min), abs($max), $span);
        $axis['decimalsInFloat'] = $scale < 1 ? 3 : ($scale < 10 ? 2 : 0);

        return $axis;
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
