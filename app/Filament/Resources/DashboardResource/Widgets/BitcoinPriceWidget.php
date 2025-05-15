<?php

namespace App\Filament\Resources\DashboardResource\Widgets;

use App\Services\Btc3rdPartyService;
use App\Services\WidgetService;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat; // filament plugin
use Filament\Widgets\StatsOverviewWidget\Stat as OldStat; // Filament default widget
use Illuminate\Support\Number;

class BitcoinPriceWidget extends BaseWidget
{
    protected string $title = 'BTC/USD';
    protected static ?string $pollingInterval = '60s';

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    public function getColumns(): int
    {
        return 1;
    }

    /**
     * @todo this is using CMC's indexes and will eventually need to be standardized (CG is standardizing some fields)
     * @return array|Stat[] or, if $compact, array
     */
    public function getStats(bool $compact = false): array
    {
        try {
            // get BTC change in the last day
            $service = new Btc3rdPartyService('CoinMarketCap');
            $priceStats = $service->getCurrentPriceStats();

            $priceStats['percent_change_24h'] = 3.73;
            $color = $priceStats['percent_change_24h'] > 0 ? 'success' : 'danger';

            $description = '24h: ' .
                (!empty($priceStats['volume_change_24h']) ?
                    'vol. ' . Number::percentage($priceStats['volume_change_24h'], 2) . ' | ' :
                    '') .
                'price ' . Number::percentage($priceStats['percent_change_24h'], 2);

            $stat = Stat::make($this->title, Number::currency($priceStats['price']))
                ->description($description)
                ->descriptionColor($color)
                ->textColor('default', $color, $color)
                ->icon('heroicon-o-scale')
                ->iconColor('warning')
                ->descriptionIcon('heroicon-m-arrow-trending-' . ($priceStats['percent_change_24h'] > 0 ? 'up' : 'down'));

            // used in top bar
            if ($compact) {
                $description = 'last 24h: ' . Number::percentage($priceStats['percent_change_24h'], 2);
                $widgetColor = $priceStats['percent_change_24h'] > 0 ? 'green' : 'red';

                $stats = [
                    'value' => Number::currency($priceStats['price']),
                    'color' => $widgetColor,
                    'description' => $description,
                    'description_color' => $widgetColor,
                    'label' => $stat->getLabel(),
                    'label_color' => 'grey',
                    'icon' => $stat->getDescriptionIcon(),
                    'icon_color' => $widgetColor,
                    // polling
                    'id' => 'btc-price-widget',
                    'update_endpoint' => '/web-api/current-price?hr=1',
                    'polling_interval' => $this->getPollingInterval(),
                    'link' => 'https://coinmarketcap.com/currencies/bitcoin/',
                ];
            } else {
                $stats = [
                    $stat
                ];
            }
        } catch (\Throwable $e) {
            report(new \RuntimeException($e->getMessage() . ' - ' . (isset($service) ? json_encode($service->getInfo()) : 'no $service instantiated')));
            $errorMessage = 'BTC Price';
            if ($compact) {
                $stats = (new WidgetService())->getErrorArray($errorMessage);
            } else {
                $stats = [(new WidgetService())->getErrorStat($errorMessage)];
            }
        }

        return $stats;
    }
}
