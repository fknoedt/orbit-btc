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
            'time-series' => UserActivityLog::where('user_id', $userId)
                ->where('action', 'visited_time_series_page')
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
        return '';
    }
}
