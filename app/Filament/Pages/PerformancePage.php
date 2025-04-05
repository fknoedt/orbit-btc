<?php

namespace App\Filament\Pages;

use App\Filament\Charts\UserModelChart;
use App\Filament\Resources\UserModelResource;
use App\Models\DailyPrice;
use App\Models\UserModel;
use App\Models\UserModelDailyScore;
use App\Services\UserModelService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class PerformancePage extends Page
{
    use UserModelChart;

    protected static string $view = 'filament.pages.performance-page';

    protected static ?string $title = 'Performance';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    public int $selectedUserModelId;

    public array $modelData = [];

    public array $chartData = [];

    public ?string $selectedDate = null;

    public bool $showChartModal = false;

    public function mount(?int $id = null): void
    {
        // If an ID is provided via the route, use it; otherwise, fall back to the first available model
        $this->selectedUserModelId = $id ?? (array_keys($this->userModels)[0] ?? null);

        // Ensure the selected ID belongs to the authenticated user (security check)
        if ($this->selectedUserModelId && !array_key_exists($this->selectedUserModelId, $this->userModels)) {
            $this->selectedUserModelId = array_keys($this->userModels)[0] ?? null;
        }

        $this->updateModelData();
        $this->updateChartData();
    }

    #[Computed]
    public function userModels()
    {
        return UserModel::where('user_id', auth()->id())
            ->pluck('name', 'id')
            ->toArray();
    }

    #[On('refresh')]
    public function refresh(): void
    {
        // Refresh the model data and chart
        $this->updateModelData();
        $this->updateChartData();
    }

    public function updatedSelectedUserModelId(): void
    {
        // Update model data and chart when the selected model changes
        $this->updateModelData();
        $this->updateChartData();

        // Dispatch refresh-chart event to update the chart in the frontend
        $dispatchData = [
            'chartId' => 'chart-daily-score', // Match DOM ID
            'options' => $this->chartData['options'] ?? [],
        ];
        $this->dispatch('refresh-chart', $dispatchData);

        // Update the URL to reflect the new selectedUserModelId
        $this->dispatch('update-url', ['id' => $this->selectedUserModelId]);
    }

    protected function updateModelData(): void
    {
        // Clear model data if no model is selected
        if (!$this->selectedUserModelId) {
            $this->modelData = [];
            return;
        }

        $userModel = UserModel::with(['userModelMetrics' => function ($query) {
            return $query->orderBy('weight', 'desc');
        }, 'userModelMetrics.metric'])
            ->find($this->selectedUserModelId);

        // Clear model data if the model is not found
        if (!$userModel) {
            $this->modelData = [];
            return;
        }

        // Populate model data for display
        $this->modelData = [
            'paused' => $userModel->is_paused ?? false, // Add paused status
            'description' => $userModel->description,
            'total_score' => number_format($userModel->total_signal_value ?? 0, 2),
            'last_score' => number_format($userModel->last_score ?? 0, 2),
            'metrics' => $userModel->userModelMetrics->map(function ($userModelMetric) {
                return [
                    'id' => $userModelMetric->id,
                    'metric_id' => $userModelMetric->metric_id,
                    'weight' => $userModelMetric->weight,
                    'operator' => $userModelMetric->operator,
                    'oscillation_threshold' => $userModelMetric->oscillation_threshold,
                    'oscillation_threshold_enabled' => $userModelMetric->oscillation_threshold_enabled,
                    'metric_name' => $userModelMetric->metric->name,
                ];
            })->toArray(),
            'threshold' => $userModel->threshold ?? 0,
            'signal' => $userModel->buy_or_sell ?? 'N/A',
            'horizon' => $userModel->time_horizon ? ($userModel->time_horizon . ' day' . ($userModel->time_horizon == 1 ? '' : 's')) : 'N/A',
            'total_simulated_trades' => $userModel->total_simulated_trades ?? 0,
            'last_signal_value' => $userModel->last_signal_value ?? 0,
            'first_date_calculated' => $userModel->first_date_calculated ?? null,
            'last_date_calculated' => $userModel->last_date_calculated ?? null,
            'error' => $userModel->error,
            'warning' => $userModel->warning,
            'stake_value' => UserModelService::TRADE_SIZE_IN_USD,
            'total_stake' => Number::abbreviate(UserModelService::TRADE_SIZE_IN_USD * ($userModel->total_simulated_trades ?? 0)),
        ];
    }

    protected function updateChartData(): void
    {
        // Clear chart data if no model is selected
        if (!$this->selectedUserModelId) {
            $this->chartData = [];
            return;
        }

        // Update chart data based on the selected model, overriding default behavior
        $this->userModelId = $this->selectedUserModelId; // Set for trait methods if needed
        $options = $this->getChartOptions($this->selectedUserModelId, 5);
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
                'options' => $this->getChartOptions($this->selectedUserModelId, 5)
            ];
            $this->dispatch('refresh-chart', $dispatchData);
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
                    ->url(UserModelResource::getUrl('edit', ['record' => UserModel::find($this->selectedUserModelId)]))
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
                'dailyScore' => $this->selectedDate ? Cache::remember('first_model_score_by_date_' . $this->selectedDate, now()->endOfDay(), fn() => UserModelDailyScore::where('date', $this->selectedDate)->where('user_model_id', $this->selectedUserModelId)->first()) : null,
                'userModel' => UserModel::find($this->selectedUserModelId),
            ]),
        ];
    }

    /**
     * Override getChartData to use selectedUserModelId explicitly
     */
    public function getChartData(): array
    {
        $userModel = UserModel::find($this->selectedUserModelId);
        $this->userModelId = $userModel?->id;
        $options = $this->getChartOptions($userModel?->id, 5);

        return [
            'options' => $options,
            'extraJsOptions' => $this->getExtraJsOptions(),
        ];
    }
}
