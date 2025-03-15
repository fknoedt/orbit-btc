<?php

namespace App\Services;

use App\Exceptions\ErrorMessages;
use App\Filament\Resources\DashboardResource\Widgets\BitcoinDominanceWidget;
use App\Filament\Resources\DashboardResource\Widgets\BitcoinPriceWidget;
use App\Filament\Resources\DashboardResource\Widgets\BtcPriceChartWidget;
use App\Filament\Resources\DashboardResource\Widgets\MempoolWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;

class WidgetService
{
    public function getUserWidgets(): array
    {
        // TODO: persist and make it configurable
        return [
            BitcoinPriceWidget::class,
            BitcoinDominanceWidget::class,
            BtcPriceChartWidget::class,
        ];
    }

    public function getErrorStat()
    {
        $funnyMessages = ErrorMessages::FUNNY_SHORT_MESSAGES;
        $message = $funnyMessages[array_rand($funnyMessages)];
        return Stat::make('Error', $message)
            ->description('Error while loading the widget')
            ->color('warning')
            ->icon('heroicon-o-bug-ant')
            ->iconColor('danger');
    }
}
