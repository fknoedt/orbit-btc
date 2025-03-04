<?php

namespace App\Filament\Resources\UserModelResource\Traits;

use App\Filament\Resources\UserModelResource;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Set;

trait UserModelWizardSteps
{
    protected function getSteps(): array
    {
        return [
            Step::make('Info')
                ->description('Define your Model')
                ->schema(UserModelResource::getInfoSchema())
                ->icon('heroicon-o-identification')
                // save the record upon 'Save' button in the 1st step
                /*->afterValidation(function(Set $set) {
                    $data = $this->form->getState();
                    // UserModel was already created (back button)
                    if (! empty($data['id'])) {
                        $this->getModel()::find($data['id'])->update($data);
                    } else {
                        $data['user_id'] = auth()->id();
                        $this->record = new ($this->getModel())($data);
                        $this->record->save();
                    }

                    $set('id', $this->record->id);
                })*/,
            Step::make('Metrics')
                ->description("Manage your Model's Metrics")
                ->schema(UserModelResource::getMetricsSchema())
                ->icon('heroicon-o-adjustments-horizontal'),
            Step::make('Threshold')
                ->description('Tune your Model')
                ->schema(UserModelResource::getThresholdSchema())
                ->icon('heroicon-o-presentation-chart-bar'),
        ];
    }
}
