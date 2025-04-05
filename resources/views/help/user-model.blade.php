@php use App\Services\UserModelService;use Illuminate\Support\Number; @endphp
<div class="p-6 bg-gray-900 text-black-400 dark:text-gray-400 rounded-lg">
    <section>
        <h2 class="text-xl font-bold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Model - Overview</h2>
        <p class="mt-2">
            Orbit BTC lets you design and evaluate your own custom bitcoin analysis models. You can combine various
            <span class="font-bold">Metrics</span>, like Total Volume or Exchanges Reserve, with your own <span
                class="font-bold">Weights</span> and <span class="font-bold">Thresholds</span> to determine smart <span
                class="font-bold">Buy/Sell Signals</span> that will trigger <span
                class="font-bold">Simulates Trades</span>.
        </p>
        <p class="mt-2">
            For each day in a time series starting {{ Number::abbreviate(UserModelService::MAX_DAYS_BACK) }} days in the
            past, a {{ Number::currency(constant('App\Services\UserModelService::TRADE_SIZE_IN_USD')) }} buy or sell
            simulation trade will be created if your Model's score for that day hits its Threshold, and the operation
            performance will be assessed over selected time horizons (1 to 10 days) using historical and current bitcoin
            price data.
        </p>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-bold border-b-2  text-orange-500 border-orange-500 pb-2 mb-4">Create Your Model</h2>
        <p class="mt-4 font-bold">Info:</p>
        <ul class="list-disc pl-5 mt-2 space-y-2">
            <li><span class="font-bold">Name</span>: Give your Model a name. Brief or abbreviated names are recommended
                for better reference and display.
            </li>
            <li><span class="font-bold">Description</span>: Describe the Model's premises and goals.
            </li>
            <li><span class="font-bold">Notify Email/Telegram</span>: Get a notification of your Model's Daily Signal
                (see below)
            </li>
        </ul>
        <p class="mt-6 font-bold">Metrics:</p>
        <ul class="list-disc pl-5 mt-2 space-y-2">
            <li><span class="font-bold">Metrics</span>: Choose from different bitcoin metrics, like Total
                Exchanges Reserve, Fear & Greed Index, Mayer Multiple, etc. The metric's daily variation from the
                previous day will be the base for each day's score ("Signal").
            </li>
            <li><span class="font-bold">Up, Down or Both</span>: Determine if your metric is watching variations Up,
                Down or in Both directions. E.g. if you choose <span class="font-bold">Up</span>, any day that the
                metric goes down, nothing will be added to the Signal. Opposite for <span class="font-bold">Down</span>
                and <span class="font-bold">Both</span> will watch either variation.
            </li>
            <li><span class="font-bold">Metric Weight</span>: Assign a weight (0 to 10, in 0.1 increments) to each
                metric to reflect its importance. Daily variation percentage multiplied by weight is the metric's
                <span class="font-bold">Daily Signal</span>.
            </li>
            <li><span class="font-bold">Metric Threshold [optional]</span>: Determine a floor/minimum value that the
                metric's <span class="font-bold">Daily Signal</span> has to reach or it will be discarded for that day.
            </li>
        </ul>
        <p class="mt-2 italic">The sum of each calculated Metric's <span class="font-bold">Daily Signal</span>
            determines the <span class="font-bold">Model Daily Signal</span></p>
        <p class="mt-6 font-bold">Tuning:</p>
        <ul class="list-disc pl-5 mt-2 space-y-2">
            <li><span class="font-bold">Paused?</span>: This will stop any calculation or notification for
                the Model. Performance Analysis will also be disabled.
            </li>
            <li><span class="font-bold">Model Daily Threshold</span>: Determine which value the
                <span class="font-bold">Model Daily Signal</span> has to hit to trigger a trade operation on each day.
            </li>
            <li><span class="font-bold">Signal Type</span>: If the <span class="font-bold">Model Threshold</span> is
                hit, a <span class="font-bold">Buy</span> (price increase expected) or <span class="font-bold">Sell</span>
                (price decrease expected) operation should be created. If threshold is not met, no operation will be
                created for that day.
            </li>
            <li><span class="font-bold">Time Horizon</span>: Select how many days ahead to evaluate the signal: 1, 3, 5,
                or 10 days. The price delta between the trade operation's date and N days ahead will determine the
                daily score, which can go up or down depending on the operation of the day.
            </li>
        </ul>
        <p class="mt-2 italic">The sum of each <span class="font-bold">Model Daily Signal</span>
            determines the <span class="font-bold">Model Global Score</span>, which will be re-calculated daily</p>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold  text-orange-500 border-orange-500 border-b-2 pb-2 mb-4">How Daily Scores are
            Calculated</h2>
        <p class="mt-2">
            The system calculates daily scores by analyzing changes in your metrics across historical data. For each
            day, it determines the percentage change of each metric from the previous day—for example, Total Volume
            might increase by 5%, while Fear & Greed drops by 2%. It multiplies each metric’s change by its weight
            (e.g., 5% × 2.0 = 10 points, -2% × 1.5 = -3 points) and sums them to get your <span class="font-bold">Weighted Sum</span>
            (e.g., 10 + (-3) = 7). If the Weighted Sum exceeds your <span class="font-bold">Threshold</span> (e.g., 16 >
            15), a Buy or Sell signal is triggered based on your model’s settings.
        </p>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold border-b-2  text-orange-500 border-orange-500 pb-2 mb-4">Test Against
            History</h2>
        <p class="mt-2">When a signal triggers, the system runs a $1000 simulated trade using historical data:</p>
        <ul class="list-disc pl-5 mt-2 space-y-4">
            <li>
                <span class="font-bold text-orange-500">Buy Signal</span>: You buy $1000 worth of bitcoin at the current
                closing price. After your chosen horizon (h days), the value is:
                <div class="mt-2">
                    <div class="katex-formula">
                        \text{Value} = 1000 \times \frac{P_{\text{today} + h}}{P_{\text{today}}}
                    </div>
                    <span class="block mt-2">Then, your profit is:</span>
                    <div class="katex-formula mt-2">
                        \text{Profit} = \text{Value} - 1000
                    </div>
                </div>
                <p class="mt-2">For example, if you buy at $80,000 and the price after 3 days is $84,000, your profit is
                    1000 × (84,000 /80,000) - 1000 = $50.</p>
            </li>
            <li>
                <span class="font-bold text-orange-500">Sell Signal</span>: You sell $1000 worth of bitcoin, receiving
                $1000 cash. Your profit (or savings) is:
                <div class="mt-2">
                    <div class="katex-formula">
                        \text{Profit} = 1000 \times \left(1 - \frac{P_{\text{today} + h}}{P_{\text{today}}}\right)
                    </div>
                </div>
                <p class="mt-2">For example, if you sell at $100,000 and the price after 3 days is $95,000, your profit
                    is 1000 × (1 - 95,000 /100,000) = $50.</p>
            </li>
        </ul>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Example</h2>
        <div class="bg-gray-800 p-4 rounded mt-2">
            <p>
                You create a model with Total Volume (weight 2.0), Fear & Greed (weight 1.5), a threshold of 15, a Sell
                signal, and a 3-day horizon. On a historical day, Total Volume rises 8% (8% × 2.0 = 16 points) and Fear
                & Greed rises 2% (2% × 1.5 = 3 points). Your Weighted Sum is 16 + 3 = 19, exceeding the threshold of 15,
                triggering a Sell signal. A $1000 trade is executed at $100,000, and after 3 days, the price drops to
                $95,000. Your profit is 1000 × (1 - 95,000 /100,000) = $50, so your Model Score is $50.
            </p>
        </div>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Notes</h2>
        <p class="mt-2">
            Metric changes can be positive or negative, affecting the Weighted Sum. The system uses closing prices for
            consistency. Future updates may include adjusting trade sizes, adding more metrics, or introducing
            gamification features like challenges.
        </p>
    </section>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.css" crossorigin="anonymous">
@endpush

@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll('.katex-formula').forEach(function (element) {
                try {
                    katex.render(element.textContent.trim(), element, {
                        displayMode: true,
                        throwOnError: false
                    });
                } catch (e) {
                    console.error('KaTeX rendering error:', e);
                }
            });
        });
    </script>
@endpush
