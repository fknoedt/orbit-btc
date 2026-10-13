@php
    $bitcoinQuotes = [
        'Fix the money, fix the world',
        'Not your keys, not your coins',
        'No man should work for what another man can print',
        'Be your own bank',
        'Bitcoin fixes this',
        'Don\'t trust, verify',
        'When in doubt, zoom out',
        'Everyone gets Bitcoin at the price they deserve',
        'Stay humble, stack sats',
        'There is no second best',
        'Trusted third parties are security holes',
    ];
    $randomQuote = $bitcoinQuotes[array_rand($bitcoinQuotes)];
@endphp

<footer class="ol-footer">
    <div class="ol-wrap">
        <div class="ol-footer__grid">
            <a class="ol-logo" href="{{ url('/') }}">
                <img src="{{ asset('assets/images/logo-light.png') }}" width="160" height="24" alt="Orbit BTC">
            </a>
            <p class="ol-quote">{{ $randomQuote }}
                <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="12" fill="#f54900"/>
                    <path fill="#fff" d="M16.1 10.55c.25-1.62-.99-2.49-2.68-3.07l.55-2.19-1.34-.33-.53 2.13c-.35-.09-.71-.17-1.07-.25l.54-2.15-1.34-.33-.55 2.19c-.29-.07-.57-.13-.85-.2l-1.84-.46-.36 1.43s.99.23.97.24c.54.14.64.49.62.78l-.62 2.5c.04.01.09.02.14.05l-.14-.04-.87 3.5c-.07.16-.23.41-.61.32.01.02-.97-.24-.97-.24l-.66 1.53 1.74.43c.32.08.64.16.95.23l-.55 2.22 1.34.33.55-2.2c.36.1.72.19 1.06.27l-.55 2.19 1.34.33.55-2.22c2.24.42 3.92.25 4.63-1.77.57-1.63-.03-2.57-1.2-3.18.86-.2 1.5-.76 1.67-1.93zm-2.99 4.19c-.41 1.63-3.16.75-4.05.53l.72-2.9c.89.22 3.75.66 3.33 2.37zm.41-4.21c-.37 1.49-2.66.73-3.4.55l.66-2.63c.74.18 3.12.53 2.74 2.08z"/>
                </svg>
            </p>
            <span></span>
        </div>
        <nav class="ol-footer__links" aria-label="Footer">
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#how-it-works">How it Works</a>
            <a href="#contact">Contact us</a>
            <a href="/app/login">Log in</a>
            <a href="/app/register">Sign up</a>
        </nav>
    </div>
</footer>
