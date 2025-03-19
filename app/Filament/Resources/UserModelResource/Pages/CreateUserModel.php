<?php

namespace App\Filament\Resources\UserModelResource\Pages;

use App\Filament\Charts\UserModelChart;
use App\Filament\Resources\UserModelResource;
use App\Filament\Resources\UserModelResource\Traits\UserModelWizardSteps;
use App\Services\UserModelService;
use Filament\Actions\Concerns\HasWizard;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class CreateUserModel extends CreateRecord
{
    use HasWizard, UserModelWizardSteps, UserModelChart;

    protected static string $resource = UserModelResource::class;

    protected static bool $canCreateAnother = false;

    public $threshold = 0; // Default value

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->threshold = $data['threshold'] ?? 0;

        // Initialize userModelMetrics for new record
        if (isset($data['userModelMetrics'])) {
            $data['userModelMetrics'] = array_map(function ($item) {
                $item['oscillation_threshold_enabled'] = !empty($item['oscillation_threshold']);
                return $item;
            }, $data['userModelMetrics']);
            $this->form->fill($data);
        }

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure threshold is pulled from the form state if available
        $formData = $this->form->getState();
        $data['threshold'] = $formData['threshold'] ?? $this->threshold;
        $data['user_id'] = auth()->id();

        return $data;
    }

    public function form(Form $form): Form
    {
        return parent::form($form)
            ->schema([
                Wizard::make($this->getSteps())
                    ->cancelAction($this->getCancelFormAction())
                    ->submitAction($this->getSubmitFormAction())
                    ->skippable(false)
                    ->startOnStep(2)
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
     * Hook to calculate UserModelDailyScore upon creation
     */
    protected function afterCreate(): void
    {
        $userModel = $this->getRecord();
        if ($userModel->userModelMetrics->count()) {
            $service = app(UserModelService::class);
            $service->updateDailyScores($userModel->id);
            // Debug the dispatch data
            $dispatchData = [
                'chartId' => 'chart-daily-score',
                'options' => $this->getChartOptions($userModel->id)
            ];

            // Dispatch a Filament event to refresh the chart
            $this->dispatch('refresh-chart', $dispatchData);
        }
    }
}
