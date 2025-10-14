@extends('landing.layouts.app')

@section('content')
    @include('landing.partials.navbar.nav-light')

    <!-- Hero Start -->
    <section class="py-36 lg:py-64 w-full table relative bg-no-repeat bg-center bg-cover active" id="home" style="background-image: url('/assets/images/bg/satellite-waves.jpg');">
        <div class="container relative z-1">
            <div class="grid grid-cols-1 mt-12">
                <h4 class="text-white lg:text-5xl text-4xl lg:leading-normal leading-normal font-medium mb-7 position-relative z-10">Craft your own signals and <br> level-up your bitcoin game</h4>
                <p class="text-white opacity-80 mb-0 max-w-2xl text-lg z-10">Orbit allows you to create and monitor bitcoin signals by combining, weighting, thresholding and backtesting on-chain metrics and market indicators.</p>
                <div class="relative mt-10 z-10">
                    <a href="/app/register" class="btn bg-orange-600 hover:bg-orange-700 border-orange-600 hover:border-orange-700 text-white rounded-md">Create a Free Account</a>
                </div>
            </div>
        </div><!--end container-->
        <div class="absolute lg:w-1/2 w-full h-full bg-gradient-to-t from-orange-400 to-orange-600 top-0 z-0"></div>
    </section><!--end section-->
    <!-- Hero End -->

    <!-- What is Orbit -->
    <section class="relative md:py-24 py-16" id="features">
        <div class="container md:mt-24 mt-8">
            <div class="container mx-auto text-center">
                <h6 class="text-orange-600 text-base font-medium uppercase mb-2">What is Orbit BTC ?</h6>
                <h3 class="mb-4 md:text-2xl text-xl font-medium dark:text-white">Bitcoin exploring and monitoring tool</h3>
                <p class="text-slate-400 max-w-xl mx-auto">Indicators can show you a snapshot of one factor influencing what you're trying to track, but that's usually just one part of the puzzle.
                    In fields like the stock market, networks, medicine and climate, indexes are created with composite indicators to offer a more holistic view of what is being tracked. Orbit is a tool to bring the same approach into the bitcoin ecosystem enabling anyone to explore, compose, backtest and monitor bitcoin-related metrics in a simple and objective way.  </p>
            </div><!--end grid-->
        </div>
    </section>
    <!-- End What is Orbit -->

    <!-- Why Orbit -->
    <section class="relative md:py-24 py-16 bg-gray-50 dark:bg-slate-800 z-10" id="about">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-12 md:grid-cols-2 gap-10 items-center">
                <div class="lg:col-span-5">
                    <div class="relative">
                        <img src="{{ asset('assets/images/dashboard-part.png') }}" class="group-hover:origin-center group-hover:scale-105 transition duration-500" alt="">
                        <!--img src="{{ asset('assets/images/dashboard.png') }}" class="rounded-lg shadow-lg relative" alt=""-->
                        <!--div class="absolute bottom-2/4 translate-y-2/4 start-0 end-0 text-center">
                            <a href="#!" data-type="youtube" data-id="S_CGed6E610" class="lightbox size-20 rounded-full shadow-lg shadow-slate-100 dark:shadow-slate-800 inline-flex items-center justify-center bg-white dark:bg-slate-900 text-orange-600">
                                <i class="mdi mdi-play inline-flex items-center justify-center text-2xl"></i>
                            </a>
                        </div-->
                    </div>
                </div>
                <div class="lg:col-span-7">
                    <div class="lg:ms-7">
                        <h4 class="text-orange-600 text-base font-medium uppercase mb-2">Why Orbit ?</h4>
                        <h3 class="mb-4 md:text-2xl text-xl font-medium dark:text-white">Unique features and all the good indicators</h3>
                        <p class="text-slate-400 max-w-2xl">Separating signal from noise on bitcoin's ecosystem doesn't mean checking lots of complex charts on multiple platforms every day, reading a long newsletter every week or scrolling through BTC brawls on X. Those are actually cool, but what if there's an objective yet flexible way to keep track of the indicators you want the way you want?</p>
                        <p class="text-slate-400 max-w-2xl mt-4">Orbit let's you learn about, experiment and test relevant bitcoin-related indicators to build and track your own custom signals. From a single notification when your favorite on-chain metric varies above a custom threshold, to a combination of weighted metrics into a unique signal that you can back-test against historical data, track and share, all in a single and friendly platform. </p>
                        <div class="relative mt-10">
                            <a href="/app/register" class="btn bg-orange-600 hover:bg-orange-700 border-orange-600 hover:border-orange-700 text-white rounded-md">Create a Free Account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- End About Section -->

    <!-- Features Section -->
    <!-- Start -->
    {{--<section class="relative md:py-24 py-8" id="features">
        <div class="container md:mt-24 mt-8">
            <div class="grid grid-cols-1 pb-8 text-center">
                <h4 class="text-orange-600 text-base font-medium uppercase mb-2">How does it work ?</h4>
                <h3 class="mb-4 md:text-2xl text-xl font-medium dark:text-white">Bitcoin-only exploring and monitoring tool</h3>

                <p class="text-slate-400 max-w-xl mx-auto">Launch your campaign and benefit from our expertise on designing and managing conversion centered Tailwind CSS html page.</p>
            </div><!--end grid-->

            <div class="grid grid-cols-1 mt-8">
                <div class="timeline relative">
                    <div class="timeline-item">
                        <div class="grid sm:grid-cols-2">
                            <div class="">
                                <div class="duration date-label-left ltr:float-right rtl:float-left md:me-7 relative">
                                    <img src="assets/images/svg/design-thinking.svg" class="size-64" alt="">
                                </div>
                            </div><!--end col-->
                            <div class="mt-4 md:mt-0">
                                <div class="event event-description-right ltr:float-left rtl:float-right ltr:text-left rtl:text-right md:ms-7">
                                    <h5 class="text-lg dark:text-white mb-1 font-medium">Explore</h5>
                                    <p class="timeline-subtitle mt-3 mb-0 text-slate-400">The generated injected humour, or non-characteristic words etc. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis,</p>
                                </div>
                            </div><!--end col-->
                        </div><!--end grid-->
                    </div><!--end timeline item-->

                    <div class="timeline-item mt-5 pt-4">
                        <div class="grid sm:grid-cols-2">
                            <div class="md:order-1 order-2">
                                <div class="event event-description-left ltr:float-left rtl:float-right ltr:text-right rtl:text-left md:me-7">
                                    <h5 class="text-lg dark:text-white mb-1 font-medium">Backtest</h5>
                                    <p class="timeline-subtitle mt-3 mb-0 text-slate-400">The generated injected humour, or non-characteristic words etc. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis,</p>
                                </div>
                            </div><!--end col-->
                            <div class="md:order-2 order-1">
                                <div class="duration duration-right md:ms-7 relative">
                                    <img src="assets/images/svg/coding.svg" class="size-64" alt="">
                                </div>
                            </div><!--end col-->
                        </div><!--end grid-->
                    </div><!--end timeline item-->

                    <div class="timeline-item mt-5 pt-4">
                        <div class="grid sm:grid-cols-2">
                            <div class="mt-4 mt-sm-0">
                                <div class="duration date-label-left ltr:float-right rtl:float-left md:me-7 relative">
                                    <img src="assets/images/svg/office-desk.svg" class="size-64" alt="">
                                </div>
                            </div><!--end col-->
                            <div class="mt-4 mt-sm-0">
                                <div class="event event-description-right ltr:float-left rtl:float-right ltr:text-left rtl:text-right md:ms-7">
                                    <h5 class="text-lg dark:text-white mb-1 font-medium">Monitor</h5>
                                    <p class="timeline-subtitle mt-3 mb-0 text-slate-400">The generated injected humour, or non-characteristic words etc. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis,</p>
                                </div>
                            </div><!--end col-->
                        </div><!--end grid-->
                    </div><!--end timeline item-->
                </div>
            </div>
        </div><!--end container-->
    </section><!--end section-->
    <!-- End -->--}}

    <!-- Start -->
    {{--<section class="py-24 w-full table relative bg-[url('../../assets/images/bg/cta.png')] bg-center bg-cover">
        <div class="absolute inset-0 bg-black opacity-80"></div>
        <div class="container relative">
            <div class="grid grid-cols-1 pb-8 text-center">
                <h3 class="mb-6 md:text-3xl text-2xl text-white font-medium">Ready to start your next web project now?</h3>

                <p class="text-white opacity-50 max-w-xl mx-auto">Launch your campaign and benefit from our expertise on designing and managing conversion centered Tailwind CSS html page.</p>

                <div class="relative mt-10">
                    <a href="/app/register" class="btn bg-orange-600 hover:bg-orange-700 border-orange-600 hover:border-orange-700 text-white rounded-md">Create a Free Account</a>
                </div>
            </div><!--end grid-->
        </div><!--end container-->
    </section><!--end section-->
    --}}
    <!-- End -->

    <!-- Start -->
    <section class="relative md:py-24 py-8" id="contact">
        <div class="container">
            <div class="grid grid-cols-1 pb-8 text-center">
                <h6 class="text-orange-600 text-base font-medium uppercase mb-2">Contact us</h6>
                <h3 class="mb-4 md:text-2xl text-xl font-medium dark:text-white">Get in touch</h3>
                <p class="text-slate-400 max-w-xl mx-auto">Feel free to hit us up with questions, feedback, feature requests or to discuss potential partnerships.</p>
            </div><!--end grid-->

            <div class="grid grid-cols-1 lg:grid-cols-8 md:grid-cols-2 items-center gap-6">
                <div class="lg:col-span-8">
                    <div class="p-6 rounded-md shadow-sm bg-white dark:bg-slate-900">
                        <form method="post" name="contactForm" id="contactForm" action="/investor-inquiry">
                            <p class="mb-0" id="error-msg"></p>
                            <div id="simple-msg"></div>
                            <div class="grid lg:grid-cols-12 lg:gap-6">
                                <div class="lg:col-span-6 mb-5">
                                    <input name="name" id="name" type="text" class="form-input w-full py-2 px-3 border border-gray-200 dark:border-gray-800 focus:ring-0 focus:border-orange-600/50 dark:bg-slate-500 dark:text-slate-200 rounded h-10 outline-none" placeholder="Name">
                                </div>

                                <div class="lg:col-span-6 mb-5">
                                    <input name="email" id="email" type="email" class="form-input w-full py-2 px-3 border border-gray-200 dark:border-gray-800 focus:ring-0 focus:border-orange-600/50 dark:bg-slate-500 dark:text-slate-200 rounded h-10 outline-none" placeholder="Email">
                                </div><!--end col-->
                            </div>

                            <div class="grid grid-cols-1">
                                <div class="mb-5">
                                    <input name="subject" id="subject" class="form-input w-full py-2 px-3 border border-gray-200 dark:border-gray-800 focus:ring-0 focus:border-orange-600/50 dark:bg-slate-500 dark:text-slate-200 rounded h-10 outline-none" placeholder="Subject">
                                </div>

                                <div class="mb-5">
                                    <textarea name="message" id="message" class="form-input w-full py-2 px-3 border border-gray-200 dark:border-gray-800 focus:ring-0 focus:border-orange-600/50 dark:bg-slate-500 dark:text-slate-200 rounded h-28 outline-none textarea" placeholder="Message"></textarea>
                                </div>
                            </div>
                            <button type="submit" id="submit" name="send" class="btn bg-orange-600 hover:bg-orange-700 border-orange-600 hover:border-orange-700 text-white rounded-md h-11 justify-center flex items-center">Send Message</button>
                        </form>
                    </div>
                </div>
            </div><!--end grid-->
        </div><!--end container-->
    </section><!--end section-->
    <!-- End -->

    @include('landing.partials.footer')

    <!-- Back to Top -->
    <a href="#" onclick="topFunction()" id="back-to-top" class="back-to-top fixed hidden text-lg rounded-full z-10 bottom-5 right-5 size-8 text-center bg-orange-600 text-white leading-8">
        <i class="mdi mdi-arrow-up"></i>
    </a>

    @include('landing.partials.switcher')
@endsection

@section('scripts')
    <script>
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
    </script>
@endsection
