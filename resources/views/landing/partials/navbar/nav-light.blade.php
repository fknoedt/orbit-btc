<header>
    <nav class="ol-nav" id="navbar">
        <div class="ol-wrap ol-nav__inner">
            <a class="ol-logo" href="{{ url('/') }}">
                <img src="{{ asset('assets/images/logo-light.png') }}" width="160" height="24" alt="Orbit BTC">
            </a>

            <div class="ol-menu hidden" id="menu-collapse">
                <ul id="navbar-navlist">
                    <li class="active"><a href="#home">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#how-it-works">How it Works</a></li>
                    <li><a href="#contact">Contact us</a></li>
                </ul>
                <a class="ol-menu-login" href="/app/login">Log in</a>
            </div>

            <div class="ol-nav__actions">
                <a class="ol-login" href="/app/login">Log in</a>
                <a class="ol-btn ol-btn--sm" href="/app/register">Sign up</a>
                <button class="ol-burger" type="button" data-collapse="menu-collapse" aria-controls="menu-collapse" aria-expanded="false" aria-label="Navigation Menu">
                    <svg class="ol-burger__open" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M3 5h14M3 10h14M3 15h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    <svg class="ol-burger__close" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
        </div>
    </nav>
</header>
