<?php

use App\Services\LightningService;

it('rejects a malformed lnd host', function () {
    (new LightningService)->setHost('not a host');
})->throws(Exception::class, 'Invalid lnd host');

it('rejects a missing macaroon file', function () {
    (new LightningService)->loadMacaroon('/tmp/orbit-btc-missing.macaroon');
})->throws(Exception::class, 'Macaroon not found');

it('rejects a missing tls certificate', function () {
    (new LightningService)->loadTlsCert('/tmp/orbit-btc-missing.cert');
})->throws(Exception::class, 'TLS Certificate not found');
