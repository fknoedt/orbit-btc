<?php

namespace App\Filament\Charts;

use App\Models\UserModel;
use App\Models\UserModelDailyScore;
use App\Services\PriceService;
use Carbon\Carbon;

trait UserModelChart
{
    private function getChartOptions(int $userModelId = null): array
    {
        if (! $userModelId) {
            return [];
        }
        $service = new PriceService();
        $since = (new Carbon())->subMonths(3);
        $dailyPrices = $service->getAllDailyPricesKeyByDate($since)->toArray();
        $dailyScores = UserModelDailyScore::where('user_model_id', $userModelId)
            ->where('date', '>=', $since->format('Y-m-d'))
            ->get()
            ->toArray();

        $labels = array_map(function ($item) {
            return Carbon::parse($item)->format('d M');
        }, array_keys($dailyPrices));
        $prices = array_column($dailyPrices, 'close');
        $scores = array_column($dailyScores, 'score');

        $userModel = UserModel::findOrFail($userModelId);
        $threshold = $userModel->threshold ?? 0;

        $options = [
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
                'text' => ''
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
                    'decimalsInFloat' => 0,
                    'tickAmount' => 10,
                ],
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
                '#1A237E',
                '#FF9800'
            ],
            'fill' => [
                'type' => ['solid', 'gradient'],
                'gradient' => [
                    'shade' => 'light',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => ['#FF9800'],
                    'inverseColors' => false,
                    'opacityFrom' => 0,
                    'opacityTo' => 0.6,
                    'stops' => [0, 100]
                ]
            ],
            'plotOptions' => [
                'bar' => [
                    'columnWidth' => '50%',
                    'colors' => [
                        'ranges' => [
                            [
                                'from' => 0,
                                'to' => $threshold,
                                'color' => '#485FD4'
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

        return $options;
    }
}
