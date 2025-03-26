<?php

namespace App\Filament\Resources\DashboardResource\Widgets;

use App\Exceptions\ExternalApiException;
use App\Services\Btc3rdPartyService;
use App\Services\WidgetService;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;

use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
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
        try {
            // get BTC change in the last day
            $service = new Btc3rdPartyService('CoinMarketCap');
            $stats = $service->getCurrentPriceStats();

            if (empty($stats['market_cap_dominance'])) {
                throw new ExternalApiException(
                    __METHOD__ .
                    ': `market_cap_dominance` is empty: ' .
                    json_encode($stats)
                );
            }

            $stat = Stat::make($this->title, Number::percentage($stats['market_cap_dominance'], 2))
                ->icon('heroicon-o-chart-pie')
                ->descriptionIcon('heroicon-o-chevron-up', 'before')
                ->progress($stats['market_cap_dominance'])
                ->progressBarColor('success')
                ->iconColor('info')
                ->descriptionColor('primary');

        } catch (\Throwable $e) {
            report($e);
            $stat = (new WidgetService())->getErrorStat($this->title);
        }

        return [
            $stat
        ];
    }
}
