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
