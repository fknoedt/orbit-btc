<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DashboardResource\Widgets\MempoolWidget;
use App\Services\UserModelService;
use App\Services\WidgetService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full'; // Full width

    protected static ?string $pollingInterval = '30s';

    protected const int GOOD_FEE_THRESHOLD = 3;
    protected const int COMMON_FEE_THRESHOLD = 8;
    protected const int HIGH_FEE_THRESHOLD = 20;

    protected function getStats(): array
    {
        $userId = auth()->user()->id;
        $modelService = new UserModelService();
        $widgetService = new WidgetService();

        try {
            $mempoolWidget = new MempoolWidget();
            $mempoolStat = $mempoolWidget->getStats()[0];
        } catch (\Throwable $e) {
            report($e);
            $mempoolStat = $widgetService->getErrorStat('Mempool');
        }

        try {
            $userStats = $modelService->getUserStats($userId);
            $totalModelsStat = Stat::make('Total Models', $userStats['total_models'])
                ->icon('heroicon-o-cube')
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
            $totalModelsStat = $widgetService->getErrorStat('Models');
        }

        try {
            $topModel = $modelService->getUserTopModel($userId);
            $topModelStat = Stat::make('Top Performing Model', Str::limit($topModel->name, 15, '.'))
                ->icon('heroicon-o-trophy')
                ->iconPosition('end')
                ->chartColor('success')
                ->description(
                    sprintf(
                        'score: %s | rank: %s',
                        Number::format($topModel->total_signal_value, 1),
                        rand(1, 50000),
                    )
                )
                ->descriptionColor('success')
                ->iconColor('success');
        } catch (\Throwable $e) {
            report($e);
            $topModelStat = $widgetService->getErrorStat('Top Model');
        }

        return [
            $totalModelsStat,
            $topModelStat,
            $mempoolStat,
        ];

    }


}
