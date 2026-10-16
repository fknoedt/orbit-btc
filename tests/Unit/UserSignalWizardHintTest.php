<?php

use App\Filament\Resources\UserSignalResource\Pages\ViewUserSignal;
use App\Filament\Resources\UserSignalResource\Traits\UserSignalWizardSteps;
use App\Models\LevelingMove;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

/**
 * The wizard methods under test are private. This page stands in for Edit
 * and calls them without rendering Filament.
 */
function hintPage(): object
{
    return new class
    {
        use UserSignalWizardSteps;

        public array $data = [];

        public function hint(array $rows, string $key, Collection $moves): mixed
        {
            return $this->multiplierHint($rows, $key, $moves, true);
        }

        public function note(float $weight): string
        {
            return $this->singleMetricNote($weight);
        }

        public function level(): void
        {
            $this->levelMetrics();
        }
    };
}

/** Fear 7d is the loudest percent. MVRV and Puell are the quieter 14d rows. */
function sampleMoves(): Collection
{
    $moves = [
        [6, 5, 14.545455],
        [24, 6, 7.056269],
        [29, 6, 13.605442],
    ];

    return collect($moves)->mapWithKeys(function (array $row) {
        $move = new LevelingMove([
            'metric_id' => $row[0],
            'frequency_id' => $row[1],
            'abs_percent' => $row[2],
        ]);

        return [$row[0] . '-' . $row[1] => $move];
    });
}

function sampleRows(float $fearWeight = 3, float $mvrvWeight = 5): array
{
    return [
        'fear' => ['metric_id' => 6, 'frequency_id' => 5, 'weight' => $fearWeight],
        'mvrv' => ['metric_id' => 24, 'frequency_id' => 6, 'weight' => $mvrvWeight],
        'puell' => ['metric_id' => 29, 'frequency_id' => 6, 'weight' => 4],
    ];
}

it('names a saved one-metric multiplier without rounding it to one decimal', function () {
    $page = hintPage();

    expect($page->note(1.34))
        ->toBe('This saved signal still multiplies that percent by 1.34. Saving from Edit uses 1.')
        ->and($page->note(1.0))
        ->toBe('The percent this metric moves over the days you set becomes points on the day\'s score.');
});

it('shows undo only while the applied suggestion is still current', function () {
    $page = hintPage();
    $moves = sampleMoves();
    $rows = sampleRows();
    $rows['mvrv']['weight'] = 6.2;

    expect($page->hint($rows, 'mvrv', $moves))->toBeNull();

    $page->multiplierBeforeHint['mvrv'] = [
        'weight' => 5.0,
        'metric_id' => 24,
        'frequency_id' => 6,
        'applied' => 6.2,
    ];

    expect((string) $page->hint($rows, 'mvrv', $moves))->toContain('Undo: 5');

    $rows['fear']['weight'] = 1;

    $afterAnchorChange = (string) $page->hint($rows, 'mvrv', $moves);

    expect($afterAnchorChange)->toContain('Try 2.1 to level the metrics')
        ->and($afterAnchorChange)->not->toContain('Undo');
});

it('does not change a viewed signal when a hint is clicked', function () {
    $view = (new ReflectionClass(ViewUserSignal::class))->newInstanceWithoutConstructor();
    $view->multiplierBeforeHint = [];
    $view->levelMetricsNotice = null;
    $view->data = [
        'userSignalMetrics' => [
            'only' => ['metric_id' => 6, 'frequency_id' => 5, 'weight' => 1.34],
        ],
    ];

    $view->applyMultiplierHint('only');
    $view->levelMetrics();

    expect($view->data['userSignalMetrics']['only']['weight'])->toEqual(1.34)
        ->and($view->multiplierBeforeHint)->toBe([])
        ->and($view->levelMetricsNotice)->toBeNull();
});

it('asks for another metric when leveling with only one chosen', function () {
    $page = hintPage();
    $page->data = [
        'userSignalMetrics' => [
            'rsi' => ['metric_id' => 6, 'frequency_id' => 1, 'weight' => 1],
            'empty' => ['metric_id' => null, 'frequency_id' => 1, 'weight' => 1],
        ],
    ];

    $page->level();

    expect($page->levelMetricsNotice)->toBe('More than one metric has to be chosen.')
        ->and($page->data['userSignalMetrics']['rsi']['weight'])->toEqual(1);

    $page->level();

    expect($page->levelMetricsNotice)->toBe('More than one metric has to be chosen.');
});

it('loads leveling percents once when leveling every row', function () {
    Schema::dropIfExists('leveling_moves');
    Schema::create('leveling_moves', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('metric_id');
        $table->unsignedBigInteger('frequency_id');
        $table->float('abs_percent');
        $table->unsignedInteger('samples')->default(30);
        $table->date('price_date')->nullable();
        $table->dateTime('calculated_at')->nullable();
    });

    foreach (sampleMoves() as $move) {
        LevelingMove::query()->create([
            'metric_id' => $move->metric_id,
            'frequency_id' => $move->frequency_id,
            'abs_percent' => $move->abs_percent,
            'samples' => 30,
            'price_date' => '2026-10-14',
            'calculated_at' => '2026-10-14 00:00:00',
        ]);
    }

    $page = hintPage();
    $page->levelMetricsNotice = 'More than one metric has to be chosen.';
    $page->data = ['userSignalMetrics' => sampleRows()];
    DB::flushQueryLog();
    DB::enableQueryLog();

    $page->level();

    $selects = array_values(array_filter(
        DB::getQueryLog(),
        fn (array $query): bool => str_contains(strtolower($query['query']), 'from "leveling_moves"')
            || str_contains(strtolower($query['query']), 'from `leveling_moves`')
    ));
    $rows = $page->data['userSignalMetrics'];

    Schema::dropIfExists('leveling_moves');

    expect($selects)->toHaveCount(1)
        ->and($rows['fear']['weight'])->toEqual(3)
        ->and($rows['mvrv']['weight'])->toEqual(6.2)
        ->and($rows['puell']['weight'])->toEqual(3.2)
        ->and($page->levelMetricsNotice)->toBeNull();
});
