<x-filament-panels::page>
    <meta http-equiv="refresh" content="300">
    <div class="get-started-container">
        <!-- Section 1: Welcome to Orbit -->
        <section class="get-started-section" id="welcome-section">
            <div class="section-background-container">
                <div class="content-wrapper">
                    <div class="title-image-container">
                        <div class="title-wrapper">
                            <h1 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                                Welcome to Orbit!
                            </h1>
                        </div>
                        <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 mt-4">
                        Orbit is where you explore Bitcoin's price and health. Look through the metrics, or combine them into a formula, set a threshold, and backtest how it would have performed against historical data.
                    </p>
                    <div class="checklist-container">
                        <div class="checklist-item {{ $this->getChecklistStatus()['get-started-page'] ? 'checked' : '' }}">
                            <input type="checkbox" {{ $this->getChecklistStatus()['get-started-page'] ? 'checked' : '' }} disabled>
                            <span class="checklist-text">Kick off the Get Started guide</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 2: Metrics -->
        <section class="get-started-section" id="metrics-section">
            <div class="section-background-container">
                <div class="content-wrapper">
                    <div class="title-image-container">
                        <div class="title-wrapper">
                            <h1 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                                Metrics
                            </h1>
                        </div>
                        <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"></path>
                        </svg>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 mt-4">
                        Metrics are at the core of Orbit. Think of them as indicators of Bitcoin's health and what can move its price. Just like a physician monitors heart rate, blood pressure, temperature and white blood cells, Orbit looks at data such as market sentiment, hashrate, exchange reserves, and many others. In the menu you’ll find the chart and detail for each metric.
                    </p>
                    <div class="checklist-container">
                        <div class="checklist-item {{ $this->getChecklistStatus()['metrics'] ? 'checked' : '' }}">
                            <input type="checkbox" {{ $this->getChecklistStatus()['metrics'] ? 'checked' : '' }} disabled>
                            <span class="checklist-text">Learn about different </span><a target="_blank" href="/app/metrics">Metrics / Indicators</a>
                        </div>
                        <div class="checklist-item {{ $this->getChecklistStatus()['alerts'] ? 'checked' : '' }}">
                            <input type="checkbox" {{ $this->getChecklistStatus()['alerts'] ? 'checked' : '' }} disabled>
                            <span class="checklist-text">Create <strong>Alerts</strong> for a Metric</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3: Time Series -->
        <section class="get-started-section" id="time-series-section">
            <div class="section-background-container">
                <div class="content-wrapper">
                    <div class="title-image-container">
                        <div class="title-wrapper">
                            <h1 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                                Time Series
                            </h1>
                        </div>
                        <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605"></path>
                        </svg>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 mt-4">
                        Time Series lets you go deeper into each metric's chart. See how these indicators have moved over weeks, months, or years, and how they relate to each other. Set a time range and search earlier history for a similar pattern.
                    </p>
                    <div class="checklist-container">
                        <div class="checklist-item {{ $this->getChecklistStatus()['time-series'] ? 'checked' : '' }}">
                            <input type="checkbox" {{ $this->getChecklistStatus()['time-series'] ? 'checked' : '' }} disabled>
                            <span class="checklist-text">Dig into Metrics' </span><a target="_blank" href="/app/time-series-page">Time Series</a><span class="checklist-text"> History</span>
                        </div>
                        <div class="checklist-item {{ $this->getChecklistStatus()['search-by-similarity'] ? 'checked' : '' }}">
                            <input type="checkbox" {{ $this->getChecklistStatus()['search-by-similarity'] ? 'checked' : '' }} disabled>
                            <span class="checklist-text">Search by <strong>similar time-bound variation</strong> of any Metrics</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 4: Signals -->
        <section class="get-started-section" id="signals-section">
            <div class="section-background-container">
                <div class="content-wrapper">
                    <div class="title-image-container">
                        <div class="title-wrapper">
                            <h1 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                                Signals
                            </h1>
                        </div>
                        <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12.75 19.5v-.75a7.5 7.5 0 0 0-7.5-7.5H4.5m0-6.75h.75c7.87 0 14.25 6.38 14.25 14.25v.75M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 mt-4">
                        A signal blends one or more metrics, each with its own weight, into a formula. Set a threshold, a time horizon, and whether a hit means buy or sell. Orbit replays that formula against historical data, and can email you when the combined daily score fires.
                    </p>
                    <div class="checklist-container">
                        <div class="checklist-item {{ $this->getChecklistStatus()['user-signals'] ? 'checked' : '' }}">
                            <input type="checkbox" {{ $this->getChecklistStatus()['user-signals'] ? 'checked' : '' }} disabled>
                            <span class="checklist-text">Create your first </span><a target="_blank" href="/app/user-signals/create?step=info">Signal</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 5: Performance -->
        <section class="get-started-section" id="performance-section">
            <div class="section-background-container">
                <div class="content-wrapper">
                    <div class="title-image-container">
                        <div class="title-wrapper">
                            <h1 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                                Performance
                            </h1>
                        </div>
                        <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"></path>
                        </svg>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 mt-4">
                        The Performance page shows how a signal would have held up. When the combined daily score crosses your threshold, Orbit triggers a simulated buy or sell against historical data, for a fixed USD amount in BTC. You can see what worked, what didn’t, and what to tune.
                    </p>
                    <div class="checklist-container">
                        <div class="checklist-item {{ $this->getChecklistStatus()['performance-page'] ? 'checked' : '' }}">
                            <input type="checkbox" {{ $this->getChecklistStatus()['performance-page'] ? 'checked' : '' }} disabled>
                            <span class="checklist-text">Analyze your Signal’s trends over time in the <a target="_blank" href="/app/performance-page">Performance Page</a></span>
                        </div>
                        <div class="checklist-item {{ $this->getChecklistStatus()['daily-signal'] ? 'checked' : '' }}">
                            <input type="checkbox" {{ $this->getChecklistStatus()['daily-signal'] ? 'checked' : '' }} disabled>
                            <span class="checklist-text">Check daily signals by clicking on each day on your </span><a target="_blank" href="/app/performance-page">Signal’s chart</a></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 6: Dashboard -->
        <section class="get-started-section" id="dashboard-section">
            <div class="section-background-container">
                <div class="content-wrapper">
                    <div class="title-image-container">
                        <div class="title-wrapper">
                            <h1 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                                Dashboard
                            </h1>
                        </div>
                        <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                    </div>
                    <p class="text-gray-900 dark:text-gray-300 mt-4">
                        The Dashboard keeps your latest alerts, signal performance, and Bitcoin metrics in one place.
                    </p>
                    <div class="checklist-container">
                        <div class="checklist-item {{ $this->getChecklistStatus()['dashboard'] ? 'checked' : '' }}">
                            <input type="checkbox" {{ $this->getChecklistStatus()['dashboard'] ? 'checked' : '' }} disabled>
                            <span class="checklist-text">Explore your </span><a target="_blank" href="/app">Dashboard</a><span class="checklist-text"> for alerts, signals, and metrics</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
