<div>
    @if(isset($dailyPrice))
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ \Carbon\Carbon::parse($date)->format('M d Y - l') }}
        </h3>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            BTC Closing Price: {{ $dailyPrice->close }}
        </p>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Model Score: {{ $dailyScore->score }}
        </p>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Threshold: {{ $userModel->threshold }} => {{ $userModel->buy_or_sell }}
        </p>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Price {{ $userModel->time_horizon }}d+: {{ $dailyPrice->{'price_change_' . $userModel->time_horizon . 'd'} }}
        </p>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Signal Value: {{ $dailyScore->signal_value }}
        </p>
    @endif
</div>
