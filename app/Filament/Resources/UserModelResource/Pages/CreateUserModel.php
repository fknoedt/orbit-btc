<?php

namespace App\Filament\Resources\UserModelResource\Pages;

use App\Filament\Charts\UserModelChart;
use App\Filament\Resources\UserModelResource;
use App\Filament\Resources\UserModelResource\Traits\UserModelWizardSteps;
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

    public $threshold;

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
                    ->cancelAction($this->getCancelFormAction())
                    ->submitAction($this->getSubmitFormAction())
                    ->skippable(false)
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
