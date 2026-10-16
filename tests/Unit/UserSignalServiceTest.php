<?php

use App\Models\DailyPrice;
use App\Models\Frequency;
use App\Models\Metric;
use App\Models\LevelingMove;
use App\Models\UserSignal;
use App\Models\UserSignalDailyScore;
use App\Models\UserSignalMetric;
use App\Services\MetricService;
use App\Services\UserSignalService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Cache::flush();
    Carbon::setTestNow('2024-03-10 12:00:00');

    foreach ([
        'leveling_moves',
        'user_signal_daily_scores',
        'user_signal_metrics',
        'user_signals',
        'metrics',
        'frequencies',
        'daily_prices',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('frequencies', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('number_of_days');
        $table->timestamp('created_at')->nullable();
    });

    Schema::create('metrics', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('column_name');
        $table->date('data_limited_at')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('user_signals', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('name')->nullable();
        $table->float('threshold')->nullable();
        $table->boolean('is_paused')->default(false);
        $table->string('buy_or_sell')->nullable();
        $table->unsignedInteger('time_horizon')->nullable();
        $table->boolean('conviction_trade')->nullable();
        $table->float('last_score')->nullable();
        $table->date('last_date_calculated')->nullable();
        $table->float('last_signal_value')->nullable();
        $table->float('total_signal_value')->nullable();
        $table->dateTime('scores_last_updated_at')->nullable();
        $table->integer('total_simulated_trades')->nullable();
        $table->boolean('warning')->nullable();
        $table->boolean('error')->nullable();
        $table->date('data_limited_at')->nullable();
        $table->date('first_date_calculated')->nullable();
        $table->timestamps();
    });

    Schema::create('user_signal_metrics', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_signal_id');
        $table->unsignedBigInteger('metric_id');
        $table->unsignedBigInteger('frequency_id');
        $table->string('operator')->default('+');
        $table->float('weight')->default(1);
        $table->float('threshold')->nullable();
        $table->timestamps();
    });

    Schema::create('user_signal_daily_scores', function (Blueprint $table) {
        $table->id();
        $table->date('date');
        $table->unsignedBigInteger('user_signal_id');
        $table->float('score');
        $table->float('signal_value')->nullable();
        $table->float('conviction')->nullable();
        $table->float('stake')->nullable();
        $table->boolean('quarantined')->nullable();
    });

    Schema::create('leveling_moves', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('metric_id');
        $table->unsignedBigInteger('frequency_id');
        $table->float('abs_percent');
        $table->unsignedInteger('samples');
        $table->date('price_date');
        $table->dateTime('calculated_at');
    });

    Schema::create('daily_prices', function (Blueprint $table) {
        $table->id();
        $table->date('date')->unique();
        $table->float('close')->nullable();
        $table->float('price_change_1d')->nullable();
        $table->timestamps();
    });
});

function seedPrices(string $from, string $to, float $close = 100, float $change1d = 10): void
{
    for ($date = Carbon::parse($from); $date->lte(Carbon::parse($to)); $date->addDay()) {
        DailyPrice::create([
            'date' => $date->toDateString(),
            'close' => $close,
            'price_change_1d' => $change1d,
        ]);
    }
}

function makeSignal(array $signal = [], array $metric = []): UserSignal
{
    $frequency = Frequency::create(['name' => 'Daily', 'number_of_days' => 1]);
    $closeMetric = Metric::create([
        'name' => 'Close',
        'column_name' => 'close',
        'data_limited_at' => '2009-10-05',
    ]);

    $userSignal = UserSignal::create(array_merge([
        'user_id' => 1,
        'name' => 'test-signal',
        'threshold' => 0,
        'is_paused' => false,
        'buy_or_sell' => 'buy',
        'time_horizon' => 1,
        'conviction_trade' => false,
    ], $signal));

    UserSignalMetric::create(array_merge([
        'user_signal_id' => $userSignal->id,
        'metric_id' => $closeMetric->id,
        'frequency_id' => $frequency->id,
        'operator' => '+',
        'weight' => 1,
    ], $metric));

    return $userSignal;
}

function seedLevelingMove(UserSignalMetric $metric, float $percent): void
{
    LevelingMove::create([
        'metric_id' => $metric->metric_id,
        'frequency_id' => $metric->frequency_id,
        'abs_percent' => $percent,
        'samples' => 30,
        'price_date' => '2024-03-10',
        'calculated_at' => now(),
    ]);
}

