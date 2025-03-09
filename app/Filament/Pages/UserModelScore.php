<?php

namespace App\Filament\Pages;

use App\Models\UserModel;
use App\Models\UserModelDailyScore;
use App\Services\PriceService;
use Carbon\Carbon;
use Filament\Pages\Page;

class UserModelScore extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static string $view = 'filament.pages.user-model-score';
    public bool $deferLoading = false;

    public ?array $options = [];

    protected function getViewData(): array
    {
        $userModelId = 2;
        $service = new PriceService();
        $since = (new Carbon())->subMonths(3);
        $dailyPrices = $service->getAllDailyPricesKeyByDate($since)->toArray();
        $dailyScores = UserModelDailyScore::where('user_model_id', $userModelId)->where('date', '>=', $since->format('Y-m-d'))->get()->toArray();

        $labels = array_map(function ($item) {
            return Carbon::parse($item)->format('d M');
        }, array_keys($dailyPrices));
        $prices = array_column($dailyPrices, 'close');
        $scores = array_column($dailyScores, 'score');

        $userModel = UserModel::find($userModelId);
        $threshold = $userModel->threshold ?? 0;

        // TODO: hot & cold days

        $this->options = [
            'series' => [
                [
                    'name' => 'Model Score',
                    'type' => 'column', // Confirmed as column
                    'data' => $scores, // Updated with x, y, fillColor
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
                '#1A237E', // Default (overridden by fillColor)
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
                                'color' => '#485FD4' // light blue below threshold
                            ],
                            [
                                'from' => $threshold + 1,
                                'to' => max($scores),
                                'color' => '#17B528' // green for above threshold
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return [
            'options' => $this->options,
        ];
    }
}
