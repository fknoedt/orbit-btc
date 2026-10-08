<?php

use Illuminate\Support\Facades\Mail;

it('renders the landing page', function () {
    $this->get('/')->assertOk();
});

it('rejects an incomplete investor inquiry', function () {
    $this->postJson('/investor-inquiry', [
        'name' => 'Ada',
    ])->assertStatus(422)->assertJsonPath('status', 'error');
});

it('accepts a valid investor inquiry', function () {
    Mail::fake();

    $this->postJson('/investor-inquiry', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'subject' => 'invest',
        'message' => 'Interested in partnering.',
    ])->assertOk()->assertJsonPath('status', 'success');
});
