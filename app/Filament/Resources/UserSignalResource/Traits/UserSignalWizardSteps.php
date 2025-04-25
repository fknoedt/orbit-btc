<?php

namespace App\Filament\Resources\UserSignalResource\Traits;

use App\Enum\Operators;
use App\Enum\TimeHorizon;
use App\Models\Frequency;
use App\Models\Metric;
use App\Services\UserSignalService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Wizard\Step;
use Filament\Support\RawJs;

trait UserSignalWizardSteps
{
    protected function getSteps(): array
    {
        $operation = $this->getCurrentOperation();

        return [
            Step::make('Help')
                ->description("I'm lost")
                ->schema([View::make('help.user-signal')])
                ->icon('heroicon-o-lifebuoy')
                ->completedIcon('heroicon-o-lifebuoy'),
            Step::make('Info')
                ->description('Define your Signal')
                ->schema($this->getInfoSchema())
                ->icon('heroicon-o-identification')
                ->completedIcon('heroicon-o-identification'),
            Step::make('Metrics')
                ->description("Manage your Signal's Metrics")
                ->schema($this->getMetricsSchema($operation))
                ->icon('heroicon-o-adjustments-horizontal')
                ->completedIcon('heroicon-o-adjustments-horizontal'),
            Step::make('Tuning')
                ->description('Tune your Signal')
                ->schema($this->getTuningSchema($operation, $this->record->id ?? null))
                ->icon('heroicon-o-presentation-chart-bar')
                ->completedIcon('heroicon-o-presentation-chart-bar'),
        ];
    }

