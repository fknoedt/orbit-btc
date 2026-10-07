<?php

use App\Services\DailyPriceService;
use App\Services\MetricService;
use App\Services\SignalGeneratorService;
use App\Services\UserSignalService;

function signalGenerator(): SignalGeneratorService
{
    return new SignalGeneratorService(
        new DailyPriceService,
        new MetricService,
        new UserSignalService,
    );
}

it('returns null when there are not enough prices to compute a change', function () {
    expect(signalGenerator()->medianChange(['2024-01-01' => 100]))->toBeNull();
});

it('computes median, average, and capped high/low percent changes', function () {
    $service = signalGenerator();
    $values = [
        '2024-01-03' => 30,
        '2024-01-01' => 10,
        '2024-01-02' => 20,
    ];

    expect($service->medianChange($values))->toEqual(75)
        ->and($service->averageChange($values))->toEqual(75)
        ->and($service->highChanges(['2024-01-01' => 100, '2024-01-02' => 250]))->toEqual(100)
        ->and($service->lowChanges(['2024-01-01' => 100, '2024-01-02' => 1]))->toEqual(-99);
});
