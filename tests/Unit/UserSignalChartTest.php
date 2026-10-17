<?php

use App\Filament\Charts\UserSignalChart;
use App\Models\Frequency;
use App\Models\Metric;
use App\Models\UserSignalMetric;
use Tests\TestCase;

uses(TestCase::class);

/**
 * metricLines is private. This stand-in calls it without a chart or a database.
 */
function signalChartPage(): object
{
    return new class
    {
        use UserSignalChart;

        public function lines(iterable $signalMetrics, array $dailyPrices, array $dates): array
        {
            return $this->metricLines($signalMetrics, $dailyPrices, $dates);
        }
    };
}

function chartMetric(string $name, string $column, ?string $color = null): Metric
{
    return new Metric([
        'name' => $name,
        'column_name' => $column,
        'color' => $color,
    ]);
}

function chartSignalMetric(Metric $metric, string $frequency = '1-day interval'): UserSignalMetric
{
    $row = new UserSignalMetric;
    $row->setRelation('metric', $metric);
    $row->setRelation('frequency', new Frequency(['name' => $frequency]));

    return $row;
}

it('draws each metric on its own scale and leaves a gap where the value is missing', function () {
    $lines = signalChartPage()->lines(
        [
            chartSignalMetric(chartMetric('MVRV', 'mvrv', '#4CAF50')),
            chartSignalMetric(chartMetric('Mayer Multiple', 'mayer_multiple', '#E91E63')),
        ],
        [
            '2026-02-28' => ['mvrv' => '1.238', 'mayer_multiple' => 0.687],
            '2026-03-01' => ['mvrv' => null, 'mayer_multiple' => 0.669],
            '2026-03-02' => ['mvrv' => '', 'mayer_multiple' => '0'],
        ],
        ['2026-02-28', '2026-03-01', '2026-03-02'],
    );

    expect($lines['series'])->toHaveCount(2)
        ->and($lines['series'][0]['name'])->toBe('MVRV (1-day interval)')
        ->and($lines['series'][0]['type'])->toBe('line')
        ->and($lines['series'][0]['data'])->toBe([1.238, null, null])
        ->and($lines['series'][1]['name'])->toBe('Mayer Multiple (1-day interval)')
        ->and($lines['series'][1]['data'])->toBe([0.687, 0.669, 0.0])
        ->and($lines['strokes'])->toBe([2, 2])
        ->and($lines['fills'])->toBe(['solid', 'solid'])
        ->and($lines['colors'])->toBe(['#4CAF50', '#E91E63']);

    $mvrvAxis = $lines['axes'][0];
    expect($mvrvAxis['seriesName'])->toBe('MVRV (1-day interval)')
        ->and($mvrvAxis['show'])->toBeFalse()
        ->and($mvrvAxis['floating'])->toBeTrue()
        ->and($mvrvAxis['min'])->toBeLessThan(1.238)
        ->and($mvrvAxis['max'])->toBeGreaterThan(1.238)
        ->and($mvrvAxis['max'] - $mvrvAxis['min'])->toBeLessThan(1)
        ->and($mvrvAxis['decimalsInFloat'])->toBe(2);

    $mayerAxis = $lines['axes'][1];
    expect($mayerAxis['seriesName'])->toBe('Mayer Multiple (1-day interval)')
        ->and($mayerAxis['min'])->toBeLessThan(0)
        ->and($mayerAxis['max'])->toBeGreaterThan(0.687)
        ->and($mayerAxis['decimalsInFloat'])->toBe(3);
});

it('keeps a metric that has no numbers, and suffixes a repeated name', function () {
    $blank = new UserSignalMetric;
    $blank->setRelation('metric', null);

    $noColumn = new UserSignalMetric;
    $noColumn->setRelation('metric', chartMetric('Empty', ''));

    $lines = signalChartPage()->lines(
        [
            $blank,
            $noColumn,
            chartSignalMetric(chartMetric('Hashrate', 'average_hashrate', 'not-a-color')),
            chartSignalMetric(chartMetric('Hashrate', 'average_hashrate', '#A067E9'), '30-day interval'),
            chartSignalMetric(chartMetric('Hashrate', 'average_hashrate'), '30-day interval'),
        ],
        [
            '2026-10-01' => ['average_hashrate' => null],
            '2026-10-02' => ['average_hashrate' => 'n/a'],
        ],
        ['2026-10-01', '2026-10-02'],
    );

    expect($lines['series'])->toHaveCount(3)
        ->and($lines['series'][0]['name'])->toBe('Hashrate (1-day interval)')
        ->and($lines['series'][0]['data'])->toBe([null, null])
        ->and($lines['series'][1]['name'])->toBe('Hashrate (30-day interval)')
        ->and($lines['series'][2]['name'])->toBe('Hashrate (30-day interval) 2')
        ->and($lines['colors'])->toBe(['#38bdf8', '#A067E9', '#fbbf24'])
        ->and($lines['axes'][0])->not->toHaveKey('min')
        ->and($lines['axes'][0]['decimalsInFloat'])->toBe(2)
        ->and($lines['axes'][0]['seriesName'])->toBe('Hashrate (1-day interval)')
        ->and($lines['axes'][2]['seriesName'])->toBe('Hashrate (30-day interval) 2');
});

it('uses a wide scale for a large metric and does not borrow the price scale', function () {
    $lines = signalChartPage()->lines(
        [chartSignalMetric(chartMetric('Puell Multiple', 'puell_multiple'))],
        [
            '2026-10-01' => ['puell_multiple' => 12],
            '2026-10-02' => ['puell_multiple' => 18],
        ],
        ['2026-10-01', '2026-10-02'],
    );

    $axis = $lines['axes'][0];
    expect($lines['series'][0]['data'])->toBe([12.0, 18.0])
        ->and($axis['min'])->toBeGreaterThan(10)
        ->and($axis['max'])->toBeLessThan(20)
        ->and($axis['decimalsInFloat'])->toBe(0);
});
