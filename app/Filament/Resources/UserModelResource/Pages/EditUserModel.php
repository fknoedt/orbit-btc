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

    // Hook to capture original data before save
    protected function beforeSave(): void
    {
        $this->originalRecord = $this->getRecord()->toArray();
        $this->originalMetrics = $this->getRecord()->userModelMetrics->toArray(); // Original related metrics
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
