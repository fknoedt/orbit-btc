<?php

namespace App\Filament\Resources\DashboardResource\Widgets;

use App\Services\Btc3rdPartyService;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat; // filament plugin
use Filament\Widgets\StatsOverviewWidget\Stat as OldStat; // Filament default widget
use Illuminate\Support\Number;

class BitcoinPriceWidget extends BaseWidget
{
    protected string $title = 'BTC/USD';
    protected static ?string $pollingInterval = '10s';

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    public function getColumns(): int
    {
        return 1;
    }

    /**
     * @todo this is using CMC's indexes and will eventually need to be standardized
     * @return array|Stat[]
     */
    protected function getStats(): array
    {
        // get BTC change in the last day
        $service = new Btc3rdPartyService();
        $stats = $service->getCurrentPriceStats();

        $color = $stats['percent_change_24h'] > 0 ? 'success' : 'danger';

        return [
            Stat::make($this->title, Number::currency($stats['price']))
                ->description(
                    PHP_EOL . 'Last 24h:
    vol. ' . Number::percentage($stats['volume_change_24h'], 2) . ' |
    price ' . Number::percentage($stats['percent_change_24h'], 2)
                )
                ->descriptionColor($color)
                ->textColor('default', $color, $color)
                ->icon('heroicon-o-scale')
                ->iconColor('warning')
                ->descriptionIcon('heroicon-m-arrow-trending-' . ($stats['percent_change_24h'] > 0 ? 'up' : 'down'))
        ];
    }
}
