<?php

namespace App\Services;

use App\Filament\Resources\DashboardResource\Widgets\BitcoinDominanceWidget;
use App\Filament\Resources\DashboardResource\Widgets\BitcoinPriceWidget;
use App\Filament\Resources\DashboardResource\Widgets\BtcPriceChartWidget;
use App\Filament\Resources\DashboardResource\Widgets\MempoolWidget;

class WidgetService
{

    public function __construct()
    {
    }

    public function getUserWidgets(): array
    {
        // TODO: persist and make it configurable
        return [
            BitcoinPriceWidget::class,
            BitcoinDominanceWidget::class,
            BtcPriceChartWidget::class,
            MempoolWidget::class,
        ];
    }
}
