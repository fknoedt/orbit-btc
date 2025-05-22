<div>
    @if(isset($dailyPrice))
        <h3 class="text-lg font-medium text-orange-600 dark:text-orange-600" style="color: darkorange">
            {{ \Carbon\Carbon::parse($date)->format('M d Y - l') }}
        </h3>
        <p class="mt-2 text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                BTC Closing Price:
            </span>
            <span class="text-base font-semibold !text-white">{{ number_format($dailyPrice->close, 2) }}</span>
        </p>
        <p class="mt-2 text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                Daily Score:
            </span>
            <span class="text-base font-semibold !text-white">{{ number_format($dailyScore->score, 2) }}</span>
        </p>
        <p class="mt-2 text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                Threshold:
            </span>
            <span class="text-base font-semibold !text-white">
                {{ $userSignal->threshold }}
                @if ($dailyScore->score > $userSignal->threshold)
                    🎯
                    <x-heroicon-o-arrow-right class="inline w-5 h-5" />
                    {{ ucfirst($userSignal->buy_or_sell) }}
                    {{ $userSignal->buy_or_sell === 'buy' ? '📈' : '📉' }}
                @else
                    <x-heroicon-o-x-circle class="inline w-5 h-5 text-red-300" style="color: indianred"/>
                    <x-heroicon-o-arrow-right class="inline w-5 h-5" style="color: grey" />
                    Don't {{ ucfirst($userSignal->buy_or_sell) }}
                @endif
            </span>
        </p>
        @php
            $futureChange = $dailyPrice->{'price_change_' . $userSignal->time_horizon . 'd'};
            $futurePrice =
                $dailyPrice->close + (
                    $dailyPrice->close * ($futureChange / 100)
                );
        @endphp
        <p class="mt-2 text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                Price After {{ $userSignal->time_horizon }} day(s):
            </span>
            <span class="text-base font-semibold !text-white" style="color: {{ $futurePrice > $dailyPrice->close ? '#008FFB' : '#ef4444' }}">
                {{ number_format($futurePrice, 2) }} |
                @if ($futureChange > 0)
                    <x-heroicon-o-arrow-trending-up class="inline w-5 h-5" />
                @else
                    <x-heroicon-o-arrow-trending-down class="inline w-5 h-5" />
                @endif
                {{ number_format($futureChange, 2) }}%
            </span>
        </p>
        @if($dailyScore->score > $userSignal->threshold && ! $dailyScore->quarantined)
            <p class="mt-2 text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                Stake:
            </span>
                <span class="text-base font-semibold !text-white">
                ${{ number_format($dailyScore->stake, 2) }}
            </span>
            </p>
            <p class="mt-2 text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                Signal Value:
            </span>
                <span class="text-base font-semibold !text-white" style="color: {{ $dailyScore->signal_value > 0 ? '#22c55e' : '#ef4444' }}">
                {{ number_format($dailyScore->signal_value, 2) }}
            </span>
            </p>
        @else
            <p class="mt-4 text-sm col-span-2 text-center" style="margin-top: 20px; margin-bottom: 20px;">
                @if ($dailyScore->quarantined)
                    <x-filament::badge style="display: inline-block; width: auto; vertical-align: middle; color: red;">
                        Quarantined since last purchase
                    </x-filament::badge>
                @else
                    <span class="text-base font-semibold !text-white">
                        Threshold not hit - no stake in this day
                    </span>
                @endif
            </p>
        @endif
    @endif
</div>
