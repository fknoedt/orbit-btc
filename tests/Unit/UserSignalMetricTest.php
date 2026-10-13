<?php

use App\Enum\Operators;
use App\Models\DailyPrice;
use App\Models\UserSignalMetric;
use Tests\TestCase;

uses(TestCase::class);

it('treats a price increase as negative oscillation', function () {
    $metric = new UserSignalMetric;

    expect($metric->dailyOscillation(
        new DailyPrice(['close' => 100]),
        new DailyPrice(['close' => 110]),
        'close',
    ))->toEqualWithDelta(-10, 0.01);
});

it('treats a price decrease as positive oscillation', function () {
    $metric = new UserSignalMetric;

    expect($metric->dailyOscillation(
        new DailyPrice(['close' => 100]),
        new DailyPrice(['close' => 90]),
        'close',
    ))->toEqualWithDelta(10, 0.01);
});

it('keeps a rise for up, a drop for down, and either for both', function () {
    $up = new UserSignalMetric(['operator' => Operators::PLUS]);
    $down = new UserSignalMetric(['operator' => Operators::MINUS]);
    $both = new UserSignalMetric(['operator' => Operators::PLUS_MINUS]);

    expect($up->matchesDirection(-10))->toBeTrue()
        ->and($up->matchesDirection(10))->toBeFalse()
        ->and($down->matchesDirection(10))->toBeTrue()
        ->and($down->matchesDirection(-10))->toBeFalse()
        ->and($both->matchesDirection(-10))->toBeTrue()
        ->and($both->matchesDirection(10))->toBeTrue();
});

it('returns zero when a value is missing', function () {
    $metric = new UserSignalMetric;

    expect($metric->dailyOscillation(
        new DailyPrice(['close' => 100]),
        new DailyPrice(['close' => null]),
        'close',
    ))->toEqual(0);
});
