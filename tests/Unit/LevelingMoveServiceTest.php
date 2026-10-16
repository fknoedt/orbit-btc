<?php

use App\Models\DailyPrice;
use App\Models\Frequency;
use App\Models\Metric;
use App\Models\LevelingMove;
use App\Services\LevelingMoveService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    foreach (['leveling_moves', 'metrics', 'frequencies', 'daily_prices'] as $table) {
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

    Schema::create('leveling_moves', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('metric_id');
        $table->unsignedBigInteger('frequency_id');
        $table->float('abs_percent');
        $table->unsignedInteger('samples');
        $table->date('price_date');
        $table->dateTime('calculated_at');
        $table->unique(['metric_id', 'frequency_id']);
    });

    Schema::create('daily_prices', function (Blueprint $table) {
        $table->id();
        $table->date('date')->unique();
        $table->float('close')->nullable();
        $table->timestamps();
    });
});

function priceSeries(int $days, float $step = 1.1): void
{
    $price = 100.0;
    for ($i = 0; $i < $days; $i++) {
        DailyPrice::create([
            'date' => Carbon::parse('2024-01-01')->addDays($i)->toDateString(),
            'close' => $price,
        ]);
        $price *= $step;
    }
}

it('takes the middle value, and the average of the two middle values', function () {
    $service = new LevelingMoveService;

    expect($service->median([]))->toBeNull()
        ->and($service->median([3, 1, 2]))->toEqual(2.0)
        ->and($service->median([4, 1, 2, 3]))->toEqual(2.5);
});

it('stores the median absolute percent change for each interval', function () {
    priceSeries(40);
    $daily = Frequency::create(['name' => '1 day', 'number_of_days' => 1]);
    $three = Frequency::create(['name' => '3 days', 'number_of_days' => 3]);
    $metric = Metric::create([
        'name' => 'Close',
        'column_name' => 'close',
        'data_limited_at' => '2024-01-01',
    ]);

    $result = (new LevelingMoveService)->update();

    $oneDay = LevelingMove::where('frequency_id', $daily->id)->first();
    $threeDay = LevelingMove::where('frequency_id', $three->id)->first();

    expect($result['updated'])->toBe(2)
        ->and($oneDay->samples)->toBe(39)
        ->and($oneDay->abs_percent)->toEqualWithDelta(10, 0.05)
        ->and($oneDay->price_date->toDateString())->toBe('2024-02-09')
        ->and($threeDay->samples)->toBe(37)
        ->and($threeDay->abs_percent)->toEqualWithDelta(33.1, 0.1);
});

it('updates an existing window in place', function () {
    priceSeries(40);
    Frequency::create(['name' => '1 day', 'number_of_days' => 1]);
    Frequency::create(['name' => '3 days', 'number_of_days' => 3]);
    Metric::create([
        'name' => 'Close',
        'column_name' => 'close',
        'data_limited_at' => '2024-01-01',
    ]);
    $service = new LevelingMoveService;
    $service->update();
    DailyPrice::where('date', '2024-02-09')->update(['close' => 1]);

    $service->update();

    expect(LevelingMove::count())->toBe(2)
        ->and(LevelingMove::query()->orderBy('frequency_id')->first()->price_date->toDateString())
        ->toBe('2024-02-09');
});

it('skips a missing calendar day instead of a neighboring day', function () {
    priceSeries(40);
    DailyPrice::where('date', '2024-01-20')->delete();
    Frequency::create(['name' => '1 day', 'number_of_days' => 1]);
    Metric::create([
        'name' => 'Close',
        'column_name' => 'close',
        'data_limited_at' => '2024-01-01',
    ]);

    (new LevelingMoveService)->update();

    expect(LevelingMove::first()->samples)->toBe(37)
        ->and(LevelingMove::first()->abs_percent)->toEqualWithDelta(10, 0.05);
});

it('does not store a leveling percent until there are enough samples', function () {
    priceSeries(10);
    Frequency::create(['name' => '1 day', 'number_of_days' => 1]);
    Metric::create([
        'name' => 'Close',
        'column_name' => 'close',
        'data_limited_at' => '2024-01-01',
    ]);

    $result = (new LevelingMoveService)->update();

    expect($result['short'])->toBe(1)
        ->and(LevelingMove::count())->toBe(0);
});

it('keeps the last leveling percent when the new sample is too small', function () {
    priceSeries(5);
    $frequency = Frequency::create(['name' => '1 day', 'number_of_days' => 1]);
    $metric = Metric::create([
        'name' => 'Close',
        'column_name' => 'close',
        'data_limited_at' => '2024-01-01',
    ]);
    LevelingMove::create([
        'metric_id' => $metric->id,
        'frequency_id' => $frequency->id,
        'abs_percent' => 4,
        'samples' => 100,
        'price_date' => '2023-12-01',
        'calculated_at' => '2023-12-01 00:00:00',
    ]);

    (new LevelingMoveService)->update();

    $move = LevelingMove::first();

    expect(LevelingMove::count())->toBe(1)
        ->and($move->abs_percent)->toEqual(4.0)
        ->and($move->samples)->toBe(100)
        ->and($move->price_date->toDateString())->toBe('2023-12-01');
});

it('does not write during a dry run', function () {
    priceSeries(40);
    Frequency::create(['name' => '1 day', 'number_of_days' => 1]);
    Metric::create([
        'name' => 'Close',
        'column_name' => 'close',
        'data_limited_at' => '2024-01-01',
    ]);

    $result = (new LevelingMoveService)->update(dryRun: true);

    expect($result['updated'])->toBe(1)
        ->and(LevelingMove::count())->toBe(0);
});

it('skips a column that is not a daily price', function () {
    priceSeries(40);
    Frequency::create(['name' => '1 day', 'number_of_days' => 1]);
    Metric::create([
        'name' => 'Missing',
        'column_name' => 'not_a_column',
        'data_limited_at' => '2024-01-01',
    ]);
    Metric::create([
        'name' => 'Unsafe',
        'column_name' => 'close;drop',
        'data_limited_at' => '2024-01-01',
    ]);

    $result = (new LevelingMoveService)->update();

    expect($result['skipped'])->toBe(2)
        ->and($result['updated'])->toBe(0)
        ->and(LevelingMove::count())->toBe(0);
});
