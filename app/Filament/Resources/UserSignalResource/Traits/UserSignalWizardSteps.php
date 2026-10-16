<?php

namespace App\Filament\Resources\UserSignalResource\Traits;

use App\Enum\Operators;
use App\Enum\TimeHorizon;
use App\Filament\Resources\UserSignalResource\Pages\ViewUserSignal;
use App\Models\Frequency;
use App\Models\LevelingMove;
use App\Models\Metric;
use App\Services\MetricWeightSuggester;
use App\Services\UserSignalService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

trait UserSignalWizardSteps
{
    private const string METRICS_HINT = 'This is your Signal\'s formula, the most important part of it. Each day, Orbit takes each metric\'s percent change over the days you set. That percent is the score. Multiply when you want to level the metrics, or to raise the importance of the ones you want. The total percent variation of every metric is your daily signal. It triggers when the total reaches the threshold. This is your own Bitcoin signal.';

    private const string LOOK_BACK_TIP = 'How many days back. Orbit uses the percent change over that window. A longer window is usually a bigger percent.';

    private const string MULTIPLIER_TIP = 'The percent change over the days you set is multiplied by this. 1 leaves the score equal to the percent. 0.5 halves it, 2 doubles it, and so on.';

    /** @var array<string, array{weight: float, metric_id: int, frequency_id: int, applied: float}> */
    public array $multiplierBeforeHint = [];

    /** Note inside the yellow composite box when Level the metrics is clicked before two metrics are chosen. The next click that levels clears it. */
    public ?string $levelMetricsNotice = null;

    /** @var Collection<string, LevelingMove>|null */
    private ?Collection $levelingMovesThisRequest = null;

    protected function getSteps(): array
    {
        $operation = $this->getCurrentOperation();

        return [
            Step::make('Help')
                ->description('How signals work')
                ->schema([View::make('help.user-signal')])
                ->icon('heroicon-o-lifebuoy')
                ->completedIcon('heroicon-o-lifebuoy'),
            Step::make('Info')
                ->description('Name, email, pause')
                ->schema($this->getInfoSchema())
                ->icon('heroicon-o-identification')
                ->completedIcon('heroicon-o-identification'),
            Step::make('Metrics')
                ->description('What each metric adds')
                ->schema($this->getMetricsSchema())
                ->icon('heroicon-o-adjustments-horizontal')
                ->completedIcon('heroicon-o-adjustments-horizontal'),
            Step::make('Tuning')
                ->description('When a trade starts')
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
            Toggle::make('email_notification')
                ->label('Email when it fires')
                ->default(false),
            ViewField::make('last_notification_at')
                ->label('Last email')
                ->view('filament.components.read-only-timestamp')
                ->dehydrated(false),
            Toggle::make('is_paused')
                ->label('Pause scoring')
                ->default(false),
        ];
    }

