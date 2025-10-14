<?php

namespace App\Filament\Resources\UserSignalResource\Pages;

use App\Filament\Charts\UserSignalChart;
use App\Filament\Resources\UserSignalResource;
use App\Filament\Resources\UserSignalResource\Traits\UserSignalWizardSteps;
use App\Services\UserSignalService;
use Filament\Actions;
use Filament\Actions\Action as FilamentAction; // Alias to avoid conflict
use Filament\Actions\Concerns\HasWizard;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;

class ViewUserSignal extends ViewRecord
{
    use HasWizard, UserSignalWizardSteps, UserSignalChart;

    protected static string $resource = UserSignalResource::class;

    public $threshold;

    public function getTitle(): string
    {
        return 'Signal: ' . $this->record->name;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->threshold = $data['threshold'] ?? 0;

        // Load the userSignalMetrics relationship
        $record = $this->getRecord();
        $metrics = $record->userSignalMetrics()->get();

        if ($metrics->isNotEmpty()) {
            // Update oscillation_threshold_enabled in the database based on oscillation_threshold
            foreach ($metrics as $metric) {
                $shouldEnable = !empty($metric->oscillation_threshold);
                if ($metric->oscillation_threshold_enabled !== $shouldEnable) {
                    $metric->oscillation_threshold_enabled = $shouldEnable;
                    $metric->save();
                }
            }

            // Reload the updated metrics to ensure the form uses the latest data
            $data['userSignalMetrics'] = $record->userSignalMetrics()->get()->toArray();
            $this->form->fill($data);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['threshold'] = $this->threshold;
        return $data;
    }

    public function form(Form $form): Form
    {
        return parent::form($form)
            ->schema([
                Wizard::make($this->getSteps())
                    ->skippable(true)
                    ->startOnStep(4)
                    ->columnSpanFull()
                    ->persistStepInQueryString()
                    ->nextAction(
                        fn (Action $action) => $action->label('>>')
                    )
                    ->previousAction(
                        fn (Action $action) => $action->label('<<')
                    )
                    ->submitAction(null)
            ])
            ->columns(null);
    }

    protected function getHeaderActions(): array
    {
        return array_merge(
            [
                FilamentAction::make('view_score') // Add the custom action
                ->label('Performance')
                    ->button()
                    ->color('gray')
                    ->url(fn ($record) => "/app/user-signal-score/{$record->id}"),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ],
            $this->getChartActions()
        );
    }

    protected function afterSave(): void
    {
        $userSignal = $this->getRecord();
        $userSignalId = $userSignal->id;

        $service = app(UserSignalService::class);
        $service->updateDailyScores($userSignalId);

        $dispatchData = [
            'chartId' => 'chart-daily-score',
            'options' => $this->getChartOptions($userSignalId)
        ];

        // Dispatch a Filament event to refresh the chart
        $this->dispatch('refresh-chart', $dispatchData);
    }
}
