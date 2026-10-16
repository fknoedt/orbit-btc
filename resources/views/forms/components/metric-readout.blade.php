<div class="metric-readout">
    <div>
        <div class="metric-k">When the metric</div>
        <div class="metric-v metric-name-line">
            <span>
                {{ $name }}
                @if ($source)
                    <span class="metric-source">({{ $source }})</span>
                @endif
            </span>
            @if (! empty($chartUrl))
                <a class="metric-chart-link" href="{{ $chartUrl }}" target="_blank" rel="noopener">Chart</a>
            @endif
        </div>
    </div>
    <div>
        <div class="metric-k metric-k-nowrap">Moves (percent)</div>
        <div class="metric-v">
            <span class="dir-pill is-selected {{ $directionClass }}">
                @switch($directionIcon)
                    @case('arrow-up')
                        <x-heroicon-o-arrow-up style="width: 16px; height: 16px; stroke-width: 2; color: currentColor;" />
                        @break
                    @case('arrow-down')
                        <x-heroicon-o-arrow-down style="width: 16px; height: 16px; stroke-width: 2; color: currentColor;" />
                        @break
                    @default
                        <x-heroicon-o-arrows-up-down style="width: 16px; height: 16px; stroke-width: 2; color: currentColor;" />
                @endswitch
                {{ $directionWord }}
            </span>
        </div>
    </div>
    <div>
        <div class="metric-k">
            @include('forms.components.label-tip', ['label' => 'In the last', 'tooltip' => $lookBackTip])
        </div>
        <div class="metric-v">{{ $span }}</div>
    </div>
    @if (empty($lockedNote))
        <div>
            <div class="metric-k">
                @include('forms.components.label-tip', ['label' => 'Multiply movement by', 'tooltip' => $multiplierTip])
            </div>
            <div class="metric-v">{{ $multiplierText }}</div>
            @if (! empty($multiplierHint))
                <p class="metric-hint">{{ $multiplierHint }}</p>
            @endif
        </div>
    @else
        <p class="metric-single-note">{{ $lockedNote }}</p>
    @endif
</div>
