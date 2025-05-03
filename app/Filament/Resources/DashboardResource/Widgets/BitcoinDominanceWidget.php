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
    protected static ?string $pollingInterval = '1h';

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    public function getColumns(): int
    {
        return 1;
    }

    public function getStats(bool $compact = false): array
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

            if ($compact) {
                $stats = [
                    'value' => $stat->getValue(),
                    'color' => 'inherit',
                    'label' => 'BTC Dominance',
                    'label_color' => 'grey',
                    'icon' => $stat->getIcon(), // TODO: make it nullable in the template
                    'description' => '',
                    'description_color' => 'grey',
                    // polling
                    'id' => 'btc-dominance-widget',
                    'update_endpoint' => '/web-api/btc-dominance?hr=1',
                    'polling_interval' => $this->getPollingInterval(),
                    'link' => 'https://coinmarketcap.com/charts/bitcoin-dominance/'
                ];
            } else {
                $stats = [
                    $stat
                ];
            }

        } catch (\Throwable $e) {
            report(new \RuntimeException($e->getMessage() . ' - ' . (isset($service) ? json_encode($service->getInfo()) : 'no $service instantiated')));
            $errorMessage = 'BTC Dominance';
            if ($compact) {
                $stats = (new WidgetService())->getErrorArray($errorMessage);
            } else {
                $stats = [(new WidgetService())->getErrorStat($errorMessage)];
            }
        }

        return $stats;
    }
}
