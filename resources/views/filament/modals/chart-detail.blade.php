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
                Model Score:
            </span>
            <span class="text-base font-semibold !text-white">{{ number_format($dailyScore->score, 2) }}</span>
        </p>
        <p class="mt-2 text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                Threshold:
            </span>
            <span class="text-base font-semibold !text-white">
                {{ $userModel->threshold . ($dailyScore->score > $userModel->threshold ? ' [hit]' : '[not hit]') }} =>
                {{ ucfirst($userModel->buy_or_sell) }}
            </span>
        </p>
        <p class="mt-2 text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                Price After {{ $userModel->time_horizon }} day(s):
            </span>
            <span class="text-base font-semibold !text-white">
                {{ number_format(
                    $dailyPrice->close +
                    (
                        $dailyPrice->close *
                        (($dailyPrice->{'price_change_' . $userModel->time_horizon . 'd'}) / 100)
                    )
                    ,
                    2
                )  }}
            </span>
        </p>
        <p class="mt-2 text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                Signal Value:
            </span>
            @if($dailyScore->score > $userModel->threshold)
                <span class="text-base font-semibold !text-white">
                    {{ $dailyScore->signal_value }}
                </span>
            @else
                <span class="text-gray-600 dark:text-gray-400">
                    Threshold not hit
                </span>
            @endif
        </p>
    @endif
</div>
