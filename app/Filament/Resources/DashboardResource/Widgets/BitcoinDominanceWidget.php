<?php

namespace App\Filament\Resources\DashboardResource\Widgets;

use App\Services\Btc3rdPartyService;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;

use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat; // filament plugin
use Filament\Widgets\StatsOverviewWidget\Stat as OldStat; // Filament default widget
use Illuminate\Support\Number;

class BitcoinDominanceWidget extends BaseWidget
{
    protected string $title = 'BTC Market Cap Dominance';
    protected string $description = 'Bitcoin x 💩coins';
    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    public function getColumns(): int
    {
        return 1;
    }

    protected function getStats(): array
    {
        // get BTC change in the last day
        $service = new Btc3rdPartyService();
        $stats = $service->getCurrentPriceStats();

        return [
            Stat::make($this->title, Number::percentage($stats['market_cap_dominance'], 2))
                ->icon('heroicon-o-chart-pie')
                ->descriptionIcon('heroicon-o-chevron-up', 'before')
                ->progress($stats['market_cap_dominance'])
                ->progressBarColor('success')
                ->iconColor('info')
                ->descriptionColor('primary')
        ];
    }
}
