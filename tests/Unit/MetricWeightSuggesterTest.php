<?php

use App\Services\MetricWeightSuggester;

it('raises the quieter metric so a typical move matches the louder one', function () {
    $weights = (new MetricWeightSuggester)->desiredWeights([
        'a' => ['percent' => 10, 'weight' => 1],
        'b' => ['percent' => 1, 'weight' => 1],
    ]);

    expect($weights)->toBe(['a' => 1.0, 'b' => 10.0]);
});

it('raises the metric that was added first when the new one moves more', function () {
    $weights = (new MetricWeightSuggester)->desiredWeights([
        'b' => ['percent' => 1, 'weight' => 1],
        'a' => ['percent' => 10, 'weight' => 1],
    ]);

    expect($weights)->toBe(['b' => 10.0, 'a' => 1.0]);
});

it('keeps a louder metric at the number the user already set', function () {
    $weights = (new MetricWeightSuggester)->desiredWeights([
        'a' => ['percent' => 10, 'weight' => 5],
        'b' => ['percent' => 1, 'weight' => 1],
    ]);

    expect($weights['a'])->toBe(5.0)
        ->and($weights['b'])->toBe(50.0);
});

it('rounds to one decimal and caps at 100', function () {
    $suggester = new MetricWeightSuggester;

    expect($suggester->desiredWeights([
        'a' => ['percent' => 10, 'weight' => 1],
        'b' => ['percent' => 3, 'weight' => 1],
    ])['b'])->toBe(3.3)
        ->and($suggester->desiredWeights([
            'loud' => ['percent' => 40, 'weight' => 1],
            'quiet' => ['percent' => 0.1, 'weight' => 1],
        ])['quiet'])->toBe(100.0);
});

it('does nothing with fewer than two usable moves', function () {
    $suggester = new MetricWeightSuggester;

    expect($suggester->desiredWeights([
        'a' => ['percent' => 10, 'weight' => 1],
    ]))->toBe([])
        ->and($suggester->desiredWeights([
            'a' => ['percent' => 10, 'weight' => 1],
            'b' => ['percent' => null, 'weight' => 1],
        ]))->toBe([]);
});

it('shows the suggestion with one decimal', function () {
    $suggester = new MetricWeightSuggester;

    expect($suggester->formatDecimal(10))->toBe('10.0')
        ->and($suggester->formatDecimal(3.333))->toBe('3.3')
        ->and($suggester->formatDecimal(14.16))->toBe('14.2')
        ->and($suggester->suggestionLine(3.333, false, false))
        ->toBe('Try 3.3 to level the metrics')
        ->and($suggester->suggestionLine(10, false, false))
        ->toBe('Try 10.0 to level the metrics')
        ->and($suggester->suggestionLine(3.2, true, false))->toBeNull()
        ->and($suggester->suggestionLine(100, false, true))
        ->toBe('Try 100.0 to level the metrics')
        ->and($suggester->suggestionLine(100, true, true))
        ->toBe('100 is the highest this can go')
        ->and($suggester->formatExact(1.34))->toBe('1.34')
        ->and($suggester->formatExact(1))->toBe('1')
        ->and($suggester->formatExact(6.2))->toBe('6.2')
        ->and($suggester->formatNumber(1.34))->toBe('1.3');
});
