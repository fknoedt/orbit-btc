<div>
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
        Chart Details for {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
    </h3>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        You clicked on the date: {{ $dailyPrice->close ?? 'null' }} / {{ $dailyScore->score ?? 'null' }}
    </p>
</div>
