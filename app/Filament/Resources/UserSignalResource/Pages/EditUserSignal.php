<?php

namespace App\Filament\Resources\UserSignalResource\Pages;

use App\Filament\Charts\UserSignalChart;
use App\Filament\Resources\UserSignalResource;
use App\Filament\Resources\UserSignalResource\Traits\UserSignalWizardSteps;
use App\Services\UserSignalService;
use Filament\Actions;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\Concerns\HasWizard;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class EditUserSignal extends EditRecord
{
    use HasWizard, UserSignalWizardSteps, UserSignalChart;

    protected static string $resource = UserSignalResource::class;
    private array $originalRecord;
    private array $originalMetrics;
    private bool $scoreUpdated;

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
            // Update oscillation_threshold_enabled in the database
            foreach ($metrics as $metric) {
                $shouldEnable = !empty($metric->oscillation_threshold);
                if ($metric->oscillation_threshold_enabled !== $shouldEnable) {
                    // Clean up
                    if (!$shouldEnable) {
                        $metric->oscillation_threshold = null;
                    }
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

    protected function getHeaderActions(): array
    {
        return array_merge(
            [
                FilamentAction::make('view_score') // Add the custom action
                ->label('Performance')
                    ->button()
                    ->color('gray')
                    ->url(fn ($record) => "/app/user-signal-score/{$record->id}"),
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ],
            $this->getChartActions()
        );
    }

    public function form(Form $form): Form
    {
        return parent::form($form)
            ->schema([
                Wizard::make($this->getSteps())
                    ->cancelAction($this->getCancelFormAction())
                    ->submitAction($this->getSubmitFormAction())
                    ->skippable(true)
                    ->columnSpanFull()
                    ->startOnStep(4)
                    ->persistStepInQueryString()
                    ->nextAction(
                        fn (Action $action) => $action->label('>>')
                    )
                    ->previousAction(
                        fn (Action $action) => $action->label('<<')
                    )
                    ->submitAction(new HtmlString(Blade::render(<<<BLADE
                        <x-filament::button
                            type="submit"
                            size="sm"
                            wire:submit="save"
                        >
                            Save
                        </x-filament::button>
                    BLADE)))
            ])
            ->columns(null);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    /**
     * Hook to capture original data before save and set values to null when threshold is disabled
     */
    protected function beforeSave(): void
    {
        $this->originalRecord = $this->getRecord()->toArray();
        $this->originalMetrics = $this->getRecord()->userSignalMetrics->toArray();

        $livewire = $this->form->getLivewire();
        $formData = $livewire->data;

        if (!empty($formData['userSignalMetrics'])) {
            $updatedMetrics = [];
            foreach ($formData['userSignalMetrics'] as $key => $item) {
                if (!($item['oscillation_threshold_enabled'] ?? false)) {
                    $item['oscillation_threshold'] = null;
                }
                $updatedMetrics[$key] = $item;
            }

            $formData['userSignalMetrics'] = $updatedMetrics;
            $livewire->data = $formData;
        }
    }

    /**
     * Hook to detect changes and run service method after save
     */
    protected function afterSave(): void
    {
        $userSignal = $this->getRecord();
        $userSignalId = $userSignal->id;

        // Check if threshold or userSignalMetrics changed
        $currentMetrics = array_values($userSignal->userSignalMetrics->toArray());

        $this->scoreUpdated = $this->originalRecord['threshold'] != $userSignal->threshold ||
            $this->originalMetrics !== $currentMetrics ||
            $this->originalRecord['buy_or_sell'] != $userSignal->buy_or_sell ||
            $this->originalRecord['time_horizon'] != $userSignal->time_horizon;

        // Run service method if either changed
        if ($this->scoreUpdated) {
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

    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return \Filament\Notifications\Notification::make()
            ->title('Signal Updated')
            ->body($this->scoreUpdated ? 'Scores were updated' : '')
            ->success();
    }
}
