<div class="orbit-btc-logo-wrapper {{ request()->routeIs(['filament.admin.auth.login', 'filament.admin.auth.register']) ? 'login-page' : 'main-page' }}">
    <img
        src="{{ asset(request()->routeIs(['filament.admin.auth.login', 'filament.admin.auth.register']) ? 'images/orbit-btc-large.png' : 'images/orbit-btc-header.png') }}"
        alt="Orbit BTC Logo"
        class="orbit-btc-logo"
    >
</div>
