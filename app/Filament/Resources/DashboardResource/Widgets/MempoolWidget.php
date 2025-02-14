<?php

namespace App\Filament\Resources\DashboardResource\Widgets;

use App\Clients\MempoolClient;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat; // filament plugin
use Filament\Widgets\StatsOverviewWidget\Stat as OldStat; // Filament default widget
use Illuminate\Support\Number;

class MempoolWidget extends BaseWidget
{
    protected string $title = 'Mempool';
    protected static ?string $pollingInterval = '10s';

    /**
     * @return array|Stat[]
     */
    protected function getStats(): array
    {
        $client = new MempoolClient();

        dd($client->request(
            'get',
            'mining/blocks/fee-rates/1y',
            [],
            []
        ));

        $color = $stats['percent_change_24h'] > 0 ? 'success' : 'danger';

        return [
            Stat::make($this->title, Number::currency($stats['price']))
                ->description(
                    PHP_EOL . 'Last 24h:
    price ' . Number::percentage($stats['percent_change_24h'], 2) . ' |
    volume ' . Number::percentage($stats['volume_change_24h'], 2)
                )
                ->descriptionColor($color)
                ->textColor('default', $color, $color)
                ->icon('heroicon-o-scale')
                ->iconColor('warning')
                ->descriptionIcon('heroicon-m-arrow-trending-' . ($stats['percent_change_24h'] > 0 ? 'up' : 'down'))
        ];
    }
}
