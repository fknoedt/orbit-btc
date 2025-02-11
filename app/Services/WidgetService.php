<?php

namespace App\Services;

use App\Filament\Resources\DashboardResource\Widgets\BitcoinDominanceWidget;
use App\Filament\Resources\DashboardResource\Widgets\BitcoinPriceWidget;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

class WidgetService
{

    public function __construct()
    {
    }

    public function getUserWidgets(): array
    {
        // TODO: persist and make it configurable
        return [
            // AccountWidget::class,
            BitcoinPriceWidget::class,
            BitcoinDominanceWidget::class,
        ];
    }
}
