<?php

use App\Services\DailyPriceService;

it('refuses getDailyPrice before prices are loaded', function () {
    (new DailyPriceService)->getDailyPrice('2024-01-01');
})->throws(BadMethodCallException::class);
