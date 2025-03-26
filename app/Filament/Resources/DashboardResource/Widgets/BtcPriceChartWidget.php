<?php

namespace App\Filament\Resources\DashboardResource\Widgets;

use App\Services\Btc3rdPartyService;
use App\Services\PriceService;
use App\Services\WidgetService;
use Carbon\Carbon;
use EightyNine\FilamentAdvancedWidget\AdvancedChartWidget;
use Illuminate\Support\Number;

class BtcPriceChartWidget extends AdvancedChartWidget
{
    protected static string $color = 'success';
    protected static ?string $icon = 'heroicon-o-chart-bar';
    protected static ?string $iconColor = 'warning';
    protected static ?string $label = 'BTC x USD Price Chart';

    //protected static ?string $badge = 'new';
    protected static ?string $badgeColor = 'success';
    protected static ?string $badgeIcon = 'heroicon-o-check-circle';
    protected static ?string $badgeIconPosition = 'after';
    protected static ?string $badgeSize = 'xs';

    // dev mode
    // protected static ?string $pollingInterval = '5';

    public ?string $filter = '30d';

    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full'; // Full width

    protected function getFilters(): ?array
    {
        return [
            '1d' => '24 hours',
            '7d' => '1 Week',
            '30d' => '1 Month',
            '90d' => '3 Months',
            '180d' => '6 Months',
            '365d' => '1 Year',
            '1095d' => '3 Years',
            '1826d' => '5 Years',
            '0d' => 'All-Time'
        ];
    }

    protected function getData(): array
    {
        try {
            $priceService = new PriceService();
            $endDate = now();

            if ($this->filter === '0d') {
                $startDate = Carbon::parse(config('btc.first_available_date'));
            } else {
                $numberOfDays = intval(str_replace('d', '', $this->filter));
                $startDate = now()->subDays($numberOfDays);
            }

            $prices = $priceService->getClosePriceByDays($startDate, $endDate, true, true);

            $stat = [
                'datasets' => [
                    [
                        'label' => 'BTC Price in USD',
                        'data' => array_map(fn ($item) => $item['close'], $prices),
                        'backgroundColor' => '#FA9902FF',
                        'borderColor' => '#CCCCCC',
                        'chartColor' => 'success'
                    ]
                ],
                'labels' => array_keys($prices),
                'fill' => true,
            ];
        } catch (\Throwable $e) {
            report($e);
            $stat = (new WidgetService())->getErrorStat('BTC Price Chart');
        }

        return $stat;
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function getHeading(): string
    {
        try {
            $service = new Btc3rdPartyService();
            $heading = (string) Number::currency($service->getCurrentPrice());
        } catch (\Throwable $e) {
            report($e);
            $heading = WidgetService::ERROR_SHORT_MESSAGE;
        }

        return $heading;
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'colors' => [
                    'forceOverride' => true,
                ],
            ],
            'maintainAspectRatio' => false, // Allow custom height
            'height' => 120, // Reduced to 120px (adjust as needed)
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'ticks' => [
                        'font' => [
                            'size' => 10, // Reduce tick label size for compactness
                        ],
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'font' => [
                            'size' => 10, // Reduce x-axis label size
                        ],
                    ],
                ],
            ],
        ];
    }
}
