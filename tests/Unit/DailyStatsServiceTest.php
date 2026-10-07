<?php

use App\Models\DailyPrice;
use App\Services\DailyStatsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('daily_prices');
    Schema::create('daily_prices', function (Blueprint $table) {
        $table->id();
        $table->date('date')->unique();
        $table->float('close')->nullable();
        $table->float('m2')->nullable();
        $table->timestamps();
    });
});

it('rejects empty, badly keyed, or out-of-order fill payloads', function (array $payload) {
    (new DailyStatsService)->fillStats($payload);
})->with([
    [[]],
    [['not-a-date' => ['close' => 1]]],
    [['2024-01-02' => ['close' => 1], '2024-01-01' => ['close' => 2]]],
])->throws(InvalidArgumentException::class);

it('fills null columns and leaves existing values unless forced', function () {
    DailyPrice::create(['date' => '2024-01-01', 'close' => null]);
    DailyPrice::create(['date' => '2024-01-02', 'close' => 100]);

    $service = new DailyStatsService;
    $payload = [
        '2024-01-01' => ['close' => 42],
        '2024-01-02' => ['close' => 99],
    ];

    expect($service->fillStats($payload))->toBe(1)
        ->and(DailyPrice::where('date', '2024-01-01')->value('close'))->toEqual(42)
        ->and(DailyPrice::where('date', '2024-01-02')->value('close'))->toEqual(100);

    expect($service->fillStats($payload, force: true))->toBe(2)
        ->and(DailyPrice::where('date', '2024-01-02')->value('close'))->toEqual(99);
});

it('forwards the last known value into later nulls', function () {
    DailyPrice::create(['date' => '2024-01-01', 'm2' => 10]);
    DailyPrice::create(['date' => '2024-01-02', 'm2' => null]);
    DailyPrice::create(['date' => '2024-01-03', 'm2' => null]);

    expect((new DailyStatsService)->fillForward('m2'))->toBe(2)
        ->and(DailyPrice::where('date', '2024-01-03')->value('m2'))->toEqual(10);
});
