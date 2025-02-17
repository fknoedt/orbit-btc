<?php

namespace App\Filament\Resources\DashboardResource\Widgets;

use App\Clients\MempoolClient;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat; // filament plugin
use Filament\Widgets\StatsOverviewWidget\Stat as OldStat; // Filament default widget
use Illuminate\Support\Number;

class MempoolWidget extends BaseWidget
{
    protected string $title = 'Recommended Fee';
    protected static ?string $pollingInterval = '30s';

    protected const int GOOD_FEE_THRESHOLD = 3;
    protected const int COMMON_FEE_THRESHOLD = 8;
    protected const int HIGH_FEE_THRESHOLD = 20;

    /**
     * @return array|Stat[]
     */
    protected function getStats(): array
    {
        $client = new MempoolClient();

        $recommendedFees = $client->request(
            'get',
            "fees/recommended"
        );

        $color = ($recommendedFees['fastestFee'] <= self::GOOD_FEE_THRESHOLD) ? 'success' :
            (($recommendedFees['fastestFee'] <= self::COMMON_FEE_THRESHOLD) ? 'info':
                (($recommendedFees['fastestFee'] <= self::HIGH_FEE_THRESHOLD) ? 'warning':
                    'danger'));

        return [
            Stat::make($this->title, $recommendedFees['fastestFee'] . ' sats/vB')
                ->description(
                    'half-hour: ' . $recommendedFees['halfHourFee'] . ' sats/vB | ' .
                    'economy: ' . $recommendedFees['economyFee'] . ' sats/vB'
                )
                ->descriptionColor($color)
                ->textColor('default', $color, $color)
                ->icon('heroicon-o-document-currency-dollar')
                ->iconColor('warning')
        ];
    }
}
