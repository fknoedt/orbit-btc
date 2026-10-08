<?php

it('rejects current-price without an api client token', function () {
    $this->getJson('/api/current-price')->assertForbidden();
});
