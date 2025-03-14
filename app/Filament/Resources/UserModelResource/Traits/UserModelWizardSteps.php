<?php

namespace App\Filament\Resources\UserModelResource\Traits;

use App\Enum\Operators;
use App\Models\Metric;
use App\Services\UserModelService;
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

trait UserModelWizardSteps
{

    protected function getSteps(): array
    {
        return [
            Step::make('Info')
                ->description('Define your Model')
                ->schema($this->getInfoSchema())
                ->icon('heroicon-o-identification')
                ->completedIcon('heroicon-o-identification'),
            Step::make('Metrics')
                ->description("Manage your Model's Metrics")
                ->schema($this->getMetricsSchema())
                ->icon('heroicon-o-adjustments-horizontal')
                ->completedIcon('heroicon-o-adjustments-horizontal'),
            Step::make('Tuning')
                ->description('Tune your Model')
                ->schema($this->getTuningSchema($this->record->id ?? null))
                ->icon('heroicon-o-presentation-chart-bar')
                ->completedIcon('heroicon-o-presentation-chart-bar'),
        ];
    }

    /**
     * $this->form not built yet at this point, so...
     */
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
                ->placeholder('What is your Model looking for?')
                ->required()
                ->columnSpanFull(),
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

    public function getTuningSchema(int $userModelId = null): array
    {
        $operation = $this->getCurrentOperation();
        $service = app(UserModelService::class);
        $maxThreshold = $service->getMaxThreshold($userModelId);

        $schema = [
            Toggle::make('is_paused')
                ->label('Monitoring Paused?')
                ->default(false)
                ->columns(1),
            Radio::make('buy_or_sell')
                ->label('Signal')
                ->default('sell')
                ->inline()
                ->options(['buy' => 'Buy', 'sell' => 'Sell'])
                ->extraAttributes(['class' => 'flex items-center space-x-2']),
            View::make('components.range-slider')
                ->viewData([
                    'name' => 'threshold',
                    'min' => 0,
                    'max' => $maxThreshold,
                    'step' => 1,
                    'value' => $this->record->threshold ?? 0,
                    'label' => "Threshold (0-{$maxThreshold}): ",
                    'disabled' => ($operation === 'view'),
                    'hint' => 'Max. threshold is related to the weight of each metric x ' .
                        UserModelService::MAX_OSCILLATION_PER_METRIC . '% of oscillation'
                ]),
        ];

        if ($operation !== 'create') {
            $schema[] = ViewField::make('daily-score')
                ->label('Daily Score')
                ->hint($operation === 'edit' ? 'Save your Model to see the updated chart' : '')
                ->view('filament.components.user-model-chart')
                ->viewData(['options' => $this->getChartOptions($userModelId)])
                ->dehydrated(false);
        }

        return $schema;
    }
}
