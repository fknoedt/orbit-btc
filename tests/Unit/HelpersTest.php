<?php

use App\Helpers\BitcoinHelper;
use App\Helpers\NumberHelper;

it('maps genesis block height to 2009-01-03', function () {
    expect(BitcoinHelper::blockHeightToDate(0))->toBe('2009-01-03');
});

it('rejects dates before genesis', function () {
    BitcoinHelper::dateToBlockHeight('2009-01-02');
})->throws(InvalidArgumentException::class);

it('counts integer digits in a float', function (float $number, int $magnitude) {
    expect(NumberHelper::getFloatMagnitude($number))->toBe($magnitude);
})->with([
    [3.7, 1],
    [9.0, 1],
    [19.344, 2],
    [421.5, 3],
    [152555.13, 6],
    [-19.3, 2],
]);
