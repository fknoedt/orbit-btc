@extends('landing.layouts.app')

@section('content')
    @include('landing.partials.navbar.nav-light')

    <main>
        <section class="ol-hero" id="home">
            <div class="ol-hero__glow" aria-hidden="true"></div>
            <div class="ol-hero__glow--edge" aria-hidden="true"></div>
            <div class="ol-hero__chart" aria-hidden="true">
                @include('partials.orbit-chart', ['prefix' => 'ol'])
            </div>

            <div class="ol-wrap ol-hero__copy">
                <h1 class="ol-h1">Craft your own signals and level-up your <span class="ol-grad">Bitcoin game</span></h1>
                <p class="ol-lead">Orbit allows you to create and monitor bitcoin signals by combining, weighting, thresholding and backtesting on-chain metrics and market indicators.</p>
                <div class="ol-hero__actions">
                    <a class="ol-btn" href="/app/register">Create a Free Account</a>
                    <a class="ol-btn ol-btn--ghost" href="#how-it-works">See how it works</a>
                </div>
                <ul class="ol-checks">
                    <li>
                        <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                            <circle cx="9" cy="9" r="9" fill="rgba(61,214,140,0.16)"/>
                            <path d="M5 9.2l2.4 2.4L13 6.2" fill="none" stroke="#3dd68c" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Demo version
                    </li>
                    <li>
                        <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                            <circle cx="9" cy="9" r="9" fill="rgba(61,214,140,0.16)"/>
                            <path d="M5 9.2l2.4 2.4L13 6.2" fill="none" stroke="#3dd68c" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        6 months of free Pro Access
                    </li>
                </ul>
            </div>

            <div class="ol-wrap">
                <figure class="ol-frame">
                    <figcaption class="ol-frame__bar">
                        <span class="ol-frame__dots" aria-hidden="true"><span></span><span></span><span></span></span>
                        <span class="ol-frame__url">Orbit dashboard</span>
                    </figcaption>
                    <img src="{{ asset('assets/images/dashboard.png') }}" width="1439" height="742" alt="Orbit dashboard with bitcoin price, signals, and on-chain metrics">
                </figure>
            </div>
        </section>

        <section class="ol-section" id="about">
            <div class="ol-wrap ol-center">
                <p class="ol-kicker">What is Orbit BTC?</p>
                <h2 class="ol-h2">Bitcoin exploring and monitoring tool</h2>
                <p class="ol-lead ol-narrow">Indicators can show you a snapshot of one factor influencing what you're trying to track, but that's usually just one part of the puzzle. In fields like the stock market, networks, healthcare, climate and sports, indexes are created with composite indicators to offer a more holistic view of what is being tracked. Orbit is a tool to bring the same approach into the bitcoin ecosystem enabling anyone to explore, compose, backtest and monitor bitcoin-related metrics in a simple and objective way.</p>
            </div>
        </section>

        <section class="ol-section ol-section--alt" id="why">
            <div class="ol-wrap ol-split ol-why">
                <div class="ol-shot">
                    <img src="{{ asset('assets/images/dashboard-part.png') }}" width="828" height="754" alt="Signal score chart with daily bars and price">
                </div>
                <div>
                    <p class="ol-kicker">Why Orbit?</p>
                    <h2 class="ol-h2">Unique features and all the good indicators</h2>
                    <p class="ol-lead">Separating signal from noise on bitcoin's ecosystem doesn't mean checking lots of complex charts on multiple platforms every day, reading a long newsletter every week or scrolling through BTC brawls on X. Those are actually cool, but what if there's an objective yet flexible way to keep track of the indicators you want the way you want?</p>
                    <p class="ol-lead">Orbit lets you learn about, experiment and test relevant bitcoin-related indicators to build and track your own custom signals. From a single notification when your favorite on-chain metric varies above a custom threshold, to a combination of weighted metrics into a unique signal that you can back-test against historical data, track and share, all in a single and friendly platform.</p>
                    <a class="ol-btn" href="/app/register">Create a Free Account</a>
                </div>
            </div>
        </section>

        <section class="ol-section" id="how-it-works">
            <div class="ol-wrap">
                <div class="ol-center">
                    <p class="ol-kicker">How does it work?</p>
                    <h2 class="ol-h2">Craft, test and monitor your own Signals</h2>
                    <p class="ol-lead ol-narrow">Orbit is a tool for anyone who is interested in exploring bitcoin's ecosystem.</p>
                </div>

                <div class="ol-steps">
                    <article class="ol-step">
                        <div class="ol-step__icon">
                            <img src="{{ asset('assets/images/svg/explore.svg') }}" alt="" width="148" height="148">
                        </div>
                        <h3>Explore</h3>
                        <p>Orbit offers all the relevant on-chain metrics and market indicators for you to use in your signals. You can understand how they relate to bitcoin's price, explore their time-series and use tools like pattern matching to pick your favorite ones.</p>
                    </article>

                    <article class="ol-step">
                        <div class="ol-step__icon">
                            <img src="{{ asset('assets/images/svg/build.svg') }}" alt="" width="148" height="148">
                        </div>
                        <h3>Build</h3>
                        <p>Create your own signals based on weighted metrics, thresholds and time-horizon to simulate buy or sell operations whenever the threshold is hit.</p>
                    </article>

                    <article class="ol-step">
                        <div class="ol-step__icon">
                            <img class="is-wide" src="{{ asset('assets/images/svg/test.svg') }}" alt="" width="230" height="100">
                        </div>
                        <h3>Backtest</h3>
                        <p>Your signal is then tested against historical data and a score is given based on the buy or sell operations it triggers, which you can drill into.</p>
                    </article>

                    <article class="ol-step">
                        <div class="ol-step__icon">
                            <img src="{{ asset('assets/images/svg/monitor.svg') }}" alt="" width="148" height="148">
                        </div>
                        <h3>Monitor</h3>
                        <p>With your signals tuned, you can sit back and wait for notifications or keep an eye on its performance to tweak them further.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="ol-section ol-section--alt ol-cta" id="quote">
            <div class="ol-wrap">
                <p class="ol-kicker">How can Orbit BTC help me?</p>
                <h2 class="ol-h2">Predicting the market might be hard, but patterns tend to reoccur. Customize, backtest and automate your own Bitcoin Signals with Orbit.</h2>
                <a class="ol-btn" href="/app/register">Try it for free now</a>
            </div>
        </section>

        <section class="ol-section" id="contact">
            <div class="ol-wrap">
                <div class="ol-center">
                    <p class="ol-kicker">Contact us</p>
                    <h2 class="ol-h2">Get in touch</h2>
                    <p class="ol-lead ol-narrow">Feel free to hit us up with questions, feedback, feature requests or to discuss potential partnerships.</p>
                </div>

                <form class="ol-form" method="post" name="contactForm" id="contactForm" action="{{ route('investor-inquiry.store') }}">
                    @csrf
                    <p id="error-msg"></p>
                    <div id="simple-msg"></div>
                    <div class="ol-form__row">
                        <div class="ol-field-wrap">
                            <label class="ol-label" for="name">Name</label>
                            <input class="ol-field" name="name" id="name" type="text" autocomplete="name" placeholder="Your name">
                        </div>
                        <div class="ol-field-wrap">
                            <label class="ol-label" for="email">Email</label>
                            <input class="ol-field" name="email" id="email" type="email" autocomplete="email" placeholder="you@email.com">
                        </div>
                    </div>
                    <div class="ol-field-wrap">
                        <label class="ol-label" for="subject">Subject</label>
                        <input class="ol-field" name="subject" id="subject" type="text" placeholder="What is this about?">
                    </div>
                    <div class="ol-field-wrap">
                        <label class="ol-label" for="message">Message</label>
                        <textarea class="ol-field" name="message" id="message" placeholder="Tell us a bit more"></textarea>
                    </div>
                    <button class="ol-btn" type="submit" id="submit" name="send">Send Message</button>
                </form>
            </div>
        </section>
    </main>

    @include('landing.partials.footer')

    <a href="#home" onclick="topFunction()" id="back-to-top" class="ol-top hidden" aria-label="Back to top">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <path d="M9 14V4M9 4L4.5 8.5M9 4l4.5 4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>
