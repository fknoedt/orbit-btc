<div class="orbit-btc-logo-wrapper {{ request()->routeIs(['filament.app.auth.login', 'filament.app.auth.register']) ? 'login-page' : 'main-page' }}">
    <img
        src="{{ asset(request()->routeIs(['filament.app.auth.login', 'filament.app.auth.register']) ? 'images/orbit-btc-large.png' : 'images/orbit-btc-header.png') }}"
        alt="Orbit BTC Logo"
        class="orbit-btc-logo"
    >
</div>
