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
use Filament\Resources\Pages\ViewRecord;

class ViewUserModel extends ViewRecord
{
    use HasWizard, UserModelWizardSteps, UserModelChart;

    protected static string $resource = UserModelResource::class;

    public $threshold;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->threshold = $data['threshold'] ?? 0;
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
                    ->columnSpanFull()
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
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $userModel = $this->getRecord();
        $userModelId = $userModel->id;

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
