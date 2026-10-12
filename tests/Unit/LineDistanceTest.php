<?php

use App\Exceptions\MathException;
use App\Math\LineDistance;

function percentFromStart(array $prices): array
{
    $base = $prices[0];

    return array_map(fn ($price) => ($price / $base) - 1, $prices);
}

it('returns zero for the same line at a different price level', function () {
    $valley = [10, 8, 6, 8, 10];
    $sameShape = [100, 80, 60, 80, 100];

    expect((new LineDistance)->distance(percentFromStart($valley), $sameShape))->toBe(0.0);
});

it('ranks a valley closer to another valley than to a climb', function () {
    $line = new LineDistance;
    $valley = percentFromStart([10, 8, 6, 8, 10]);
    $nearValley = [10, 9, 7, 9, 10];
    $climb = [10, 12, 14, 16, 18];

    $near = $line->distance($valley, $nearValley);
    $far = $line->distance($valley, $climb);

    expect($near)->toBeLessThan($far);
});

it('stops once the line is already too far', function () {
    $valley = percentFromStart([10, 8, 6, 8, 10]);

    expect((new LineDistance)->distance($valley, [10, 12, 14, 16, 18], 0, 0.0))->toBeNull();
});

it('returns the distance when the line stays within the cap', function () {
    $line = new LineDistance;
    $valley = percentFromStart([10, 8, 6, 8, 10]);
    $full = $line->distance($valley, [10, 9, 7, 9, 10]);

    expect($line->distance($valley, [10, 9, 7, 9, 10], 0, $full))->toBe($full);
});

it('rejects an empty query or a window that does not fit', function (array $query, array $prices) {
    (new LineDistance)->distance($query, $prices);
})->with([
    [[], []],
    [[0.0, -0.1], [10]],
])->throws(MathException::class);

it('rejects a window that starts at zero', function () {
    (new LineDistance)->distance([0.0, -0.1], [0, 5]);
})->throws(MathException::class);
