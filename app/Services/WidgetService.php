<?php

namespace App\Services;

use App\Exceptions\ErrorMessages;
use App\Filament\Resources\DashboardResource\Widgets\BitcoinDominanceWidget;
use App\Filament\Resources\DashboardResource\Widgets\BitcoinPriceWidget;
use App\Filament\Resources\DashboardResource\Widgets\BtcPriceChartWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;

class WidgetService
{
    public const string ERROR_SHORT_MESSAGE = '#Error#';

    public function getUserWidgets(): array
    {
        // TODO: persist and make it configurable
        return [
            BitcoinPriceWidget::class,
            BitcoinDominanceWidget::class,
            BtcPriceChartWidget::class,
        ];
    }

    public function getErrorStat(string $label = 'Error'): Stat
    {
        $funnyMessages = ErrorMessages::FUNNY_SHORT_MESSAGES;
        $message = $funnyMessages[array_rand($funnyMessages)];
        return Stat::make($label, $message)
            ->description('Error while loading the widget')
            ->color('warning')
            ->icon('heroicon-o-bug-ant')
            ->iconColor('danger');
    }
}
