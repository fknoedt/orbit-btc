<?php

use App\Exceptions\DailyPriceStatsException;
use App\Models\DailyPrice;
use App\Services\PriceHistoryService;
use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Cache::flush();
    Schema::dropIfExists('daily_prices');
    Schema::create('daily_prices', function (Blueprint $table) {
        $table->id();
        $table->date('date')->unique();
        $table->float('close')->nullable();
        $table->float('rsi')->nullable();
        $table->float('mayer_multiple')->nullable();
        $table->float('bb_upper')->nullable();
        $table->float('bb_middle')->nullable();
        $table->float('bb_lower')->nullable();
        $table->float('price_change_1d')->nullable();
        $table->float('price_change_3d')->nullable();
        $table->float('price_change_5d')->nullable();
        $table->float('price_change_10d')->nullable();
        $table->float('price_change_14d')->nullable();
        $table->float('price_change_30d')->nullable();
        $table->float('roi_30d')->nullable();
        $table->float('roi_1yr')->nullable();
        $table->timestamps();
    });
});

function insertCloses(string $from, int $days, callable $close): void
{
    $date = Carbon::parse($from);
    for ($i = 0; $i < $days; $i++) {
        DailyPrice::create([
            'date' => $date->toDateString(),
            'close' => $close($i),
        ]);
        $date->addDay();
    }
}

function priceHistoryConsole(): OutputStyle
{
    return new OutputStyle(new ArrayInput([]), new NullOutput);
}

it('requires an output when updating future price change', function () {
    (new PriceHistoryService)->updateFuturePriceChange('2024-03-01');
})->throws(InvalidArgumentException::class);

it('throws when there is not enough data for RSI', function () {
    insertCloses('2024-03-01', 5, fn () => 100);

    (new PriceHistoryService)->updateRsi('2024-03-05');
})->throws(DailyPriceStatsException::class);

it('sets RSI to 100 when every close is higher than the last', function () {
    insertCloses('2024-03-01', 16, fn (int $i) => 100 + $i);

    expect((new PriceHistoryService)->updateRsi('2024-03-16'))->toBeGreaterThan(0)
        ->and(DailyPrice::where('date', '2024-03-16')->value('rsi'))->toEqual(100);
});

it('collapses bollinger bands to the close when price is flat', function () {
    insertCloses('2024-03-01', 20, fn () => 50);

    (new PriceHistoryService)->updateBollingerBands('2024-03-20');

    $day = DailyPrice::where('date', '2024-03-20')->first();

    expect($day->bb_middle)->toEqual(50)
        ->and($day->bb_upper)->toEqual(50)
        ->and($day->bb_lower)->toEqual(50);
});

it('computes mayer multiple as close over the 200-day average', function () {
    insertCloses('2023-08-23', 200, fn () => 100);
    DailyPrice::create(['date' => '2024-03-10', 'close' => 200]);

    (new PriceHistoryService)->updateMayerMultiple('2024-03-10');

    expect(DailyPrice::where('date', '2024-03-10')->value('mayer_multiple'))->toEqual(2);
});

it('writes the 1-day future percent change', function () {
    Carbon::setTestNow('2024-03-10 12:00:00');
    DailyPrice::create(['date' => '2024-03-09', 'close' => 100]);
    DailyPrice::create(['date' => '2024-03-10', 'close' => 110]);

    // start one calendar day earlier: sqlite compares date strings against datetimes
    (new PriceHistoryService)->updateFuturePriceChange('2024-03-08', priceHistoryConsole());

    expect(DailyPrice::where('date', '2024-03-09')->value('price_change_1d'))->toEqual(10)
        ->and(DailyPrice::where('date', '2024-03-10')->value('price_change_1d'))->toBeNull();
});

it('writes the 30-day and 365-day return of the close', function () {
    insertCloses('2023-01-01', 366, function (int $i) {
        if ($i === 0) {
            return 50;
        }
        if ($i === 335) {
            return 80;
        }

        return 100;
    });

    // 2023-12-02 (index 335) is the 30-day base for 2024-01-01. Days before --since stay empty.
    expect((new PriceHistoryService)->updateRoi('2024-01-01'))->toBe(2)
        ->and(DailyPrice::where('date', '2023-12-02')->value('roi_30d'))->toBeNull()
        ->and(DailyPrice::where('date', '2024-01-01')->value('roi_30d'))->toEqual(25)
        ->and(DailyPrice::where('date', '2024-01-01')->value('roi_1yr'))->toEqual(100);
});

it('leaves the return empty when the earlier close is missing or zero', function () {
    DailyPrice::create(['date' => '2024-03-01', 'close' => 0]);
    DailyPrice::create(['date' => '2024-03-31', 'close' => 110]);
    DailyPrice::create(['date' => '2024-05-01', 'close' => 120]);

    expect((new PriceHistoryService)->updateRoi('2024-03-31'))->toBe(0)
        ->and(DailyPrice::where('date', '2024-03-31')->value('roi_30d'))->toBeNull()
        ->and(DailyPrice::where('date', '2024-05-01')->value('roi_30d'))->toBeNull();
});

it('keeps a return that is already stored', function () {
    insertCloses('2024-03-01', 31, fn (int $i) => $i === 30 ? 110 : 100);
    DailyPrice::where('date', '2024-03-31')->update(['roi_30d' => 99]);

    (new PriceHistoryService)->updateRoi('2024-03-31');

    expect(DailyPrice::where('date', '2024-03-31')->value('roi_30d'))->toEqual(99);
});

it('ignores an empty return from before the series starts', function () {
    DailyPrice::create(['date' => '2009-10-06', 'close' => 1]);
    DailyPrice::create(['date' => '2024-06-01', 'close' => 2]);

    expect(DailyPrice::getLastEmptyRoiDay())->toBe('2024-06-01');
});
