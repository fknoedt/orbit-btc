@php use App\Services\UserSignalService;use Illuminate\Support\Number; @endphp
<div class="p-6 bg-gray-900 text-black-400 dark:text-gray-400 rounded-lg">
    <section>
        <h2 class="text-xl font-bold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Signal - Overview</h2>
        <p class="mt-2">
            Orbit BTC is your playground for crafting custom bitcoin analysis Signals. Mix and match <span
                    class="font-bold">Metrics</span> like Total Volume or Exchanges Reserve, tweak their <span
                    class="font-bold">Weights</span> and <span class="font-bold">Thresholds</span>, and set up <span
                    class="font-bold">Buy/Sell Signals</span> to kick off <span
                    class="font-bold">Simulated Trades</span>.
        </p>
        <p class="mt-2">
            Every day, going back {{ Number::abbreviate(UserSignalService::MAX_DAYS_BACK) }} days, Orbit runs
            a {{ Number::currency(constant('App\Services\UserSignalService::TRADE_SIZE_IN_USD')) }} trade simulation if
            your Signal’s score hits its threshold. It then checks how it performs over your chosen time horizon (1 to 10
            days) using real bitcoin price data—past and present.
        </p>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-bold border-b-2 text-orange-500 border-orange-500 pb-2 mb-4">Create Your Signal</h2>
        <p class="mt-4 font-bold">Info:</p>
        <ul class="list-disc pl-5 mt-2 space-y-2">
            <li><span class="font-bold">Name</span>: Pick a short, snappy name for your Signal—makes it easier to spot
                later.
            </li>
            <li><span class="font-bold">Description</span>: Jot down what your Signal’s all about and what it’s aiming
                for.
            </li>
            <li><span class="font-bold">Notify Email/Telegram</span>: Get a ping with your Signal (more on
                that below).
            </li>
        </ul>
        <p class="mt-6 font-bold">Metrics:</p>
        <ul class="list-disc pl-5 mt-2 space-y-2">
            <li><span class="font-bold">Metrics</span>: Pick from bitcoin goodies like Total Exchanges Reserve, Fear &
                Greed Index, Mayer Multiple, or Closing Price. Their daily percentage change from yesterday drives your
                score.
            </li>
            <li><span class="font-bold">Up, Down, or Both</span>: Decide which way your metric counts—<span
                        class="font-bold">Up</span> only scores gains (ignores drops), <span
                        class="font-bold">Down</span> only scores drops (ignores gains), or <span
                        class="font-bold">Both</span> counts any change.
            </li>
            <li><span class="font-bold">Metric Weight</span>: Give each metric a weight (0 to 10, 0.1 steps) to boost
                its impact. Daily change % × weight = that metric’s <span class="font-bold">Daily Score</span>.
            </li>
            <li><span class="font-bold">Metric Threshold [optional]</span>: Set a minimum bar for the metric’s <span
                        class="font-bold">Daily Score</span>. If it doesn’t hit this, it’s ignored that day.
            </li>
        </ul>
        <p class="mt-2 italic">Add up each metric’s <span class="font-bold">Daily Score</span> to get the <span
                    class="font-bold">Daily Score</span>.</p>
        <p class="mt-6 font-bold">Tuning:</p>
        <ul class="list-disc pl-5 mt-2 space-y-2">
            <li><span class="font-bold">Paused?</span>: Flip this on to freeze calculations, notifications, and
                performance tracking.
            </li>
            <li><span class="font-bold">Signal Daily Threshold</span>: Set the magic number your <span class="font-bold">Signal Daily Score</span>
                needs to reach to trigger a trade.
            </li>
            <li><span class="font-bold">Signal Type</span>: Choose what happens when the threshold’s hit—<span
                        class="font-bold">Buy</span> (betting on a price jump) or <span class="font-bold">Sell</span>
                (expecting a dip). No hit, no trade.
            </li>
            <li><span class="font-bold">Time Horizon</span>: Pick 1, 3, 5, or 10 days to see how your trade pans out.
                Price change from trade day to horizon day sets your score—up or down, depending on the signal.
            </li>
        </ul>
        <p class="mt-2 italic">All your <span class="font-bold">Signal Daily Scores</span> add up to the <span
                    class="font-bold">Signal Global Score</span>, updated daily.</p>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold text-orange-500 border-orange-500 border-b-2 pb-2 mb-4">How Daily Scores
            Work</h2>
        <p class="mt-2">
            Here’s the deal: each day, Orbit checks how your metrics shift based on its frequency—like Total Volume up 5% within 24 hours
            and Mayer Multiple up 10% within a week. Multiply those changes by their weights (say, 5% × 1.0 = 5, 10% × 1.3 = 13), then
            add them up for your <span class="font-bold">Signal Daily Score</span> (5 + 13 = 18). If that beats your
            <span class="font-bold">Signal Daily Threshold</span> (like 18 > 15), it triggers a Buy or Sell, depending on
            your setup.
        </p>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold border-b-2 text-orange-500 border-orange-500 pb-2 mb-4">Testing with
            History</h2>
        <p class="mt-2">When a signal fires, Orbit runs a $1000 fake trade with old price data:</p>
        <ul class="list-disc pl-5 mt-2 space-y-4">
            <li>
                <span class="font-bold text-orange-500">Buy Signal</span>: You grab $1000 of bitcoin at today’s close.
                After your horizon (h days), your value’s:
                <div class="mt-2">
                    <div class="katex-formula">
                        \text{Value} = 1000 \times \frac{P_{\text{today} + h}}{P_{\text{today}}}
                    </div>
                    <span class="block mt-2">Profit’s just:</span>
                    <div class="katex-formula mt-2">
                        \text{Profit} = \text{Value} - 1000
                    </div>
                </div>
                <p class="mt-2">Say you buy at $80,000, and 3 days later it’s $84,000: 1000 × (84,000 / 80,000) - 1000 =
                    $50 profit.</p>
            </li>
            <li>
                <span class="font-bold text-orange-500">Sell Signal</span>: You ditch $1000 of bitcoin for cash. Profit
                (or savings) is:
                <div class="mt-2">
                    <div class="katex-formula">
                        \text{Profit} = 1000 \times \left(1 - \frac{P_{\text{today} + h}}{P_{\text{today}}}\right)
                    </div>
                </div>
                <p class="mt-2">Sell at $100,000, 3 days later it’s $95,000: 1000 × (1 - 95,000 / 100,000) = $50
                    profit.</p>
            </li>
        </ul>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Quick Example</h2>
        <div class="bg-gray-800 p-4 rounded mt-2">
            <p>
                You build a Signal: Total Volume (weight 2.0), Fear & Greed (weight 1.5), threshold 15, Sell signal,
                3-day horizon. One day, Total Volume jumps 8% (8 × 2.0 = 16), Fear & Greed nudges up 2% (2 × 1.5 = 3).
                Signal Daily Score = 19, beating 15, so it sells $1000 at $100,000. Three days later, price drops to
                $95,000. Profit: 1000 × (1 - 95,000 / 100,000) = $50. Nice!
            </p>
        </div>
    </section>

    <section class="pt-8">
        <h2 class="text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Notes</h2>
        <p class="mt-2">
            Metrics can swing up or down, tweaking your signal. Closing prices keep it consistent. Look out for future
            goodies like bigger trades, new metrics, or fun challenges.
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
