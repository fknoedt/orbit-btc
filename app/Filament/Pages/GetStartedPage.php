<?php

namespace App\Filament\Pages;

use App\Models\UserActivityLog;
use Filament\Pages\Page;

class GetStartedPage extends Page
{
    protected static string $view = 'filament.pages.get-started-page';

    protected static ?string $title = 'Get Started';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    public static function getNavigationBadge(): ?string {
        // count how many incomplete tasks are there
        $incompleteTasks = 0;

        foreach ((new self())->getChecklistStatus() as $status) {
            if (!$status) {
                $incompleteTasks++;
            }
        }

        return $incompleteTasks ?: null;
    }


    public function getChecklistStatus(): array
    {
        $userId = auth()->id();

        return [
            'get-started-page' => UserActivityLog::where('user_id', $userId)
                ->where('action', 'visited_get_started_page')
                ->exists(),
            'metrics' => UserActivityLog::where('user_id', $userId)
                ->where('action', 'visited_metrics')
                ->exists(),
            'alerts' => UserActivityLog::where('user_id', $userId)
                ->where('action', 'upsertted_alert_management')
                ->exists(),
            'time-series' => UserActivityLog::where('user_id', $userId)
                ->where('action', 'visited_time_series_page')
                ->exists(),
            'search-by-similarity' => UserActivityLog::where('user_id', $userId)
                ->where('action', 'searched_ts_by_similarity')
                ->exists(),
            'daily-signal' => UserActivityLog::where('user_id', $userId)
                ->where('action', 'visited_daily_signal')
                ->exists(),
            'user-signals' => UserActivityLog::where('user_id', $userId)
                ->where('action', 'created_user_signal')
                ->exists(),
            'performance-page' => UserActivityLog::where('user_id', $userId)
                ->where('action', 'visited_performance_page')
                ->exists(),
            'dashboard' => UserActivityLog::where('user_id', $userId)
                ->where('action', 'visited_dashboard')
                ->exists(),
        ];
    }

    public function getTitle(): string
    {
        return 'Get Started';
    }
}