it('sums a 100 percent move times each multiplier for the max threshold', function () {
    $signal = makeSignal();
    UserSignalMetric::create([
        'user_signal_id' => $signal->id,
        'metric_id' => Metric::first()->id,
        'frequency_id' => Frequency::first()->id,
        'operator' => '-',
        'weight' => 2,
    ]);

    expect((new UserSignalService)->getMaxThreshold($signal->id))->toBe(300)
        ->and((new UserSignalService)->getMaxThreshold(999))->toBe(0);
});

it('caps a one-metric slider at a 100 percent move times its multiplier', function () {
    $signal = makeSignal([], ['weight' => 4]);

    expect((new UserSignalService)->getMaxThreshold($signal->id))->toBe(400);
});

it('skips paused signals', function () {
    makeSignal(['is_paused' => true]);

    $stats = (new UserSignalService)->updateDailyScores(
        userSignalId: UserSignal::first()->id,
        since: Carbon::parse('2024-03-07'),
    );

    expect($stats['totalDailyScoresCreated'])->toBe(0)
        ->and(UserSignalDailyScore::count())->toBe(0);
});

it('scores days, simulates a buy, and quarantines the time horizon', function () {
    seedPrices('2024-02-06', '2024-03-10');
    $signal = makeSignal();

    $stats = (new UserSignalService)->updateDailyScores(
        userSignalId: $signal->id,
        since: Carbon::parse('2024-03-07'),
    );

    $scores = UserSignalDailyScore::orderBy('date')->get();

    expect($stats['totalDailyScoresCreated'])->toBe(3)
        ->and($stats['totalSimulatedTrades'])->toBe(2)
        ->and($stats['lastTotalSignalValue'])->toEqual(200)
        ->and($scores)->toHaveCount(3)
        ->and($scores[0]->quarantined)->toBeFalsy()
        ->and($scores[0]->stake)->toEqual(1000)
        ->and($scores[0]->signal_value)->toEqual(100)
        ->and($scores[1]->quarantined)->toBeTruthy()
        ->and($scores[1]->signal_value)->toBeNull()
        ->and($scores[2]->quarantined)->toBeFalsy()
        ->and($scores[2]->signal_value)->toEqual(100);

    expect($signal->fresh()->total_simulated_trades)->toBe(2)
        ->and(substr((string) $signal->fresh()->first_date_calculated, 0, 10))->toBe('2024-03-08');
});

it('does not carry the trade wait from one signal into the next', function () {
    seedPrices('2024-02-06', '2024-03-10');
    makeSignal(['name' => 'first']);
    $second = makeSignal(['name' => 'second']);

    (new UserSignalService)->updateDailyScores(since: Carbon::parse('2024-03-07'));

    $scores = UserSignalDailyScore::where('user_signal_id', $second->id)->orderBy('date')->get();

    expect($scores)->toHaveCount(3)
        ->and($scores[0]->quarantined)->toBeFalsy()
        ->and($scores[0]->signal_value)->toEqual(100)
        ->and($scores[1]->quarantined)->toBeTruthy()
        ->and($scores[1]->signal_value)->toBeNull()
        ->and($scores[2]->quarantined)->toBeFalsy()
        ->and($scores[2]->signal_value)->toEqual(100);
});

it('adds points for a rise only when direction is up', function () {
    seedPrices('2024-02-06', '2024-03-10', close: 100, change1d: 10);
    DailyPrice::where('date', '2024-03-08')->update(['close' => 110]);

    $up = makeSignal(['name' => 'up', 'threshold' => 1], ['operator' => '+']);
    $down = makeSignal(['name' => 'down', 'threshold' => 1], ['operator' => '-']);
    seedLevelingMove($up->userSignalMetrics()->first(), 10);
    seedLevelingMove($down->userSignalMetrics()->first(), 10);

    (new UserSignalService)->updateDailyScores(since: Carbon::parse('2024-03-07'));

    $upDay = UserSignalDailyScore::where('user_signal_id', $up->id)->orderBy('date')->first();
    $downDay = UserSignalDailyScore::where('user_signal_id', $down->id)->orderBy('date')->first();

    expect($upDay->score)->toEqual(10.0)
        ->and($upDay->signal_value)->toEqual(100)
        ->and($downDay->score)->toEqual(0.0)
        ->and($downDay->signal_value)->toBeNull();
});

