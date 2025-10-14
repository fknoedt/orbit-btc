<!-- Navbar Start -->
<nav class="navbar fixed top-0 w-full z-50 bg-transparent transition-all duration-500" id="navbar">
    <div class="container flex flex-wrap items-center justify-between">
        <a class="navbar-brand order-first" href="{{ url('/') }}">
            <span class="inline-block dark:hidden">
                <img src="{{ asset('assets/images/logo-dark.png') }}" class="l-dark" alt="">
                <img src="{{ asset('assets/images/logo-light.png') }}" class="l-light" alt="">
            </span>
            <img src="{{ asset('assets/images/logo-light.png') }}" class="hidden dark:inline-block" alt="">
        </a>
        <div class="navigation flex-1 order-2 hidden lg:flex justify-center" id="menu-collapse">
            <ul class="navbar-nav nav-light flex space-x-6" id="navbar-navlist">
                <li class="nav-item"><a class="nav-link active text-white hover:text-orange-600" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link text-white hover:text-orange-600" href="#contact">Contact us</a></li>
                <li class="nav-item"><a class="nav-link text-white hover:text-orange-600" href="/app/login">Log In</a></li>
                <li class="nav-item"><a class="nav-link text-white hover:text-orange-600" href="/app/register">Sign Up</a></li>
            </ul>
        </div>
        <div class="nav-icons flex items-center order-3 ms-auto">
            <ul class="list-none menu-social mb-0 flex space-x-2">
                <li class="inline">
                    <a href="https://github.com" target="_blank">
                        <span class="login-btn-primary"><span class="btn btn-sm btn-icon rounded-full bg-orange-600 hover:bg-orange-700 border-orange-600 hover:border-orange-700 text-white"><i class="uil uil-github"></i></span></span>
                    </a>
                </li>
                <li class="inline">
                    <a href="https://twitter.com" target="_blank">
                        <span class="login-btn-primary"><span class="btn btn-sm btn-icon rounded-full bg-orange-600 hover:bg-orange-700 border-orange-600 hover:border-orange-700 text-white"><i class="uil uil-twitter"></i></span></span>
                    </a>
                </li>
                <li class="inline">
                    <a href="https://instagram.com" target="_blank">
                        <span class="login-btn-primary"><span class="btn btn-sm btn-icon rounded-full bg-orange-600 hover:bg-orange-700 border-orange-600 hover:border-orange-700 text-white"><i class="uil uil-instagram"></i></span></span>
                    </a>
                </li>
            </ul>
            <button data-collapse="menu-collapse" type="button" class="collapse-btn inline-flex items-center ms-3 text-white dark:text-white lg_992:hidden" aria-controls="menu-collapse" aria-expanded="false">
                <span class="sr-only">Navigation Menu</span>
                <i class="mdi mdi-menu mdi-24px"></i>
            </button>
        </div>
    </div>
</nav>
<!-- Navbar End -->
