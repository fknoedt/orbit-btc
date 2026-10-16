@php
    use App\Services\UserSignalService;
    use Illuminate\Support\Number;
@endphp
<div class="help-signal p-6 bg-gray-900 rounded-lg">
    <style>
        .help-signal .help-field {
            font-weight: 700;
        }
        .help-signal .help-def {
            margin: 0.9rem 0 0;
            line-height: 1.55;
        }
    </style>
    <section>
        <h2 class="text-xl font-bold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">How Signals Work</h2>
        <p class="mt-2">
            A signal is a formula you build from on-chain and market metrics to spot patterns in Bitcoin's price.
            Orbit backtests that formula on past prices, then checks it every day.
            It simulates a {{ Number::currency(UserSignalService::tradeSizeInUsd()) }} buy or sell of Bitcoin,
            and notifies you when the score reaches your threshold.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-bold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Metrics</h2>
        <p class="mt-2">
            Your signal is made of one or more metrics. You set how each one is watched,
            which moves count in <span class="font-bold">percent</span>,
            and how much weight each metric has in your formula.
        </p>
        <img
            src="{{ asset('images/help/metric-box.png') }}"
            width="920"
            height="126"
            alt="One metric. Fear and Greed, Both, last 7 days. The note says the percent becomes points on the day's score."
            style="display: block; width: 920px; max-width: 100%; height: auto; margin-top: 1rem; border-radius: 12px;"
        >
        <p class="help-def" style="margin-top: 2.5rem;">
            <span class="help-field">When the metric:</span>
            Any metric you add. Orbit scores its value.
        </p>
        <p class="help-def">
            <span class="help-field">Moves (percent):</span>
            <span class="help-field">Up</span> counts a rise.
            <span class="help-field">Down</span> counts a drop.
            <span class="help-field">Both</span> counts either way (a 5% rise and a 5% drop are the same size).
        </p>
        <p class="help-def">
            <span class="help-field">In the last:</span>
            How many days back. One day is the change since yesterday.
            Fourteen days is the change over the last two weeks.
        </p>
        <p style="margin-top: 1.5rem;">
            With one metric, the percent is the score. A 5% move is 5 points, so a threshold of 5 means a 5% move.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-bold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Composite Signals</h2>
        <p class="mt-2">
            A composite signal uses more than one metric. Each one is still one part of the score. You still set
            what it watches and which moves count. Those metrics are not on the same scale, so Orbit adds a
            multiplier. The hints under the numbers offer a multiplier that levels them. Raise or lower a number
            yourself when one metric should matter more or less in your signal.
        </p>
        <img
            src="{{ asset('images/help/composite-metrics.png') }}"
            width="920"
            height="378"
            alt="Three metrics. MVRV times 5, with a hint of 2.1. Puell Multiple times 4, with a hint of 1.1. Fear and Greed times 1."
            style="display: block; width: 920px; max-width: 100%; height: auto; margin-top: 1rem; border-radius: 12px;"
        >
        <p class="help-def">
            <span class="help-field">Multiply movement by:</span>
            How strongly that metric's percent counts in the day's score.
            A hint under the number offers a multiplier that levels the metrics, scaling them so a usual move
            counts about the same on each one. 0.5 halves that percent, 1 keeps it, 2 doubles it, and so on.
        </p>
        <p style="margin-top: 1.75rem;">
            MVRV and Puell Multiple both look back 14 days, but a typical move on MVRV is about half the size of one
            on Puell Multiple. Fear &amp; Greed looks back 7 days and usually moves a little more than Puell Multiple.
            In the picture, Fear &amp; Greed is set to 1, so the hints offer 2.1 on MVRV and 1.1 on Puell Multiple.
            That puts a typical move on each metric at about the same size. Nothing changes until you click a hint,
            or <span class="font-bold">Level the metrics</span>.
        </p>
        <p class="mt-2">
            The hints only level. Type a higher number when that metric should matter more, or a lower one when it
            should matter less.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-bold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">The threshold</h2>
        <p class="mt-2">
            The threshold is one number for the whole signal. The signal fires on a day when the score reaches it.
            A lower number fires on smaller moves. A higher number waits for a larger one.
            There is no separate threshold on each metric.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-bold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4">Simulated trades</h2>
        <img
            src="{{ asset('images/help/trade-and-hold.png') }}"
            width="689"
            height="162"
            alt="Trade set to Sell, and Hold for set to 5 days. The threshold slider is not shown."
            style="display: block; width: 689px; max-width: 100%; height: auto; margin-top: 1rem; border-radius: 12px;"
        >
        <p class="help-def" style="margin-top: 1.75rem;">
            <span class="help-field">Trade:</span>
            A simulated {{ Number::currency(UserSignalService::tradeSizeInUsd()) }} order, checked against past prices.
            <span class="help-field">Buy</span> gains when the price rises while it is open.
            <span class="help-field">Sell</span> gains when the price falls.
        </p>
        <p class="help-def">
            <span class="help-field">Hold for:</span>
            The time horizon of the order. 1, 3, 5, 10, or 30 days is how long that simulated trade stays open.
            No new order starts while the last one is still open.
        </p>
        <p class="mt-2">
            Those results add up to the Signal Value.
        </p>
    </section>
</div>
