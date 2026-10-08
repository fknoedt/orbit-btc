<?php

use App\Adapters\AdapterFactory;
use Tests\TestCase;

uses(TestCase::class);

it('rejects an unknown adapter name', function () {
    AdapterFactory::getAdapter('NotARealApi');
})->throws(InvalidArgumentException::class);