@endsection

@section('scripts')
    <script>
        function topFunction() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateForm() {
            var name = document.forms["contactForm"]["name"].value;
            var email = document.forms["contactForm"]["email"].value;
            var subject = document.forms["contactForm"]["subject"].value;
            var message = document.forms["contactForm"]["message"].value;
            document.getElementById("error-msg").style.opacity = 0;
            document.getElementById('error-msg').innerHTML = "";
            if (name === "" || name == null) {
                document.getElementById('error-msg').innerHTML = "<div class='alert alert-warning error_message'>* Please enter a Name *</div>";
                fadeIn();
                return false;
            }
            if (email === "" || email == null) {
                document.getElementById('error-msg').innerHTML = "<div class='alert alert-warning error_message'>* Please enter an Email *</div>";
                fadeIn();
                return false;
            }
            if (subject === "" || subject == null) {
                document.getElementById('error-msg').innerHTML = "<div class='alert alert-warning error_message'>* Please enter a Subject *</div>";
                fadeIn();
                return false;
            }
            if (message === "" || message == null) {
                document.getElementById('error-msg').innerHTML = "<div class='alert alert-warning error_message'>* Please enter a message *</div>";
                fadeIn();
                return false;
            }
            return true;
        }

        const form = document.getElementById('contactForm');

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            const scrollY = window.scrollY;

            if (!validateForm()) {
                return;
            }

            const formData = new FormData(form);
            const notificationContainer = document.createElement('div');
            document.body.appendChild(notificationContainer);

            try {
                const response = await fetch('/investor-inquiry', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                window.scrollTo(0, scrollY);

                if (response.ok && data.status === 'success') {
                    notificationContainer.innerHTML = `<div class="notification success">${data.message || 'Thank you! Your inquiry has been submitted.'}</div>`;
                    form.reset();
                } else {
                    notificationContainer.innerHTML = `<div class="notification error">${data.message || 'Submission failed. Please try again.'}</div>`;
                }
            } catch (error) {
                console.error('Form submission error:', error);
                window.scrollTo(0, scrollY);
                notificationContainer.innerHTML = `<div class="notification error">An error occurred. Please try again.</div>`;
            }

            setTimeout(() => {
                notificationContainer.innerHTML = '';
            }, 5000);
        });

        function fadeIn() {
            var fade = document.getElementById("error-msg");
            var opacity = 0;
            var intervalID = setInterval(function () {
                if (opacity < 1) {
                    opacity = opacity + 0.5;
                    fade.style.opacity = opacity;
                } else {
                    clearInterval(intervalID);
                }
            }, 200);
        }

        function markNav() {
            var links = Array.prototype.slice.call(document.querySelectorAll('#navbar-navlist a'));
            var current = links[0];
            links.forEach(function (link) {
                var section = document.querySelector(link.getAttribute('href'));
                if (section && section.getBoundingClientRect().top <= 140) {
                    current = link;
                }
            });
            links.forEach(function (link) {
                link.parentElement.classList.toggle('active', link === current);
            });
        }

        markNav();
        window.addEventListener('scroll', markNav, { passive: true });

        document.querySelectorAll('[data-collapse]').forEach(function (button) {
            button.addEventListener('click', function () {
                setTimeout(function () {
                    var menu = document.getElementById(button.getAttribute('data-collapse'));
                    button.setAttribute('aria-expanded', menu && !menu.classList.contains('hidden') ? 'true' : 'false');
                }, 0);
            });
        });

        document.querySelectorAll('#menu-collapse a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    document.getElementById('menu-collapse').classList.add('hidden');
                    var burger = document.querySelector('.ol-burger');
                    if (burger) {
                        burger.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }
            var menu = document.getElementById('menu-collapse');
            if (!menu) {
                return;
            }
            menu.classList.add('hidden');
            var burger = document.querySelector('.ol-burger');
            if (burger) {
                burger.setAttribute('aria-expanded', 'false');
            }
        });
    </script>
@endsection
