<?php

use App\Models\DailyPrice;
use App\Models\Frequency;
use App\Models\Metric;
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

it('sums weight times 50 for the max threshold', function () {
    $signal = makeSignal();
    UserSignalMetric::create([
        'user_signal_id' => $signal->id,
        'metric_id' => Metric::first()->id,
        'frequency_id' => Frequency::first()->id,
        'operator' => '-',
        'weight' => 2,
    ]);

    expect((new UserSignalService)->getMaxThreshold($signal->id))->toBe(150)
        ->and((new UserSignalService)->getMaxThreshold(999))->toBe(0);
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

    expect($signal->fresh()->total_simulated_trades)->toBe(2);
});

it('throws when a metric id does not exist', function () {
    (new MetricService)->getMetric(999, true);
})->throws(InvalidArgumentException::class);
