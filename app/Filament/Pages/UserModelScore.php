<?php

namespace App\Filament\Pages;

use App\Filament\Charts\UserModelChart;
use App\Models\UserModel;
use Filament\Pages\Page;

class UserModelScore extends Page
{
    use UserModelChart;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static string $view = 'filament.pages.user-model-score';
    protected static ?string $title = 'Performance';
    protected static ?int $navigationSort = 3;

    public ?int $selectedUserModelId = null; // Store the selected UserModel ID
    public array $chartData = []; // Store chart data for the selected model
    public array $modelData = []; // Store the data to display in the view
    public array $userModels = []; // Store the list of user models for the select

    public function mount(): void
    {
        // Fetch all user models for the authenticated user for the select
        $this->userModels = UserModel::where('user_id', auth()->id())
            ->pluck('name', 'id')
            ->toArray();

        // Pre-select the first UserModel for the authenticated user
        $firstModel = UserModel::where('user_id', auth()->id())->first();
        $this->selectedUserModelId = $this->selectedUserModelId ?? $firstModel?->id;

        // Load the model data and chart data
        $this->loadModelData();
        $this->updateChartData();
    }

    public function loadModelData(): void
    {
        if (!$this->selectedUserModelId) {
            $this->modelData = [];
            return;
        }

        $userModel = UserModel::with('userModelMetrics.metric')
            ->find($this->selectedUserModelId);
        if (!$userModel) {
            $this->modelData = [];
            return;
        }

        // Prepare the data for the view
        $this->modelData = [
            'name' => $userModel->name,
            'score' => $userModel->last_score ?? 'N/A',
            'metrics' => $userModel->userModelMetrics->map(function ($userModelMetric) {
                return [
                    'id' => $userModelMetric->id,
                    'metric_id' => $userModelMetric->metric_id,
                    'weight' => $userModelMetric->weight,
                    'metric_name' => $userModelMetric->metric ? $userModelMetric->metric->name : 'N/A',
                ];
            })->toArray(),
            'threshold' => $userModel->threshold ?? 'N/A',
            'signal' => ucfirst($userModel->buy_or_sell ?? 'N/A'),
            'horizon' => ($userModel->time_horizon ?? 'N/A') . ' days',
            'info' => $userModel->description ?? 'N/A',
        ];
    }

    public function updateChartData(): void
    {
        if ($this->selectedUserModelId) {
            $this->userModelId = $this->selectedUserModelId; // Set the userModelId for the trait
            $this->chartData = $this->getChartData();
        } else {
            $this->chartData = [];
        }
    }

    protected function getHeaderActions(): array
    {
        return $this->getChartActions();
    }
}
