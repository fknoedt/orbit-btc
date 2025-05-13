<?php

namespace App\Services;

use App\Exceptions\ErrorMessages;
use App\Filament\Resources\DashboardResource\Widgets\BitcoinDominanceWidget;
use App\Filament\Resources\DashboardResource\Widgets\BitcoinPriceWidget;
use App\Filament\Resources\DashboardResource\Widgets\MempoolWidget;
use App\Filament\Resources\DashboardResource\Widgets\MetricsWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;

class WidgetService
{
    public const string ERROR_SHORT_MESSAGE = '#Error#';

    public function getUserWidgets(): array
    {
        return [
            MetricsWidget::class,
        ];
    }

    /**
     * @return array|Stat[]
     */
    public function getTopbarWidgets(): array
    {
        return [
            (new BitcoinPriceWidget())->getStats(true),
            (new BitcoinDominanceWidget())->getStats(true),
            (new MempoolWidget())->getStats(true),
        ];
    }

    public function getErrorArray(string $label = 'Error'): array
    {
        $funnyMessages = ErrorMessages::FUNNY_SHORT_MESSAGES;
        $message = $funnyMessages[array_rand($funnyMessages)];

        // remove first part of the message
        $messageParts = explode('!', $message);
        if (count($messageParts) > 1) {
            $message = end($messageParts);
        }

        return [
            'label' => $label,
            'value' => $message,
            'description' => null,
            'color' => 'red',
            'description_color' => 'grey',
            'label_color' => 'grey',
            'icon_color' => 'red',
            'icon' => 'heroicon-o-bug-ant',
        ];
    }

    public function getErrorStat(string $label = 'Error'): Stat
    {
        $funnyMessages = ErrorMessages::FUNNY_SHORT_MESSAGES;
        $message = $funnyMessages[array_rand($funnyMessages)];

        return Stat::make($label, $message)
            ->description('Error while loading widget')
            ->color('warning')
            ->icon('heroicon-o-bug-ant')
            ->iconColor('danger');
    }
}
