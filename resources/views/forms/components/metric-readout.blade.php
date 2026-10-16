<div class="metric-readout">
    <p class="metric-sentence">
        When
        <span class="metric-em">{{ $name }}</span>@if (! empty($chartUrl)) <a
            class="metric-chart-icon"
            href="{{ $chartUrl }}"
            target="_blank"
            rel="noopener"
            title="Open this metric's chart"
            aria-label="Open this metric's chart"
        ><x-heroicon-m-arrow-top-right-on-square style="width: 1.1em; height: 1.1em;" /></a>@endif
        moves
        <span class="metric-em">{{ $directionPhrase }}</span>
        in the last
        <span class="metric-em">{{ $spanPhrase }}</span>,
        @if (! empty($locked) && ! empty($exactMultiplier))
            this saved signal still multiplies that percent by
            <span class="metric-em">{{ $exactMultiplier }}</span>.
            Saving from Edit uses 1
        @elseif (! empty($locked))
            that percent becomes points on the day's score
        @else
            multiply the percent movement by
            <span class="metric-em">{{ $multiplierText }}</span>
        @endif
    </p>
</div>
