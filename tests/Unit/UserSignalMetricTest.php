<?php

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

it('returns zero when a value is missing', function () {
    $metric = new UserSignalMetric;

    expect($metric->dailyOscillation(
        new DailyPrice(['close' => 100]),
        new DailyPrice(['close' => null]),
        'close',
    ))->toEqual(0);
});