it('scores one metric as the percent change times its multiplier', function () {
    seedPrices('2024-02-06', '2024-03-10', close: 100, change1d: 10);
    DailyPrice::where('date', '2024-03-08')->update(['close' => 120]);

    $signal = makeSignal(['threshold' => 1000], ['operator' => '+-', 'weight' => 4]);

    (new UserSignalService)->updateDailyScores(
        userSignalId: $signal->id,
        since: Carbon::parse('2024-03-07'),
    );

    $day = UserSignalDailyScore::orderBy('date')->first();

    expect($day->score)->toEqualWithDelta(80, 0.01)
        ->and($day->signal_value)->toBeNull()
        ->and($signal->fresh()->warning)->toBeFalsy();
});

it('multiplies each metric percent even when a leveling percent is stored', function () {
    seedPrices('2024-02-06', '2024-03-10', close: 100, change1d: 10);
    DailyPrice::where('date', '2024-03-08')->update(['close' => 120]);
    DailyPrice::where('date', '2024-03-09')->update(['close' => 240]);

    $signal = makeSignal(['name' => 'two-metrics', 'threshold' => 1000], ['operator' => '+-', 'weight' => 4]);
    $first = $signal->userSignalMetrics()->first();
    seedLevelingMove($first, 10);
    $second = UserSignalMetric::create([
        'user_signal_id' => $signal->id,
        'metric_id' => Metric::create([
            'name' => 'Close again',
            'column_name' => 'close',
            'data_limited_at' => '2009-10-05',
        ])->id,
        'frequency_id' => $first->frequency_id,
        'operator' => '+-',
        'weight' => 4,
    ]);
    seedLevelingMove($second, 10);

    (new UserSignalService)->updateDailyScores(
        userSignalId: $signal->id,
        since: Carbon::parse('2024-03-07'),
    );

    $scores = UserSignalDailyScore::orderBy('date')->get();

    expect($scores[0]->score)->toEqualWithDelta(160, 0.01)
        ->and($scores[1]->score)->toEqualWithDelta(800, 0.01)
        ->and($scores[1]->signal_value)->toBeNull();
});

it('still scores a row when no leveling percent is stored', function () {
    seedPrices('2024-02-06', '2024-03-10', close: 100, change1d: 10);
    DailyPrice::where('date', '2024-03-08')->update(['close' => 110]);
    $signal = makeSignal(['name' => 'missing-level', 'threshold' => 1000], ['operator' => '+-', 'weight' => 4]);
    $first = $signal->userSignalMetrics()->first();
    UserSignalMetric::create([
        'user_signal_id' => $signal->id,
        'metric_id' => Metric::create([
            'name' => 'Close two',
            'column_name' => 'close',
            'data_limited_at' => '2009-10-05',
        ])->id,
        'frequency_id' => $first->frequency_id,
        'operator' => '+-',
        'weight' => 4,
    ]);

    (new UserSignalService)->updateDailyScores(
        userSignalId: $signal->id,
        since: Carbon::parse('2024-03-07'),
    );

    $day = UserSignalDailyScore::orderBy('date')->first();

    expect($day->score)->toEqualWithDelta(80, 0.01)
        ->and($day->signal_value)->toBeNull()
        ->and($signal->fresh()->warning)->toBeFalsy();
});

it('does not open a trade when the forward price is still unknown', function () {
    seedPrices('2024-02-06', '2024-03-10', close: 100, change1d: 10);
    DailyPrice::where('date', '2024-03-08')->update(['price_change_1d' => null]);
    $signal = makeSignal(['threshold' => 0]);

    (new UserSignalService)->updateDailyScores(
        userSignalId: $signal->id,
        since: Carbon::parse('2024-03-07'),
    );

    $scores = UserSignalDailyScore::orderBy('date')->get();

    expect($scores[0]->signal_value)->toBeNull()
        ->and($scores[0]->quarantined)->toBeFalsy()
        ->and($scores[1]->signal_value)->toEqual(100)
        ->and($scores[1]->quarantined)->toBeFalsy()
        ->and($scores[2]->quarantined)->toBeTruthy();
});

it('clears a leftover total when a signal has no metrics', function () {
    $signal = makeSignal(['total_signal_value' => 250, 'total_simulated_trades' => 4]);
    $signal->userSignalMetrics()->delete();
    UserSignalDailyScore::create([
        'date' => '2024-03-08',
        'user_signal_id' => $signal->id,
        'score' => 10,
    ]);

    (new UserSignalService)->updateDailyScores(userSignalId: $signal->id);

    expect(UserSignalDailyScore::count())->toBe(0)
        ->and((float) $signal->fresh()->total_signal_value)->toEqual(0.0)
        ->and($signal->fresh()->total_simulated_trades)->toBe(0);
});

it('throws when a metric id does not exist', function () {
    (new MetricService)->getMetric(999, true);
})->throws(InvalidArgumentException::class);
