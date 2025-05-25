@php
    use Illuminate\Support\Number;
@endphp
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="fi min-h-screen">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Join Orbit BTC as an angel investor to fund a leading bitcoin analytics platform built by a veteran systems architect.">
    <meta name="keywords" content="angel investment, bitcoin, fintech, crypto analytics, Orbit BTC">
    <meta name="robots" content="noindex">
    <title>{{ $title }} - Orbit BTC</title>
    <link rel="icon" href="{{ asset('images/orbit-btc.ico') }}">
    @vite(['resources/css/filament.css', 'resources/css/app.css'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.css" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|segment:400&display=swap" rel="stylesheet" />
    <style>
        :root {
            --font-family: 'Inter';
            --primary-50: 255, 247, 237;
            --primary-100: 255, 237, 213;
            --primary-200: 254, 215, 170;
            --primary-300: 253, 186, 116;
            --primary-400: 251, 146, 60;
            --primary-500: 249, 115, 22;
            --primary-600: 234, 88, 12;
            --primary-700: 194, 65, 12;
            --primary-800: 154, 52, 18;
            --primary-900: 124, 45, 18;
            --primary-950: 67, 20, 7;
            --gray-50: 250, 250, 250;
            --gray-100: 244, 244, 245;
            --gray-200: 228, 228, 231;
            --gray-300: 212, 212, 216;
            --gray-400: 161, 161, 170;
            --gray-500: 113, 113, 122;
            --gray-600: 82, 82, 91;
            --gray-700: 63, 63, 70;
            --gray-800: 39, 39, 42;
            --gray-900: 24, 24, 27;
            --gray-950: 9, 9, 11;
        }
        [x-cloak=''], [x-cloak='x-cloak'], [x-cloak='1'] { display: none !important; }
        @media (max-width: 1023px) { [x-cloak='-lg'] { display: none !important; } }
        @media (min-width: 1024px) { [x-cloak='lg'] { display: none !important; } }
        .blockclock {
            font-family: 'Segment', monospace;
            color: #ffffff;
            font-weight: bold;
            background-color: #000000;
            border: 1px solid #f59e0b;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            display: inline-block;
        }
        .tooltip {
            position: relative;
        }
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 80px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 4px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -40px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        .tooltip.copied .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
            padding: 1rem;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .notification.success {
            background-color: #10b981;
            color: #ffffff;
        }
        .notification.error {
            background-color: #ef4444;
            color: #ffffff;
        }
    </style>
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'system';
            if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();

        // Share button functionality
        function copyUrl() {
            try {
                navigator.clipboard.write(window.location.href).then(() => {
                    const button = document.querySelector('button[onclick="copyUrl()"]');
                    button.classList.add('copied');
                    setTimeout(() => button.classList.remove('copied'), 2000);
                }).catch(err => {
                    console.error('Clipboard API error:', err);
                    // Fallback to execCommand for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = window.location.href;
                    document.body.appendChild(textArea);
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        const button = document.querySelector('button[onclick="copyUrl()"]');
                        button.classList.add('copied');
                        setTimeout(() => button.classList.remove('copied'), 2000);
                    } catch (fallbackErr) {
                        console.error('execCommand error:', fallbackErr);
                        alert('Failed to copy URL. Please copy it manually: ' + window.location.href);
                    }
                    document.body.removeChild(textArea);
                });
            } catch (err) {
                console.error('Clipboard access failed:', err);
                alert('Clipboard not supported. Please copy the URL manually: ' + window.location.href);
            }
        }
    </script>
</head>
<body class="fi-body fi-panel-admin min-h-screen font-normal antialiased">
<main class="fi-main mx-auto h-full w-full px-4 md:px-6 lg:px-8 max-w-full">
    <div class="fi-page">
        <section class="flex flex-col gap-y-8 py-8">
            <!-- Logo and Title -->
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/orbit-btc-large.png') }}" alt="Orbit BTC Logo" class="h-24 md:h-30">
                <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-primary-600 dark:text-primary-400 sm:text-3xl">
                    {{ $heading }}
                </h1>
            </div>

            <!-- What is Orbit BTC? -->
            <section class="fi-section">
                <h2 class="text-xl font-semibold text-primary-600 dark:text-primary-400 border-b-2 border-primary-500 pb-2 mb-4">
                    What is Orbit BTC?
                </h2>
                <div class="prose prose-gray dark:prose-invert text-left">
                    <p class="text-gray-700 dark:text-gray-300 mt-2 py-0.5">
                        Orbit is a bitcoin-only platform that delivers a unique way to create custom <strong>up/sell</strong> or <strong>down/buy</strong> <strong>signals</strong> based on one or multiple <strong>metrics</strong>, parameterized and thresholded. These <strong>signals</strong> are back-tested against on-chain and market historical data, and when triggered, simulated orders generate a performance score for your signal.
                    </p>
                    <p class="text-gray-700 dark:text-gray-300 mt-2 py-0.5">
                        By combining the power of top bitcoin and <em>TradFi</em> APIs, Orbit BTC is a tool for anyone interested in the health, indicators, and buzz of bitcoin’s ecosystem. It's a tool for newbies to learn, analysts to explore, and hodlers to strengthen their conviction.
                    </p>
                </div>
            </section>

            <!-- See it in Action -->
            <section class="fi-section">
                <h2 class="text-xl font-semibold text-primary-600 dark:text-primary-400 border-b-2 border-primary-500 pb-2 mb-4">
                    See it in Action
                </h2>
                <div class="mt-4 space-y-6">
                    <div class="bg-blue-50 dark:bg-blue-900/10 p-4 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300 font-semibold">Simple and friendly approach</p>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">
                            Beyond hardcore enthusiasts, few enjoy sifting through countless Glassnode charts. Orbit makes them concise and ready to use in your formulas.
                        </p>
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg">
                        <p class="text-gray-600 dark:text-gray-400">[section for a dashboard-widget I'm going to add]</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/10 p-4 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300 font-semibold">Level up your analysis</p>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">
                            Combine multiple metrics in your signals for a high-level overview with minimal effort.
                        </p>
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg">
                        <p class="text-gray-600 dark:text-gray-400">[section for a performance-page I'm going to embed here]</p>
                    </div>
                    <div class="bg-pink-50 dark:bg-pink-900/10 p-4 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300 font-semibold">Playground area</p>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">
                            Explore, combine, and search metric time-series by pattern using Dynamic Time Warping Distance.
                        </p>
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg">
                        <p class="text-gray-600 dark:text-gray-400">[section for time-series-page]</p>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/10 p-4 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300 font-semibold">Avoid the noise</p>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">
                            Orbit BTC lets you know when your signals trigger and how simulated stakes perform over time.
                        </p>
                    </div>
                    <img src="{{ asset('images/angels-and-partnerships/daily-performance.png') }}" alt="Daily Performance" class="max-w-lg h-auto rounded-lg">
                    <div class="bg-purple-50 dark:bg-purple-900/10 p-4 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300 font-semibold">Bitcoin-only</p>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">
                            Because there’s no second best.
                        </p>
                    </div>
                    <img src="{{ asset('images/angels-and-partnerships/bitcoin-only.png') }}" alt="Bitcoin Only" class="max-w-lg h-auto rounded-lg">
                </div>
            </section>

            <!-- Infra and Data Structures -->
            <section class="fi-section">
                <h2 class="text-xl font-semibold text-primary-600 dark:text-primary-400 border-b-2 border-primary-500 pb-2 mb-4">
                    Infra and Data Structures
                </h2>
                <div class="prose prose-gray dark:prose-invert text-left">
                    <p class="text-gray-600 dark:text-gray-400 mt-2 py-0.5">
                        Designed to fetch and standardize data from any API, structure and process them efficiently, Orbit can orchestrate a wide range of complex operations between multiple data sources. Built on top of a reputable, robust and popular framework, hosted at a tailored ready-to-scale service, Orbit is committed to dev-ops and development best-practices to make the path to a successful start-up a little smoother.
                    </p>
                    <img src="{{ asset('images/angels-and-partnerships/infra-structure.png') }}" alt="Infra Structure" class="max-w-2xl h-auto rounded-lg mt-4">
                </div>
            </section>

            <!-- Potential Power-Ups -->
            <section class="fi-section">
                <h2 class="text-xl font-semibold text-primary-600 dark:text-primary-400 border-b-2 border-primary-500 pb-2 mb-4">
                    Potential Power-Ups
                </h2>
                <div class="prose prose-gray dark:prose-invert text-left">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-2">AI Plug-Ins</h3>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 py-0.5">
                        Orbit BTC can integrate AI tools to elevate user experience and analytics precision. AI-driven contextual help and smart walk-throughs guide users through signal creation and metric exploration, making the platform intuitive for newbies and hodlers alike. On-chain and market AI advisors/assistants can provide real-time insights, recommending optimal metrics and thresholds based on historical and live data.
                    </p>
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-2">Bet on your Signals</h3>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 py-0.5">
                        With user adoption and Lightning x stablecoins integration, Orbit can be enhanced to offer a next-level DCA experience by allowing users to use their tested signals to buy or sell bitcoin at the right time. Given betting houses worldwide popularity and people's common appetite for yields, Orbit can be a tool for informed and risk-aware short-time speculation over any BTC metric. What if small scale / micro betting turns out to be exciting, informative and profitable?
                    </p>
                </div>
            </section>

            <!-- Meet the Founder -->
            <section class="fi-section">
                <h2 class="text-xl font-semibold text-primary-600 dark:text-primary-400 border-b-2 border-primary-500 pb-2 mb-4">
                    Meet the Founder
                </h2>
                <div class="prose prose-gray dark:prose-invert text-left">
                    <div class="flex items-center gap-4 mb-4">
                        <img src="{{ asset('images/angels-and-partnerships/filipe-knoedt.jpeg') }}" alt="Filipe Knoedt" class="w-44 h-44 rounded-full">
                        <div class="flex gap-2">
                            <a href="https://x.com/fknoedt" target="_blank" class="text-primary-600 dark:text-primary-400">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                            </a>
                            <a href="https://github.com/fknoedt" target="_blank" class="text-primary-600 dark:text-primary-400">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 mt-2 py-0.5">
                        My name is Filipe Knoedt, a Systems Architect based in North Carolina, USA. Data structures have been my passion since college, and for over two decades, I’ve built and optimized solutions for corporate, e-commerce, marketing, ads, GIS, logistics, and education sectors.
                    </p>
                    <p class="text-gray-700 dark:text-gray-300 mt-2 py-0.5">
                        Around 2005, I began studying economics, reading articles and books on inflation, gold vs. fiat standards, and free markets, and watching Peter Schiff predict the 2008 crash on YouTube. A few years later, I launched my first startup, a subscription-based CRM and ERP SaaS. This foundation fueled my enthusiasm for bitcoin, which I first explored in 2013, though I still had to get wrecked with 💩coins before becoming a maxi.
                    </p>
                    <p class="text-gray-700 dark:text-gray-300 mt-2 py-0.5">
                        This year, I left a rewarding career to focus on building bitcoin and Lightning Network products. My mission is to apply my skills, expertise, and passion to bitcoin, the best money ever invented, during its historic monetization phase. Fix the Money, Fix the World.
                    </p>
                </div>
            </section>

            <!-- Angel Investment -->
            <section class="fi-section">
                <h2 class="text-xl font-semibold text-primary-600 dark:text-primary-400 border-b-2 border-primary-500 pb-2 mb-4">
                    Angel Investment
                </h2>
                <div class="prose prose-gray dark:prose-invert text-left">
                    <p class="text-gray-600 dark:text-gray-400 mt-2 py-0.5">
                        Bitcoin’s recent and potential growth in adoption, price, and popularity creates an ideal moment to launch a bitcoin-only tool with unique features. I’m confident that a small, product-focused team, which I’m prepared to lead, combined with outsourced services, can transform Orbit into a successful startup quickly.
                    </p>
                    <img src="{{ asset('images/angels-and-partnerships/trello-dashboard.png') }}" alt="Trello Dashboard" class="max-w-2xl h-auto rounded-lg mt-4">
                </div>
            </section>

            <!-- Partnerships -->
            <section class="fi-section">
                <h2 class="text-xl font-semibold text-primary-600 dark:text-primary-400 border-b-2 border-primary-500 pb-2 mb-4">
                    Partnerships
                </h2>
                <div class="prose prose-gray dark:prose-invert text-left">
                    <p class="text-gray-600 dark:text-gray-400 mt-2 py-0.5">
                        If you’re skilled in finance, data visualization, product development, or web development, understand the significance of 1971, and are interested in partnering on this project, reach out.
                    </p>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 py-0.5">
                        I’m eager to explore collaboration opportunities and potentially find a co-founder for this venture.
                    </p>
                </div>
            </section>

            <section class="fi-section">
                <h2 class="text-xl font-semibold text-primary-600 dark:text-primary-400 border-b-2 border-primary-500 pb-2 mb-4">
                    Get in Touch
                </h2>
                <!-- Other content -->
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-primary-600 dark:text-primary-400 border-b-2 border-primary-500 pb-2 mb-4">
                        Contact Form
                    </h3>
                    <form id="investor-inquiry-form" method="POST" action="/investor-inquiry" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input type="text" name="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Interest</label>
                            <select name="interest" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <option value="invest">Angel Investment</option>
                                <option value="partner">Partnership</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                            <textarea name="message" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300" rows="4"></textarea>
                        </div>
                        <button type="submit" class="bg-primary-500 text-white px-4 py-2 rounded hover:bg-primary-600">Submit</button>
                    </form>
                </div>
            </section>

            <!-- Explore More -->
            <section class="fi-section">
                <h2 class="text-xl font-semibold text-primary-600 dark:text-primary-400 border-b-2 border-primary-500 pb-2 mb-4">
                    Explore More
                </h2>
                <div class="prose prose-gray dark:prose-invert text-left">
                    <p class="text-gray-600 dark:text-gray-400 mt-2 py-0.5">
                        Create a user to explore Orbit's demo website here: <a href="/admin" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">https://orbitbtc.space</a>
                    </p>
                </div>
            </section>
        </section>
        <!-- Footer with Page View Counter -->
        <footer class="mt-8 py-4 text-center text-gray-600 dark:text-gray-400">
            <p>This page has been viewed by <span class="blockclock">{{ \App\Models\PageView::where('page_url', 'angels-and-partners')->count() }}</span> different IPs</p>
        </footer>
    </div>
</main>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById('investor-inquiry-form');
            if (form) {
                form.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    event.stopPropagation();
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

                        if (response.ok && data.status === 'success') {
                            notificationContainer.innerHTML = `<div class="notification success">${data.message}</div>`;
                            form.reset();
                        } else {
                            notificationContainer.innerHTML = `<div class="notification error">${data.message || 'Submission failed. Please try again.'}</div>`;
                        }
                    } catch (error) {
                        console.error('Form submission error:', error);
                        notificationContainer.innerHTML = `<div class="notification error">An error occurred. Please try again.</div>`;
                    }

                    setTimeout(() => notificationContainer.remove(), 5000);
                });
            }
        });
    </script>
@endpush
</body>
</html>
