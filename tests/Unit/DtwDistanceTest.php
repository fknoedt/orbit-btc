<?php

use App\Exceptions\MathException;
use App\Math\DtwDistance;

it('returns zero for identical series', function () {
    $dtw = new DtwDistance;

    expect($dtw->distance([1, 2, 3], [1, 2, 3]))->toBe(0.0);
});

it('rejects empty or unequal series', function (array $left, array $right) {
    (new DtwDistance)->distance($left, $right);
})->with([
    [[], []],
    [[1, 2], [1]],
])->throws(MathException::class);

it('z-scores a constant series to zeros', function () {
    expect((new DtwDistance)->normalizeSeries([5, 5, 5]))->toBe([0.0, 0.0, 0.0]);
});

it('matches the squared difference cost of a short pair', function () {
    expect((new DtwDistance)->distance([1, 2], [1, 4]))->toBe(2.0);
});

it('returns the distance when it is still within the cap', function () {
    $dtw = new DtwDistance;
    $full = $dtw->distance([0, 1, 0, 1, 0], [1, 0, 1, 0, 1]);

    expect($full)->toBeGreaterThan(0)
        ->and($dtw->distance([0, 1, 0, 1, 0], [1, 0, 1, 0, 1], null, false, $full))->toBe($full)
        ->and($dtw->distance([0, 1, 0, 1, 0], [1, 0, 1, 0, 1], null, false, $full + 1))->toBe($full);
});

it('stops once every alignment is already worse than the cap', function () {
    expect((new DtwDistance)->distance([0, 5], [5, 0], null, false, 0.0))->toBeNull();
});

it('still returns zero for an identical series when the cap is zero', function () {
    expect((new DtwDistance)->distance([1, 2, 3], [1, 2, 3], null, false, 0.0))->toBe(0.0);
});
