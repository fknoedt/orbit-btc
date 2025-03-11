<?php

namespace App\Filament\Charts;

use App\Models\UserModel;
use App\Models\UserModelDailyScore;
use App\Services\PriceService;
use Carbon\Carbon;

trait UserModelChart
{
    public const int MONTHS_BACK = 3;

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

        // y-axis scale
        $maxPrice = max($prices);
        $minPrice = $maxPrice / 2;

        $userModel = UserModel::findOrFail($userModelId);
        $threshold = $userModel->threshold ?? 0;

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
                'height' => 400,
                'type' => 'line'
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
                    'min' => $minPrice, // Set minimum BTC price
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
                            [
                                'from' => 0,
                                'to' => $threshold-1,
                                'color' => '#897F7F'
                            ],
                            [
                                'from' => $threshold,
                                'to' => $threshold,
                                'color' => '#FF3F2B'
                            ],
                            [
                                'from' => $threshold + 1,
                                'to' => $scores ? max($scores) : $threshold + 1,
                                'color' => '#F04444'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