    public function getMetricsSchema(): array
    {
        $metrics = Metric::withTrashed()->with('dataSource')->orderBy('name')->get();
        $frequencies = Frequency::orderBy('number_of_days')->get();
        $levelingMoves = $this->levelingMoveIndex();
        $readOnly = $this instanceof ViewUserSignal;

        $repeater = Repeater::make('userSignalMetrics')
            ->label('')
            ->hint(self::METRICS_HINT)
            ->extraFieldWrapperAttributes(['class' => 'metric-formula'])
            ->extraAttributes(['class' => 'metric-rules'])
            ->view('forms.components.metric-repeater')
            ->viewData(['canLevelMetrics' => ! $readOnly])
            ->relationship()
            ->lazy()
            ->reorderable(false);

        if ($readOnly) {
            return [
                View::make('forms.components.metric-step-styles'),
                $repeater
                    ->addable(false)
                    ->deletable(false)
                    ->schema([
                        Placeholder::make('readout')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->content(function (Get $get, Placeholder $component) use ($metrics, $frequencies, $levelingMoves) {
                                $rows = $this->metricRows($get);
                                $weight = is_numeric($get('weight')) ? (float) $get('weight') : 1.0;
                                $several = count($rows) > 1;

                                return view('forms.components.metric-readout', [
                                    ...$this->metricChoice(
                                        $get('metric_id'),
                                        $get('frequency_id'),
                                        $get('operator'),
                                        $get('weight'),
                                        $metrics,
                                        $frequencies,
                                    ),
                                    'lookBackTip' => self::LOOK_BACK_TIP,
                                    'multiplierTip' => self::MULTIPLIER_TIP,
                                    'multiplierHint' => $several
                                        ? $this->multiplierHint($rows, $this->rowKeyFromPath($component->getStatePath()), $levelingMoves)
                                        : null,
                                    'lockedNote' => $several ? null : $this->singleMetricNote($weight),
                                ]);
                            }),
                    ]),
            ];
        }

        return [
            View::make('forms.components.metric-step-styles'),
            $repeater
                ->defaultItems(1)
                ->addAction(fn (Action $action) => $action
                    ->label('Add a metric')
                    ->size(ActionSize::Medium))
                ->deleteAction(fn (Action $action) => $action
                    ->requiresConfirmation()
                    ->size(ActionSize::ExtraLarge)
                    ->after(function (Repeater $component) {
                        $this->lockLoneMetricWeight($component, $component->getState());
                    }))
                ->mutateRelationshipDataBeforeFillUsing(function (array $data, Repeater $component): array {
                    if ($component->getCachedExistingRecords()->count() === 1) {
                        $data['weight'] = 1;
                    }

                    return $data;
                })
                ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => $this->oneMetricWeight($data))
                ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => $this->oneMetricWeight($data))
                ->columns([
                    'default' => 1,
                    'lg' => 4,
                ])
                ->schema([
                    Select::make('metric_id')
                        ->label('When the metric')
                        ->live()
                        ->options(
                            $metrics->mapWithKeys(function (Metric $metric) {
                                return [$metric->id => sprintf('%s (%s)', $metric->name, $metric->dataSource->name)];
                            })
                        )
                        ->suffixAction(
                            Action::make('metricChart')
                                ->label('Chart')
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->iconButton()
                                ->size(ActionSize::Small)
                                ->tooltip('Open this metric\'s chart')
                                ->url(fn (Get $get): ?string => $this->metricChartUrl($get('metric_id')))
                                ->openUrlInNewTab()
                                ->visible(fn (Get $get): bool => filled($get('metric_id'))),
                            isInline: true,
                        )
                        ->afterStateUpdated(function (Select $component) {
                            unset($this->multiplierBeforeHint[$this->rowKeyFromPath($component->getStatePath())]);
                        })
                        ->markAsRequired(false)
                        ->required(),
                    ViewField::make('operator')
                        ->label('Moves (percent)')
                        ->extraFieldWrapperAttributes(['class' => 'metric-moves'])
                        ->view('forms.components.custom-operator-icons')
                        ->viewData([
                            'operators' => Operators::cases(),
                        ])
                        ->default(Operators::PLUS_MINUS->value)
                        ->markAsRequired(false)
                        ->required()
                        ->live()
                        ->dehydrated(true),
                    Select::make('frequency_id')
                        ->label($this->labelWithTip('In the last', self::LOOK_BACK_TIP))
                        ->markAsRequired(false)
                        ->live()
                        ->options(
                            $frequencies->mapWithKeys(function ($frequency) {
                                $days = (int) $frequency->number_of_days;

                                return [$frequency->id => $days === 1 ? '1 day' : "{$days} days"];
                            })->toArray()
                        )
                        ->default(1)
                        ->required()
                        ->afterStateUpdated(function (Select $component) {
                            unset($this->multiplierBeforeHint[$this->rowKeyFromPath($component->getStatePath())]);
                        }),
                    TextInput::make('weight')
                        ->label($this->labelWithTip('Multiply movement by', self::MULTIPLIER_TIP))
                        ->extraFieldWrapperAttributes(['class' => 'metric-multiplier'])
                        ->markAsRequired(false)
                        ->numeric()
                        ->step(0.1)
                        ->minValue(MetricWeightSuggester::MIN_WEIGHT)
                        ->maxValue(MetricWeightSuggester::MAX_WEIGHT)
                        ->default(1)
                        ->required()
                        ->live()
                        ->hidden(fn (Get $get): bool => count($this->metricRows($get)) < 2)
                        ->dehydratedWhenHidden()
                        ->helperText(function (Get $get, TextInput $component) use ($levelingMoves): HtmlString|string|null {
                            $rows = $this->metricRows($get);
                            if (count($rows) < 2) {
                                return null;
                            }

                            return $this->multiplierHint($rows, $this->rowKeyFromPath($component->getStatePath()), $levelingMoves, clickable: true);
                        })
                        ->afterStateUpdated(function ($state, $set, TextInput $component) {
                            $key = $this->rowKeyFromPath($component->getStatePath());
                            if (! is_numeric($state)) {
                                $this->forgetMultiplierUndo($key, null);

                                return;
                            }
                            $value = (new MetricWeightSuggester)->clamp((float) $state);
                            if (abs((float) $state - $value) >= 0.001) {
                                $set('weight', $value);
                            }
                            $this->forgetMultiplierUndo($key, $value);
                        }),
                    Placeholder::make('weight_label')
                        ->hiddenLabel()
                        ->dehydrated(false)
                        ->extraAttributes(['class' => 'metric-single-slot'])
                        ->hidden(fn (Get $get): bool => count($this->metricRows($get)) >= 2)
                        ->content(fn (): HtmlString => $this->singleMetricLabel(1)),
                ]),
        ];
    }

    public function getTuningSchema(string $operation = null, int $userSignalId = null): array
    {
        $service = app(UserSignalService::class);
        $maxThreshold = $userSignalId ? $service->getMaxThreshold($userSignalId) : 100;
        $thresholdHint = 'The day fires when the score reaches this. Each metric adds its percent change times its multiplier. The slider runs up to a 100% move on every metric (0–'
            . $maxThreshold
            . ').';

        $schema = [
            View::make('components.range-slider')
                ->viewData([
                    'name' => 'threshold',
                    'min' => 0,
                    'max' => $maxThreshold,
                    'step' => 0.1,
                    'value' => $this->record->threshold ?? 0,
                    'label' => 'Threshold',
                    'disabled' => ($operation === 'view'),
                    'hint' => $thresholdHint,
                ]),
            Radio::make('buy_or_sell')
                ->label('Trade')
                ->default('sell')
                ->inline()
                ->options(['buy' => 'Buy', 'sell' => 'Sell'])
                ->helperText('Simulates a ' . Number::currency(UserSignalService::tradeSizeInUsd()) . ' trade. A score under the threshold does nothing.'),
            Radio::make('time_horizon')
                ->label('Hold for')
                ->default('1')
                ->inline()
                ->options(TimeHorizon::class)
                ->helperText('How long that paper trade stays open. The next one waits until it closes.'),
        ];

        if ($operation !== 'create') {
            $schema[] = View::make('filament.components.user-signal-chart')
                ->viewData([
                    'label' => '',
                    'name' => 'daily-score',
                    'hint' => $operation === 'edit' ? 'Save your Signal to see the updated chart' : '',
                    'options' => isset($this->record->id) ? $this->getChartOptions($this->record->id) : [],
                    'rawExtraJsOptions' => $this->getExtraJsOptions(),
                ])
                ->extraAttributes(['style' => 'min-width: 100%; min-height: 400px;']);
        }

        return $schema;
    }

    /**
     * Name, direction, window, and chart link for one metric on the view page.
     *
     * @param  Collection<int, Metric>  $metrics
     * @param  Collection<int, Frequency>  $frequencies
     * @return array{name: string, source: ?string, span: string, directionWord: string, directionIcon: string, directionClass: string, multiplierText: string, chartUrl: ?string}
     */
    private function metricChoice(
        mixed $metricId,
        mixed $frequencyId,
        mixed $operator,
        mixed $weight,
        Collection $metrics,
        Collection $frequencies,
    ): array {
        $metric = $metrics->firstWhere('id', (int) $metricId);
        $frequency = $frequencies->firstWhere('id', (int) $frequencyId);
        $days = (int) ($frequency->number_of_days ?? 1);
        $span = $days === 1 ? '1 day' : "{$days} days";
        [$directionWord, $directionIcon, $directionClass] = match ($operator) {
            Operators::PLUS->value => ['Up', 'arrow-up', 'is-up'],
            Operators::MINUS->value => ['Down', 'arrow-down', 'is-down'],
            default => ['Both', 'arrows-up-down', 'is-both'],
        };
        $multiplier = is_numeric($weight) ? (float) $weight : 1.0;

        return [
            'name' => $metric->name ?? 'Pick a metric',
            'source' => $metric?->dataSource?->name,
            'span' => $span,
            'directionWord' => $directionWord,
            'directionIcon' => $directionIcon,
            'directionClass' => $directionClass,
            'multiplierText' => (new MetricWeightSuggester)->formatNumber($multiplier),
            'chartUrl' => $this->metricChartUrl($metric?->id),
        ];
    }

    /**
     * The metric rows on this signal, so one row can be compared with the others.
     *
     * @return array<string, mixed>
     */
    private function metricRows(Get $get): array
    {
        $rows = $get('../');
        if (! is_array($rows)) {
            $rows = $this->data['userSignalMetrics'] ?? [];
        }

        return is_array($rows) ? $rows : [];
    }

    /** The repeater key of the field that just changed. */
    private function rowKeyFromPath(?string $statePath): string
    {
        if ($statePath === null || ! str_contains($statePath, 'userSignalMetrics.')) {
            return '';
        }

        return (string) str($statePath)->after('userSignalMetrics.')->beforeLast('.');
    }

    /**
     * The Try or Undo line under a multiplier. Edit gets a button. View gets plain text.
     *
     * @param  array<string, mixed>  $rows
     * @param  Collection<string, LevelingMove>  $levelingMoves
     */
    private function multiplierHint(array $rows, string $key, Collection $levelingMoves, bool $clickable = false): HtmlString|string|null
    {
        $plan = $this->multiplierPlan($rows, $key, $levelingMoves);
        if ($plan === null) {
            return null;
        }

        $safeKey = $this->safeRowKey($key);
        if ($clickable && $safeKey !== null) {
            $undo = $this->undoWeight($safeKey, $rows);
            $applied = (float) ($this->multiplierBeforeHint[$safeKey]['applied'] ?? 0);
            $suggestionStillApplied = $undo !== null
                && isset($plan['suggested'])
                && abs((float) $plan['suggested'] - $applied) < 0.001;
            if ($suggestionStillApplied) {
                $number = (new MetricWeightSuggester)->formatNumber($undo);

                return $this->hintButton('undoMultiplierHint', $safeKey, 'Undo: ' . $number);
            }

            if ($plan['click'] && $plan['text'] !== '') {
                return $this->hintButton('applyMultiplierHint', $safeKey, $plan['text']);
            }
        }

        return $plan['text'] === '' ? null : $plan['text'];
    }

    /**
     * What to suggest for one row. Nothing when that row is the one the others level against, or when there is no number to offer.
     * A row that already levels still returns its suggested number, so Undo can stay visible.
     *
     * @param  array<string, mixed>  $rows
     * @param  Collection<string, LevelingMove>  $levelingMoves
     * @return array{text: string, click: bool, suggested?: float}|null
     */
    private function multiplierPlan(array $rows, string $key, Collection $levelingMoves): ?array
    {
        if (count($rows) < 2) {
            return null;
        }

        $row = $rows[$key] ?? null;
        if (! is_array($row) || empty($row['metric_id']) || empty($row['frequency_id'])) {
            return null;
        }

        $input = $this->weightInputs($rows, $levelingMoves);
        $mine = $input[$key] ?? null;
        if (! is_array($mine) || $mine['percent'] === null) {
            return null;
        }

        $suggester = new MetricWeightSuggester;
        $desired = $suggester->desiredWeights($input);
        if ($desired === [] || ! isset($desired[$key])) {
            return null;
        }

        $loudestKey = null;
        $loudestPercent = -1.0;
        foreach ($input as $rowKey => $candidate) {
            $percent = $candidate['percent'];
            if ($percent === null || $percent <= $loudestPercent) {
                continue;
            }
            $loudestPercent = $percent;
            $loudestKey = $rowKey;
        }

        if ((string) $key === (string) $loudestKey) {
            return null;
        }

        $suggested = (float) $desired[$key];
        $current = (float) $mine['weight'];
        $matches = abs(round($current, 1) - round($suggested, 1)) < 0.001;
        $base = $loudestKey !== null ? (float) $input[$loudestKey]['weight'] : 1.0;
        $text = $suggester->suggestionLine(
            $suggested,
            $matches,
            $suggester->rawWeight($base, $loudestPercent, (float) $mine['percent']) > MetricWeightSuggester::MAX_WEIGHT,
        );

        return [
            'text' => $text ?? '',
            'click' => ! $matches && $text !== null,
            'suggested' => $suggested,
        ];
    }

    /**
     * Each row's multiplier and the stored percent used to level it.
     *
     * @param  array<string, mixed>  $rows
     * @param  Collection<string, LevelingMove>  $levelingMoves
     * @return array<string, array{percent: ?float, weight: float, metric_id: int}>
     */
    private function weightInputs(array $rows, Collection $levelingMoves): array
    {
        $input = [];
        foreach ($rows as $rowKey => $candidate) {
            if (! is_array($candidate) || empty($candidate['metric_id']) || empty($candidate['frequency_id'])) {
                continue;
            }
            $move = $levelingMoves->get(((int) $candidate['metric_id']) . '-' . ((int) $candidate['frequency_id']));
            $input[$rowKey] = [
                'percent' => ($move && $move->abs_percent > 0) ? (float) $move->abs_percent : null,
                'weight' => is_numeric($candidate['weight'] ?? null) ? (float) $candidate['weight'] : 1.0,
                'metric_id' => (int) $candidate['metric_id'],
            ];
        }

        return $input;
    }

    /** Writes the suggested multiplier and remembers the previous number for Undo. */
    public function applyMultiplierHint(string $key): void
    {
        if ($this instanceof ViewUserSignal || $this->safeRowKey($key) === null) {
            return;
        }

        $rows = $this->data['userSignalMetrics'] ?? [];
        if (! is_array($rows) || ! isset($rows[$key]) || ! is_array($rows[$key])) {
            return;
        }

        $plan = $this->multiplierPlan($rows, $key, $this->levelingMoveIndex());
        if ($plan === null || ! $plan['click'] || ! isset($plan['suggested'])) {
            return;
        }

        $suggested = (float) $plan['suggested'];
        $current = is_numeric($rows[$key]['weight'] ?? null) ? (float) $rows[$key]['weight'] : 1.0;
        $this->multiplierBeforeHint[$key] = [
            'weight' => $current,
            'metric_id' => (int) $rows[$key]['metric_id'],
            'frequency_id' => (int) $rows[$key]['frequency_id'],
            'applied' => $suggested,
        ];
        $rows[$key]['weight'] = $suggested;
        $this->data['userSignalMetrics'] = $rows;
    }

    /**
     * Applies each row's leveling hint. The row the others level against stays as it is.
     * An empty extra row is not a second metric, so that click only leaves a short note.
     */
    public function levelMetrics(): void
    {
        if ($this instanceof ViewUserSignal) {
            return;
        }

        $rows = $this->data['userSignalMetrics'] ?? [];
        if (! is_array($rows) || count($rows) < 2) {
            return;
        }

        if ($this->chosenMetricCount($rows) < 2) {
            $this->levelMetricsNotice = 'More than one metric has to be chosen.';

            return;
        }

        $this->levelMetricsNotice = null;

        foreach (array_keys($rows) as $key) {
            $this->applyMultiplierHint((string) $key);
        }
    }

    /**
     * How many rows have a metric selected. An empty "Select an option" row does not count.
     *
     * @param  array<string, mixed>  $rows
     */
    private function chosenMetricCount(array $rows): int
    {
        return count(array_filter(
            $rows,
            fn (mixed $row): bool => is_array($row) && filled($row['metric_id'] ?? null),
        ));
    }

    /** Puts back the multiplier from before the hint, when the metric and the window are still the same. */
    public function undoMultiplierHint(string $key): void
    {
        if ($this instanceof ViewUserSignal || $this->safeRowKey($key) === null) {
            return;
        }

        $saved = $this->multiplierBeforeHint[$key] ?? null;
        unset($this->multiplierBeforeHint[$key]);
        if (! is_array($saved)) {
            return;
        }

        $rows = $this->data['userSignalMetrics'] ?? [];
        if (! is_array($rows) || ! isset($rows[$key]) || ! is_array($rows[$key])) {
            return;
        }

        $row = $rows[$key];
        if ((int) ($row['metric_id'] ?? 0) !== (int) $saved['metric_id']
            || (int) ($row['frequency_id'] ?? 0) !== (int) $saved['frequency_id']) {
            return;
        }

        $rows[$key]['weight'] = $saved['weight'];
        $this->data['userSignalMetrics'] = $rows;
    }

    /**
     * The stored typical percent for every metric and day window.
     * Loaded once per request. Level the metrics used to read the whole table again for each row.
     *
     * @return Collection<string, LevelingMove>
     */
    private function levelingMoveIndex(): Collection
    {
        if ($this->levelingMovesThisRequest !== null) {
            return $this->levelingMovesThisRequest;
        }

        if (! Schema::hasTable('leveling_moves')) {
            return $this->levelingMovesThisRequest = collect();
        }

        return $this->levelingMovesThisRequest = LevelingMove::query()
            ->get(['metric_id', 'frequency_id', 'abs_percent'])
            ->keyBy(fn (LevelingMove $move) => $move->metric_id . '-' . $move->frequency_id);
    }

    /** The multiplier to restore, when this row still shows the number the hint wrote. */
    private function undoWeight(string $key, array $rows): ?float
    {
        $saved = $this->multiplierBeforeHint[$key] ?? null;
        if (! is_array($saved) || ! isset($rows[$key]) || ! is_array($rows[$key])) {
            return null;
        }

        $row = $rows[$key];
        if ((int) ($row['metric_id'] ?? 0) !== (int) $saved['metric_id']
            || (int) ($row['frequency_id'] ?? 0) !== (int) $saved['frequency_id']) {
            return null;
        }

        $current = is_numeric($row['weight'] ?? null) ? (float) $row['weight'] : 1.0;
        if (abs($current - (float) $saved['applied']) >= 0.001) {
            return null;
        }

        return (float) $saved['weight'];
    }

    /** Drops Undo once the typed multiplier is no longer the number the hint wrote. */
    private function forgetMultiplierUndo(string $key, ?float $weight): void
    {
        $saved = $this->multiplierBeforeHint[$key] ?? null;
        if (! is_array($saved)) {
            return;
        }

        if ($weight === null || abs($weight - (float) $saved['applied']) >= 0.001) {
            unset($this->multiplierBeforeHint[$key]);
        }
    }

    /**
     * After a delete leaves one metric, that multiplier goes back to 1.
     * The leveling note is cleared too, since the yellow box goes away.
     *
     * @param  array<string, mixed>|null  $state
     */
    private function lockLoneMetricWeight(Repeater $repeater, mixed $state): void
    {
        if (! is_array($state) || count($state) !== 1) {
            return;
        }

        $this->levelMetricsNotice = null;

        $key = array_key_first($state);
        if (! is_array($state[$key] ?? null)) {
            return;
        }

        unset($this->multiplierBeforeHint[(string) $key]);
        $weight = $state[$key]['weight'] ?? null;
        if (is_numeric($weight) && abs((float) $weight - 1) < 0.001) {
            return;
        }

        $state[$key]['weight'] = 1;
        $repeater->state($state);
    }

    /**
     * On save, a signal with one metric stores a multiplier of 1.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function oneMetricWeight(array $data): array
    {
        $rows = $this->data['userSignalMetrics'] ?? [];
        if (is_array($rows) && count($rows) === 1) {
            $data['weight'] = 1;
        }

        return $data;
    }

    /** A Try or Undo button that calls back into this page. */
    private function hintButton(string $method, string $key, string $label): HtmlString
    {
        return new HtmlString(
            '<button type="button" class="metric-hint-link" wire:click="'
            . $method . '(\'' . $key . '\')">'
            . e($label)
            . '</button>'
        );
    }

    /** Allows only a repeater key, so the button cannot send anything else to the click handler. */
    private function safeRowKey(string $key): ?string
    {
        return preg_match('/^[A-Za-z0-9_-]+$/', $key) === 1 ? $key : null;
    }

    /** The time-series page for this metric. The chart link opens it in a new tab. */
    private function metricChartUrl(mixed $metricId): ?string
    {
        if (! filled($metricId)) {
            return null;
        }

        return '/app/time-series-page?selectedMetrics=' . (int) $metricId;
    }

    /** The note that replaces the multiplier when the signal has one metric. */
    private function singleMetricNote(float $weight): string
    {
        if (abs($weight - 1) < 0.001) {
            return 'The percent this metric moves over the days you set becomes points on the day\'s score.';
        }

        $number = (new MetricWeightSuggester)->formatExact($weight);

        return "This saved signal still multiplies that percent by {$number}. Saving from Edit uses 1.";
    }

    /** That same note, wrapped for the fourth column on the edit form. */
    private function singleMetricLabel(float $weight): HtmlString
    {
        return new HtmlString(
            '<p class="metric-single-note">' . e($this->singleMetricNote($weight)) . '</p>'
        );
    }

    /** A field label with the pinned tooltip used by In the last and Multiply movement by. */
    private function labelWithTip(string $label, string $tooltip): HtmlString
    {
        return new HtmlString(view('forms.components.label-tip', [
            'label' => $label,
            'tooltip' => $tooltip,
        ])->render());
    }
}
