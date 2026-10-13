<?php

use Illuminate\Support\Facades\Mail;

it('renders the landing page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Craft your own signals')
        ->assertSee('Create a Free Account')
        ->assertSee('Try it for free now')
        ->assertSee('Send Message')
        ->assertSee('id="contactForm"', false)
        ->assertSee('id="home"', false)
        ->assertSee('id="about"', false)
        ->assertSee('id="how-it-works"', false)
        ->assertSee('id="contact"', false)
        ->assertSee('/app/register', false)
        ->assertSee('/app/login', false)
        ->assertSee('investor-inquiry', false);
});

it('renders the login page with the chart background', function () {
    $this->get('/app/login')
        ->assertOk()
        ->assertSee('orbit-login-bg', false)
        ->assertSee('login-chart-line', false);
});

it('renders the register page with the chart background', function () {
    $this->get('/app/register')
        ->assertOk()
        ->assertSee('orbit-login-bg', false)
        ->assertSee('login-chart-line', false);
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
