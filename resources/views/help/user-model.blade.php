<div class="p-6 bg-gray-900 text-gray-400 dark:text-gray-400 rounded-lg">
    <section>
        <h2 class="text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Overview</h2>
        <p class="mt-2">
            This application lets you design and evaluate custom bitcoin analysis models. You can combine various <span class="font-bold text-orange-500">Metrics</span> like Total Volume or Exchanges Reserve with your own <span class="font-bold text-orange-500">Weights</span>, <span class="font-bold text-orange-500">Thresholds</span>, and <span class="font-bold text-orange-500">Buy/Sell Signals</span> to create unique <span class="font-bold text-orange-500">Models</span>. The system simulates trades based on your models, assessing their performance over selected time horizons (1, 3, 5, or 10 days) using historical bitcoin price data. A scoring mechanism ranks your models, encouraging experimentation in a gamified setup.
        </p>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Create Your Model</h2>
        <p class="mt-2">To create your model, set these parameters:</p>
        <ul class="list-disc pl-5 mt-2 space-y-2">
            <li><span class="font-bold text-orange-500">Metrics</span>: Choose from bitcoin-related metrics like Average Fee, Total Exchanges Reserve, Fear & Greed Index, Mayer Multiple, Market Cap, Total Volume, or Closing Price.</li>
            <li><span class="font-bold text-orange-500">Weights</span>: Assign a weight (0 to 10, in 0.1 increments) to each metric to reflect its importance. For example, set Total Volume to 2.0 and Fear & Greed to 1.5.</li>
            <li><span class="font-bold text-orange-500">Threshold</span>: Set a threshold value that the weighted sum of metric changes must exceed to trigger a signal, e.g., 15.</li>
            <li><span class="font-bold text-orange-500">Signal Type</span>: Specify if exceeding the threshold indicates a <span class="font-bold text-orange-500">Buy</span> (price increase expected) or a <span class="font-bold text-orange-500">Sell</span> (price decrease expected).</li>
            <li><span class="font-bold text-orange-500">Time Horizon</span>: Select how many days ahead to evaluate the signal: 1, 3, 5, or 10 days.</li>
        </ul>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">How Daily Scores are Calculated</h2>
        <p class="mt-2">
            The system calculates daily scores by analyzing changes in your metrics across historical data. For each day, it determines the percentage change of each metric from the previous day—for example, Total Volume might increase by 5%, while Fear & Greed drops by 2%. It multiplies each metric’s change by its weight (e.g., 5% × 2.0 = 10 points, -2% × 1.5 = -3 points) and sums them to get your <span class="font-bold text-orange-500">Weighted Sum</span> (e.g., 10 + (-3) = 7). If the Weighted Sum exceeds your <span class="font-bold text-orange-500">Threshold</span> (e.g., 16 > 15), a Buy or Sell signal is triggered based on your model’s settings.
        </p>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Test Against History</h2>
        <p class="mt-2">When a signal triggers, the system runs a $1000 simulated trade using historical data:</p>
        <ul class="list-disc pl-5 mt-2 space-y-4">
            <li>
                <span class="font-bold text-orange-500">Buy Signal</span>: You buy $1000 worth of bitcoin at the current closing price. After your chosen horizon (h days), the value is:
                <div class="mt-2">
                    <div class="katex-formula">
                        \text{Value} = 1000 \times \frac{P_{\text{today} + h}}{P_{\text{today}}}
                    </div>
                    <span class="block mt-2">Then, your profit is:</span>
                    <div class="katex-formula mt-2">
                        \text{Profit} = \text{Value} - 1000
                    </div>
                </div>
                <p class="mt-2">For example, if you buy at $80,000 and the price after 3 days is $84,000, your profit is 1000 × (84,000 /80,000) - 1000 = $50.</p>
            </li>
            <li>
                <span class="font-bold text-orange-500">Sell Signal</span>: You sell $1000 worth of bitcoin, receiving $1000 cash. Your profit (or savings) is:
                <div class="mt-2">
                    <div class="katex-formula">
                        \text{Profit} = 1000 \times \left(1 - \frac{P_{\text{today} + h}}{P_{\text{today}}}\right)
                    </div>
                </div>
                <p class="mt-2">For example, if you sell at $100,000 and the price after 3 days is $95,000, your profit is 1000 × (1 - 95,000 /100,000) = $50.</p>
            </li>
        </ul>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Example</h2>
        <div class="bg-gray-800 p-4 rounded mt-2">
            <p>
                You create a model with Total Volume (weight 2.0), Fear & Greed (weight 1.5), a threshold of 15, a Sell signal, and a 3-day horizon. On a historical day, Total Volume rises 8% (8% × 2.0 = 16 points) and Fear & Greed rises 2% (2% × 1.5 = 3 points). Your Weighted Sum is 16 + 3 = 19, exceeding the threshold of 15, triggering a Sell signal. A $1000 trade is executed at $100,000, and after 3 days, the price drops to $95,000. Your profit is 1000 × (1 - 95,000 /100,000) = $50, so your Model Score is $50.
            </p>
        </div>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Notes</h2>
        <p class="mt-2">
            Metric changes can be positive or negative, affecting the Weighted Sum. The system uses closing prices for consistency. Future updates may include adjusting trade sizes, adding more metrics, or introducing gamification features like challenges.
        </p>
    </section>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/model-view.css') }}">
@endpush

@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.katex-formula').forEach(function(element) {
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
