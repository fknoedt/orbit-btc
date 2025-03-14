<?php

namespace App\Filament\Resources\UserModelResource\Pages;

use App\Filament\Charts\UserModelChart;
use App\Filament\Resources\UserModelResource;
use App\Filament\Resources\UserModelResource\Traits\UserModelWizardSteps;
use App\Services\UserModelService;
use Filament\Actions;
use Filament\Actions\Concerns\HasWizard;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class EditUserModel extends EditRecord
{
    use HasWizard, UserModelWizardSteps, UserModelChart;

    protected static string $resource = UserModelResource::class;
    private array $originalRecord;
    private array $originalMetrics;
    private bool $scoreUpdated;

    public $threshold;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->threshold = $data['threshold'] ?? 0;

        // Load the userModelMetrics relationship
        $record = $this->getRecord();
        $metrics = $record->userModelMetrics()->get();

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
            $data['userModelMetrics'] = $record->userModelMetrics()->get()->toArray();
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
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
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
        $this->originalMetrics = $this->getRecord()->userModelMetrics->toArray();

        // Update userModelMetrics to set operator and oscillation_threshold to null when oscillation_threshold_enabled is false
        $formData = $this->form->getState();
        if (isset($formData['userModelMetrics'])) {
            $formData['userModelMetrics'] = array_map(function ($item) {
                if (isset($item['oscillation_threshold_enabled']) && $item['oscillation_threshold_enabled'] === false) {
                    $item['operator'] = null;
                    $item['oscillation_threshold'] = null;
                }
                return $item;
            }, $formData['userModelMetrics']);
            $this->form->fill($formData);
        }
    }

    // Hook to detect changes and run service method after save
    protected function afterSave(): void
    {
        $userModel = $this->getRecord();
        $userModelId = $userModel->id;

        // Check if threshold or userModelMetrics changed
        $currentMetrics = array_values($userModel->userModelMetrics->toArray()); // clean it

        $this->scoreUpdated = $this->originalRecord['threshold'] != $userModel->threshold ||
            $this->originalMetrics !== $currentMetrics;

        // Run service method if either changed
        if ($this->scoreUpdated) {
            $service = app(UserModelService::class);
            $service->updateDailyScores($userModelId);

            // Debug the dispatch data
            $dispatchData = [
                'chartId' => 'chart-daily-score',
                'options' => $this->getChartOptions($userModelId)
            ];

            // Dispatch a Filament event to refresh the chart
            $this->dispatch('refresh-chart', $dispatchData);
        }
    }


    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return \Filament\Notifications\Notification::make()
            ->title('Model Updated')
            ->body($this->scoreUpdated ? 'Scores were updated' : '')
            ->success();
    }
}
