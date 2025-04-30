<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DashboardResource\Widgets\MempoolWidget;
use App\Services\UserSignalService;
use App\Services\WidgetService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full'; // Full width

    protected static ?string $pollingInterval = '25s';

    protected const int GOOD_FEE_THRESHOLD = 3;
    protected const int COMMON_FEE_THRESHOLD = 8;
    protected const int HIGH_FEE_THRESHOLD = 20;

    protected function getStats(): array
    {
        $userId = auth()->user()->id;
        $signalService = new UserSignalService();
        $widgetService = new WidgetService();

        try {
            $userStats = $signalService->getUserStats($userId);
            $totalSignalsStat = Stat::make('Total Signals', $userStats['total_signals'])
                ->icon('heroicon-o-rss')
                ->iconPosition('end')
                ->chartColor('success')
                ->description(
                    sprintf(
                        '%s Metrics | %s days history',
                        $userStats['total_metrics'],
                        Number::abbreviate($userStats['total_daily_scores']),
                    )
                )
                ->descriptionColor('success')
                ->iconColor('success');
        } catch (\Throwable $e) {
            report($e);
            $totalSignalsStat = $widgetService->getErrorStat('Signals');
        }

        try {
            $topSignal = $signalService->getUserTopSignal($userId);
            $topSignalStat = Stat::make('Top Performing Signal', Str::limit($topSignal->name, 15, '.'))
                ->icon('heroicon-o-trophy')
                ->iconPosition('end')
                ->chartColor('success')
                ->description(
                    sprintf(
                        'score: %s | rank: %s',
                        Number::format($topSignal->total_signal_value, 1),
                        rand(1, 50000),
                    )
                )
                ->descriptionColor('success')
                ->iconColor('success');
        } catch (\Throwable $e) {
            report($e);
            $topSignalStat = $widgetService->getErrorStat('Top Signal');
        }

        return [
            $totalSignalsStat,
            $topSignalStat,
        ];

    }


}
