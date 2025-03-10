<?php

namespace App\Filament\Resources\UserModelResource\Traits;

use App\Enum\Operators;
use App\Models\Metric;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Wizard\Step;
use Filament\Support\RawJs;

trait UserModelWizardSteps
{
    protected function getSteps(): array
    {
        return [
            Step::make('Info')
                ->description('Define your Model')
                ->schema($this->getInfoSchema())
                ->icon('heroicon-o-identification'),
            Step::make('Metrics')
                ->description("Manage your Model's Metrics")
                ->schema($this->getMetricsSchema())
                ->icon('heroicon-o-adjustments-horizontal'),
            Step::make('Threshold')
                ->description('Tune your Model')
                ->schema($this->getThresholdSchema($this->record->id ?? null))
                ->icon('heroicon-o-presentation-chart-bar'),
        ];
    }

    public function getInfoSchema(): array
    {
        return [
            Hidden::make('id'),
            TextInput::make('user_id')
                ->required()
                ->numeric()
                ->hidden(),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->placeholder('What is your Model looking for?')
                ->required()
                ->columnSpanFull(),
            TextInput::make('threshold')
                ->numeric()
                ->hidden(),
            TextInput::make('last_score')
                ->numeric()
                ->hidden(),
            TextInput::make('email_to_notify')
                ->label('Notify Email')
                ->hint('Alerts will be sent to this email when the threshold is hit')
                ->email()
                ->maxLength(255),
            TextInput::make('telegram_to_notify')
                ->tel()
                ->maxLength(255),
        ];
    }

    public function getMetricsSchema(): array
    {
        $metrics = Metric::with('dataSource')->get();

        return [
            Section::make()
                //->columns(4)
                ->schema([
                    // Repeatable field for invoice items
                    Repeater::make('userModelMetrics')
                        ->label('Model Metrics')
                        // Defined as a relationship to the InvoiceProduct model
                        ->relationship()
                        ->lazy()
                        ->defaultItems(1)
                        ->addAction(fn (Action $action) => $action->label('Add another Metric')) // Customize the label here
                        ->deleteAction(
                            fn (Action $action) => $action->requiresConfirmation(),
                        )
                        ->schema([
                            Select::make('metric_id')
                                ->label('Metric')
                                ->options(
                                    $metrics->mapWithKeys(function (Metric $metric) {
                                        return [$metric->id => sprintf('%s (%s)', $metric->name, $metric->dataSource->name)];
                                    })
                                )
                                ->columns(1)
                                ->required(),
                            Select::make('operator')
                                ->label('Operator')
                                ->columns(1)
                                ->options(
                                    Operators::class
                                ),
                            TextInput::make('oscillation_threshold')
                                ->mask(
                                    RawJs::make("parseFloat(\$input) > 100 ? '100' : (\$input[2] === '.' ? '99.99' : (\$input[1] === '.' ? '9.99' : '999'))")
                                )
                                ->numeric()
                                ->placeholder('%')
                                ->label('Oscillation')
                                ->columns(1)
                                ->default(1),
                            Radio::make('weight')
                                ->label('Weight')
                                ->inline()
                                ->columns(1)
                                ->options(
                                    [0, 1, 2, 3, 4, 5],
                                )
                                ->required(),
                        ])
                        // Disable reordering
                        ->reorderable(false)
                        ->columns(4)
                ]),
        ];
    }

    public function getThresholdSchema(int $userModelId = null): array
    {
        return [
            Toggle::make('is_paused')
                ->label('Monitoring Paused?')
                ->default(false)
                ->columns(1),
            TextInput::make('threshold')
                ->numeric()
                ->placeholder('%')
                ->label('Final Threshold')
                ->hint('Sum of each of this Model\'s Metrics will be compared against the threshold')
                ->columns(1)
                ->default(1)
                ->required(),
            Radio::make('buy_or_sell')
                ->label('Signal')
                ->columns(1)
                ->inline()
                ->default('sell')
                ->options(['buy' => 'Buy', 'sell' => 'Sell']),
            ViewField::make('chart')
                ->view('filament.components.user-model-chart')
                ->viewData(['options' => $this->getChartOptions($userModelId)])
                ->dehydrated(false),
        ];
    }
}
