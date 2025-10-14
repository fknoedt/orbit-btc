<?php

namespace App\Filament\Pages;

use App\Filament\Charts\UserSignalChart;
use App\Filament\Resources\UserSignalResource;
use App\Models\DailyPrice;
use App\Models\UserActivityLog;
use App\Models\UserSignal;
use App\Models\UserSignalDailyScore;
use App\Services\UserSignalService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class PerformancePage extends Page
{
    use UserSignalChart;

    protected const int CHART_MONTHS_BACK = 12;

    protected static string $view = 'filament.pages.performance-page';

    protected static ?string $title = 'Performance';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    public ?int $selectedUserSignalId;

    public array $signalData = [];

    public array $chartData = [];

    public ?string $selectedDate = null;

    public bool $showChartModal = false;

    public function mount(?int $id = null): void
    {
        // Check if there are any User Signals first
        $userSignals = $this->userSignals();

        if (empty($userSignals)) {
            $this->selectedUserSignalId = null; // No Signals, so no selection
            return; // Skip further initialization
        }

        // If an ID is provided via the route, use it; otherwise, fall back to the first available Signal
        $this->selectedUserSignalId = $id ?? array_keys($userSignals)[0];

        // Ensure the selected ID belongs to the authenticated user (security check)
        if ($this->selectedUserSignalId && !array_key_exists($this->selectedUserSignalId, $userSignals)) {
            $this->selectedUserSignalId = array_keys($userSignals)[0];
        }

        $this->updateSignalData();
        $this->updateChartData();
    }

    #[Computed]
    public function userSignals()
    {
        $query = UserSignal::where('user_id', auth()->id());

        if (auth()->user()->role_id === config('data.role_id.super_admin')) {
            $query->orWhere('user_id', config('data.system_user_id'));
        }

        return $query->pluck('name', 'id')
            ->toArray();
    }

    #[On('refresh')]
    public function refresh(): void
    {
        // Refresh the Signal data and chart only if a Signal is selected
        if ($this->selectedUserSignalId) {
            $this->updateSignalData();
            $this->updateChartData();
        }
    }

    public function updatedSelectedUserSignalId(): void
    {
        // Update Signal data and chart when the selected Signal changes
        $this->updateSignalData();
        $this->updateChartData();

        // Dispatch refresh-chart event to update the chart in the frontend
        $dispatchData = [
            'chartId' => 'chart-daily-score', // Match DOM ID
            'options' => $this->chartData['options'] ?? [],
        ];
        $this->dispatch('refresh-chart', $dispatchData);

        // Update the URL to reflect the new selectedUserSignalId
        $this->dispatch('update-url', ['id' => $this->selectedUserSignalId]);
    }

    protected function updateSignalData(): void
    {
        // Clear Signal data if no Signal is selected
        if (!$this->selectedUserSignalId) {
            $this->signalData = [];
            return;
        }

        $userSignal = UserSignal::with(['userSignalMetrics' => function ($query) {
            return $query->orderBy('weight', 'desc');
        }, 'userSignalMetrics.metric'])
            ->find($this->selectedUserSignalId);

        // Clear Signal data if the Signal is not found
        if (!$userSignal) {
            $this->signalData = [];
            return;
        }

        // Populate Signal data for display
        $this->signalData = [
            'paused' => $userSignal->is_paused ?? false, // Add paused status
            'description' => $userSignal->description,
            'total_score' => number_format($userSignal->total_signal_value ?? 0, 2),
            'last_score' => number_format($userSignal->last_score ?? 0, 2),
            'metrics' => $userSignal->userSignalMetrics->map(function ($userSignalMetric) {
                return [
                    'id' => $userSignalMetric->id,
                    'metric_id' => $userSignalMetric->metric_id,
                    'weight' => $userSignalMetric->weight,
                    'operator' => $userSignalMetric->operator,
                    'oscillation_threshold' => $userSignalMetric->oscillation_threshold,
                    'oscillation_threshold_enabled' => $userSignalMetric->oscillation_threshold_enabled,
                    'metric_name' => $userSignalMetric->metric->name,
                    'frequency' => $userSignalMetric->frequency->name,
                ];
            })->toArray(),
            'threshold' => $userSignal->threshold ?? 0,
            'signal' => $userSignal->buy_or_sell ?? 'N/A',
            'horizon' => $userSignal->time_horizon ? ($userSignal->time_horizon . ' day' . ($userSignal->time_horizon == 1 ? '' : 's')) : 'N/A',
            'total_simulated_trades' => $userSignal->total_simulated_trades ?? 0,
            'last_signal_value' => $userSignal->last_signal_value ?? 0,
            'first_date_calculated' => $userSignal->first_date_calculated ?? null,
            'last_date_calculated' => $userSignal->last_date_calculated ?? null,
            'error' => $userSignal->error,
            'warning' => $userSignal->warning,
            'stake_value' => UserSignalService::TRADE_SIZE_IN_USD,
            'total_stake' => Number::abbreviate(UserSignalService::TRADE_SIZE_IN_USD * ($userSignal->total_simulated_trades ?? 0)),
        ];
    }

    protected function updateChartData(): void
    {
        // Clear chart data if no Signal is selected
        if (!$this->selectedUserSignalId) {
            $this->chartData = [];
            return;
        }

        // Update chart data based on the selected Signal, overriding default behavior
        $this->userSignalId = $this->selectedUserSignalId; // Set for trait methods if needed
        $options = $this->getChartOptions($this->selectedUserSignalId, self::CHART_MONTHS_BACK);
        $this->chartData = [
            'options' => $options,
            'extraJsOptions' => $this->getExtraJsOptions(),
        ];
    }

    #[On('open-chart-modal')]
    public function handleChartModal($date = null)
    {
        $date = is_array($date) ? ($date['date'] ?? null) : $date;

        if ($date) {
            $this->selectedDate = $date;
            $this->showChartModal = true;

            $dispatchData = [
                'chartId' => 'chart-daily-score', // Match DOM ID
                'options' => $this->getChartOptions($this->selectedUserSignalId, self::CHART_MONTHS_BACK)
            ];
            $this->dispatch('refresh-chart', $dispatchData);

            UserActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'visited_daily_signal',
                'method' => 'GET',
                'date' => now(),
            ]);
        }
    }

    public function closeChartModal()
    {
        $this->showChartModal = false;
    }

    protected function getHeaderActions(): array
    {
        return array_merge(
            [
                Action::make('edit')
                    ->label('Edit')
                    ->url($this->selectedUserSignalId ?
                        UserSignalResource::getUrl('edit', ['record' => UserSignal::find($this->selectedUserSignalId)])
                        : null
                    )
                    ->color('primary'),
            ],
            $this->getChartActions()
        );
    }

    public function getTitle(): string
    {
        return '';
    }

    protected function getViewData(): array
    {
        return [
            'chartDetailModal' => view('filament.modals.chart-detail', [
                'date' => $this->selectedDate,
                'dailyPrice' => $this->selectedDate ? Cache::remember('first_daily_price_by_date_' . $this->selectedDate, now()->endOfDay(), fn() => DailyPrice::where('date', $this->selectedDate)->first()) : null,
                'dailyScore' => $this->selectedDate ? Cache::remember('first_signal_score_by_date_' . $this->selectedDate, now()->endOfDay(), fn() => UserSignalDailyScore::where('date', $this->selectedDate)->where('user_signal_id', $this->selectedUserSignalId)->first()) : null,
                'userSignal' => UserSignal::find($this->selectedUserSignalId),
            ]),
        ];
    }

    /**
     * Override getChartData to use selectedUserSignalId explicitly
     */
    public function getChartData(): array
    {
        $userSignal = UserSignal::find($this->selectedUserSignalId);
        $this->userSignalId = $userSignal?->id;
        $options = $this->getChartOptions($userSignal?->id, self::CHART_MONTHS_BACK);

        return [
            'options' => $options,
            'extraJsOptions' => $this->getExtraJsOptions(),
        ];
    }
}