    protected function getCurrentOperation(): ?string
    {
        // on create form is submitted, the route is livewire.update so we need to look at the previous route
        $routes = [
            request()->route()->getName()
        ];

        if ($this->previousUrl ?? null) {
            $routes[] = $this->previousUrl;
        }

        foreach ($routes as $route) {
            if (str_ends_with($route, 'edit')) {
                return 'edit';
            } elseif (str_ends_with($route, 'view')) {
                return 'view';
            } elseif (str_ends_with($route, 'create')) {
                return 'create';
            }
        }

        return null; // Fallback for unexpected routes
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
                ->placeholder('What is your Signal looking into?')
                ->required()
                ->columnSpanFull(),
            TextInput::make('last_score')
                ->numeric()
                ->hidden(),
            TextInput::make('email_to_notify')
                ->label('Notify Email')
                ->hint('Alerts will be sent to this email when the threshold is hit - currently disabled')
                ->hintIcon('heroicon-o-no-symbol')
                ->email()
                ->maxLength(255)
                ->disabled(),
            TextInput::make('telegram_to_notify')
                ->tel()
                ->maxLength(255)
                ->hint('currently disabled')
                ->hintIcon('heroicon-o-no-symbol')
                ->disabled(),
        ];
    }

    public function getMetricsSchema(string $operation = null): array
    {
        $metrics = Metric::with('dataSource')->get();
        $frequencies = Frequency::orderBy('number_of_days')->get();

        $extraActions = $operation === 'view' ? [] :
            [
                Action::make('toggleOscillation')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->label(fn ($get) => $get('oscillation_threshold_enabled') ? 'Hide Oscillation' : 'Show Oscillation')
                    ->tooltip('Toggle Change Threshold')
                    ->action(function ($set, $get, $arguments) {
                        $itemKey = $arguments['item'] ?? 'no key';
                        $statePath = "userSignalMetrics.{$itemKey}";
                        $enabledPath = "{$statePath}.oscillation_threshold_enabled";
                        $currentState = $get($enabledPath) ?? false;

                        $newState = !$currentState;
                        $set($enabledPath, $newState);

                        if (!$newState) {
                            $set("{$statePath}.oscillation_threshold", null);
                        }

                        $this->dispatch('refresh');
                    })
                    ->color('secondary'),
            ];

        return [
            Section::make()
                ->schema([
                    Repeater::make('userSignalMetrics')
                        ->label('Metrics')
                        ->hint('Daily Score will be the sum of each metric\'s frequency variation x weight with optional threshold')
                        ->relationship()
                        ->lazy()
                        ->defaultItems(1)
                        ->addAction(fn (Action $action) => $action->label('Add another Metric'))
                        ->deleteAction(
                            fn (Action $action) => $action->requiresConfirmation(),
                        )
                        ->extraItemActions($extraActions)
                        ->schema([
                            Select::make('frequency_id')
                                ->label('Frequency')
                                ->options(
                                    $frequencies->mapWithKeys(function ($frequency) {
                                        return [$frequency->id => $frequency->name];
                                    })->toArray()
                                )
                                ->default(1)
                                ->columns(1)
                                ->required(),
                            Select::make('metric_id')
                                ->label('Metric')
                                ->hint('% change')
                                ->options(
                                    $metrics->mapWithKeys(function (Metric $metric) {
                                        return [$metric->id => sprintf('%s (%s)', $metric->name, $metric->dataSource->name)];
                                    })
                                )
                                ->columns(1)
                                ->required(),
                            ViewField::make('operator')
                                ->label('Direction')
                                ->view('forms.components.custom-operator-icons')
                                ->viewData([
                                    'operators' => Operators::cases(),
                                ])
                                ->required()
                                ->columns(1)
                                ->live() // Ensure real-time updates
                                ->dehydrated(true), // Ensure the value is saved
                            TextInput::make('weight')
                                ->label('Weight')
                                ->columns(1)
                                ->maxValue(10)
                                ->minValue(0)
                                ->numeric()
                                ->default('1')
                                ->hint("1 - 10")
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get, $component) {
                                    $value = (int)$state;
                                    if ($value > 10) {
                                        $set('weight', 10);
                                    } elseif ($value < 0) {
                                        $set('weight', 0);
                                    }
                                }),

                            // Threshold removed for simplicity sake. If necessary, just re-add it
                            /*TextInput::make('oscillation_threshold')
                                ->mask(
                                    RawJs::make("parseFloat(\$input) > 100 ? '100' : (\$input[2] === '.' ? '99.99' : (\$input[1] === '.' ? '9.99' : '999'))")
                                )
                                ->numeric()
                                ->placeholder('%')
                                ->label('Threshold')
                                ->hint('in %')
                                ->columns(1)
                                ->visible(fn ($get) => $get('oscillation_threshold_enabled') ?? false)
                                ->required(fn ($get) => $get('oscillation_threshold_enabled') ?? false),
                            ViewField::make('no_threshold_message')
                                ->view('forms.components.no-threshold-message')
                                ->columns(1)
                                ->visible(function ($get) use ($operation) {
                                    return ($operation ?? 'edit') !== 'view' && !($get('oscillation_threshold_enabled') ?? false);
                                }),
                            Hidden::make('oscillation_threshold_enabled')
                                ->default(false)
                                ->live()
                                ->dehydrated(true), // Ensure it’s always included in the form state
                            */
                        ])
                        ->reorderable(false)
                        ->columns(4) // increase to 5 when re-adding threshold
                ]),
        ];
    }

    public function getTuningSchema(string $operation = null, int $userSignalId = null): array
    {
        $service = app(UserSignalService::class);
        $maxThreshold = $userSignalId ? $service->getMaxThreshold($userSignalId) : 100;

        $schema = [
            Toggle::make('is_paused')
                ->label('Paused?')
                ->default(false)
                ->columns(1),
            View::make('components.range-slider')
                ->viewData([
                    'name' => 'threshold',
                    'min' => 0,
                    'max' => $maxThreshold,
                    'step' => 0.1,
                    'value' => $this->record->threshold ?? 0,
                    'label' => "Threshold (0-{$maxThreshold}): ",
                    'disabled' => ($operation === 'view'),
                    'hint' => 'Maximum threshold is the weight of each Metric x ' .
                        UserSignalService::MAX_OSCILLATION_PER_METRIC . ' (fixed daily change percentage)'
                ]),
            Radio::make('buy_or_sell')
                ->label('Signal')
                ->default('sell')
                ->inline()
                ->options(['buy' => 'Buy', 'sell' => 'Sell'])
                ->helperText('On each day, a buy or sell operation will be simulated depending on the threshold (none if not met)'),
            Radio::make('time_horizon')
                ->label('Time Horizon')
                ->default('1')
                ->inline()
                ->options(TimeHorizon::class)
                ->helperText('Price N days ahead when considering if the buy or sell operation had a positive outcome or not'),
        ];

        if ($operation !== 'create') {
            $schema[] = View::make('filament.components.user-signal-chart')
                ->viewData([
                    'label' => 'Daily Signal',
                    'name' => 'daily-score',
                    'hint' => $operation === 'edit' ? 'Save your Signal to see the updated chart' : '',
                    'options' => isset($this->record->id) ? $this->getChartOptions($this->record->id) : [],
                    'rawExtraJsOptions' => $this->getExtraJsOptions(),
                ])
                ->extraAttributes(['style' => 'min-width: 100%; min-height: 400px;']);
        }

        return $schema;
    }
}
