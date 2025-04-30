<?php

namespace App\Filament\Resources\DashboardResource\Widgets;

use App\Clients\MempoolClient;
use App\Services\WidgetService;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;

/**
 * This widget is not being called directly but used to populate StatsOverview
 * WidgetService should serve the widgets related to metrics only
 */
class MempoolWidget extends BaseWidget
{
    protected string $title = 'Current Recommended Fee';
    protected static ?string $pollingInterval = '177s'; // 3 minutes, avoid conflicting with other requests

    protected const int GOOD_FEE_THRESHOLD = 3;
    protected const int COMMON_FEE_THRESHOLD = 8;
    protected const int HIGH_FEE_THRESHOLD = 20;

    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    public function getColumns(): int
    {
        return 1;
    }

    /**
     * @return array|Stat[]
     */
    public function getStats(bool $compact = false): array
    {
        try {
            $client = new MempoolClient();

            $recommendedFees = $client->request(
                'get',
                "fees/recommended"
            );

            $color = ($recommendedFees['fastestFee'] <= self::GOOD_FEE_THRESHOLD) ? 'success' :
                (($recommendedFees['fastestFee'] <= self::COMMON_FEE_THRESHOLD) ? 'info':
                    (($recommendedFees['fastestFee'] <= self::HIGH_FEE_THRESHOLD) ? 'warning':
                        'danger'));

            $stat = Stat::make($this->title, $recommendedFees['fastestFee'] . ' sats/vB')
                ->textColor('default', $color, $color)
                ->icon(asset('images/mempool.ico'))
                ->iconPosition('end')
                ->description(
                    'half-hour: ' . $recommendedFees['halfHourFee'] . ' sats/vB | ' .
                    'economy: ' . $recommendedFees['economyFee'] . ' sats/vB'
                )
                ->chartColor('success')
                ->descriptionColor('success')
                ->textColor('default', $color, $color)
                ->iconColor('success');

            if ($compact) {
                $stats = [
                    'value' => $stat->getValue(),
                    'color' => match($color) {
                        'success' => 'green',
                        'info' => 'blue',
                        'warning' => 'orange',
                        'danger' => 'red',
                    },
                    'label' => 'Rec. Fee',
                    'label_color' => 'grey',
                    'icon' => $stat->getIcon(),
                    'description' => 'economy: ' . $recommendedFees['economyFee'],
                    'description_color' => 'grey',
                    'id' => 'mempool-widget',
                    'update_endpoint' => '/web-api/recommended-fee?hr=1',
                    'polling_interval' => '10m', // every 10 minutes
                    'link' => 'https://mempool.space/',
                ];
            } else {
                $stats = [
                    $stat
                ];
            }
        } catch (\Throwable $e) {
            report($e);
            $errorMessage = 'Mempool Error';
            if ($compact) {
                $stats = (new WidgetService())->getErrorArray($errorMessage);
            } else {
                $stats = [(new WidgetService())->getErrorStat($errorMessage)];
            }
        }

        return $stats;
    }
}
