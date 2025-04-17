<!-- resources/views/filament/pages/get-started-page.blade.php -->
<x-filament-panels::page>
    <div class="get-started-container">
        <!-- Section 1: Welcome to Orbit -->
        <section class="get-started-section" id="welcome-section">
            <div class="content-wrapper">
                <div class="title-image-container">
                    <h1 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        You are in Orbit
                    </h1>
                    <img src="https://placehold.co/300x200?text=IMG+01" alt="Welcome to Orbit" class="rounded-lg shadow-md">
                </div>
                <p class="text-gray-700 dark:text-gray-300 mt-6">
                    Orbit BTC is here to help you navigate the bitcoin market with clarity and peace of mind. Whether you’re looking to explore key metrics or build your own custom models, this platform offers a straightforward way to gain insights, test ideas, and make informed decisions. With Orbit, you can monitor market indicators, experiment with strategies, and track their performance over time as you deepen your understanding of bitcoin’s movements.
                </p>
            </div>
        </section>

        <!-- Section 2: Metrics -->
        <section class="get-started-section" id="metrics-section">
            <div class="content-wrapper">
                <div class="title-image-container">
                    <h1 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        Metrics
                    </h1>
                    <img src="https://placehold.co/300x200?text=IMG+02" alt="Metrics" class="rounded-lg shadow-md">
                </div>
                <p class="text-gray-700 dark:text-gray-300 mt-6">
                    Metrics are indicators that reflect what’s happening in the bitcoin market. They’re your starting point for learning what drives price changes. In the Metrics section, you’ll find detailed information about each one, helping you explore their significance. To see how they’ve trended over time, head to Time Series. You can use these metrics to create models that focus on what matters most to you, making it easier to monitor the market without getting overwhelmed.
                </p>
            </div>
        </section>

        <!-- Section 3: Time Series -->
        <section class="get-started-section" id="time-series-section">
            <div class="content-wrapper">
                <div class="title-image-container">
                    <h1 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        Time Series
                    </h1>
                    <img src="https://placehold.co/300x200?text=IMG+03" alt="Time Series" class="rounded-lg shadow-md">
                </div>
                <p class="text-gray-700 dark:text-gray-300 mt-6">
                    The Time Series section lets you dive deeper by viewing charts of each metric. It’s a space to explore how these indicators have evolved—whether over weeks, months, or years and correlate to each other. You can also define time ranges and search for other time ranges with similar oscillation pattern. This is a spot to explore the metrics you'll be using to build your own formulas.
                </p>
            </div>
        </section>

        <!-- Section 4: Models -->
        <section class="get-started-section" id="models-section">
            <div class="content-wrapper">
                <div class="title-image-container">
                    <h1 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        Models
                    </h1>
                    <img src="https://placehold.co/300x200?text=IMG+04" alt="Models" class="rounded-lg shadow-md">
                </div>
                <p class="text-gray-700 dark:text-gray-300 mt-6">
                    Think of a Model as custom index — your own formula combining the metrics you care about most. Choose your metrics, set their weights, and define a threshold for buy or sell signals. Orbit will monitor these for you, alerting you when conditions align with your strategy. It’s a simple way to focus on what’s important, test your ideas against historical data, and see how they perform daily—all while reducing the time spent looking at and processing multiple charts every day.
                </p>
            </div>
        </section>

        <!-- Section 5: Performance -->
        <section class="get-started-section" id="performance-section">
            <div class="content-wrapper">
                <div class="title-image-container">
                    <h1 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        Performance
                    </h1>
                    <img src="https://placehold.co/300x200?text=IMG+05" alt="Performance" class="rounded-lg shadow-md">
                </div>
                <p class="text-gray-700 dark:text-gray-300 mt-6">
                    The Performance section shows you how your models hold up. By simulating trades using historical data and tracking daily outcomes, Orbit reveals the strengths and weaknesses of your strategies. You’ll gain insights into what works, what doesn’t, and how to improve.
                </p>
            </div>
        </section>
    </div>
</x-filament-panels::page>
