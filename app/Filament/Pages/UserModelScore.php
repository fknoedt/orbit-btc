<?php

namespace App\Filament\Pages;

use App\Filament\Charts\UserModelChart;
use App\Filament\Resources\UserModelResource;
use App\Models\UserModel;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class UserModelScore extends Page
{
    use UserModelChart {
        getChartData as parentGetChartData;
    }

    // Filament page configuration
    protected static string $view = 'filament.pages.user-model-score';

    protected static ?string $title = 'Performance';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    // Properties
    public int $selectedUserModelId;

    public array $modelData = [];

    public array $chartData = [];

    public function mount(): void
    {
        // Set the initial selected model ID
        $this->selectedUserModelId = array_keys($this->userModels)[0] ?? null;
        $this->updateModelData();
        $this->updateChartData();
    }

    #[Computed]
    public function userModels()
    {
        // Fetch all user models for the authenticated user
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
            'chartId' => 'chart-user-model-score-chart',
            'options' => $this->chartData['options'] ?? [],
        ];
        $this->dispatch('refresh-chart', $dispatchData);
    }

    protected function updateModelData(): void
    {
        // Clear model data if no model is selected
        if (!$this->selectedUserModelId) {
            $this->modelData = [];
            return;
        }

        // Fetch the selected user model with its metrics
        $userModel = UserModel::with(['userModelMetrics', 'userModelMetrics.metric'])
            ->find($this->selectedUserModelId);

        // Clear model data if the model is not found
        if (!$userModel) {
            $this->modelData = [];
            return;
        }

        // Populate model data for display
        $this->modelData = [
            'description' => $userModel->description,
            'score' => number_format($userModel->last_score ?? 0, 2),
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
        ];
    }

    protected function updateChartData(): void
    {
        // Clear chart data if no model is selected
        if (!$this->selectedUserModelId) {
            $this->chartData = [];
            return;
        }

        // Update chart data based on the selected model
        $this->chartData = $this->getChartData();
    }

    public function getChartData(): array
    {
        // Override to ensure the correct userModelId is used for chart data
        $this->userModelId = $this->selectedUserModelId;
        $options = $this->getChartOptions($this->userModelId);

        return [
            'options' => $options,
            'fullDates' => $this->fullDates,
            'extraJsOptions' => $this->getExtraJsOptions(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('help')
                ->label('Help')
                ->modalContent(view('help.user-model'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalWidth('5xl') // 75% wider than 'lg'
                ->color('gray'),
            Action::make('edit')
                ->label('Edit')
                ->url(UserModelResource::getUrl('edit', ['record' => UserModel::find($this->selectedUserModelId)]))
                ->color('primary'),
        ];
    }

    // Override to remove the page title from the page (but keep it in navigation)
    public function getTitle(): string
    {
        return '';
    }
}
