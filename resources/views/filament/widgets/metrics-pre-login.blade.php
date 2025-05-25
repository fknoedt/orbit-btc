@php
    use Carbon\Carbon;
    use App\Helpers\NumberHelper;
@endphp
<div class="metrics-widget">
    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="metrics-table-container" style="height: 260px !important; overflow-y: auto !important;">
            <table class="metrics-table w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th class="p-2">Metric</th>
                    <th class="p-2">Current Value</th>
                    <th class="p-2">Change</th>
                    <th class="p-2">Interval</th>
                    <!--th>Date</th-->
                    <th class="p-2">Ref. Date</th>
                    <th class="p-2">Ref. Value</th>
                    <th class="p-2">Info</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($metrics as $metric)
                    <tr class="border-b dark:border-gray-600">
                        <td class="p-2">
                            <a href="#" onclick="openModal('metric-details-{{ $metric->id }}')" class="text-gray-900 dark:text-white cursor-pointer">{{ $metric->name }}</a>
                        </td>
                        <td class="p-2">{{ NumberHelper::formatWithSuffix($metric->current_value, 2) }}</td>
                        <td class="p-2" style="color: {{ $metric->change > 0 ? '#3b82f6' : ($metric->change == 0 ? '#cccccc' : '#ef4444') }} !important">
                            <strong>
                                {{ Number::percentage($metric->change, 2) }}
                            </strong>
                        </td>
                        <td class="p-2">
                            <span class="inline-block px-2 py-1 text-sm bg-primary-500 text-white rounded" style="width: auto; vertical-align: middle;">
                                {{ $metric->number_of_days }}d
                            </span>
                        </td>
                        {{--<td class="p-2">
                            <span class="text-gray-500">
                                {{ Carbon::createFromFormat('Y-m-d', $metric->current_date)->format('M d y') }}
                            </span>
                        </td>--}}
                        <td class="p-2">
                            <span class="text-gray-500">
                                {{ Carbon::createFromFormat('Y-m-d', $metric->reference_date)->format('M d y') }}
                            </span>
                        </td>
                        <td class="p-2">
                            <span class="text-gray-500">
                                {{ NumberHelper::formatWithSuffix($metric->reference_value, 2) }}
                            </span>
                        </td>
                        <td class="p-2">
                            <a href="{{ $metric->chart_url }}" class="chart-link inline-flex items-center text-primary-600 dark:text-primary-400">
                                <svg class="inline w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                </svg>
                                Chart
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @foreach ($metrics as $metric)
        <div id="metric-details-{{ $metric->id }}" class="modal hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center">
            <div class="p-4 bg-gray-800 rounded-lg max-w-lg w-full">
                <div class="mb-4">
                    <p class="text-white">{{ $metric->name }}</p>
                </div>
                <div class="mb-4">
                    <p class="text-white">{{ $metric->description }}</p>
                </div>
                <div class="mb-4">
                    <a href="{{ $metric->chart_url }}" class="chart-link inline-flex items-center text-white">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                        Chart
                    </a>
                </div>
                <button onclick="closeModal('metric-details-{{ $metric->id }}')" class="text-white bg-primary-500 px-4 py-2 rounded hover:bg-primary-600">Close</button>
            </div>
        </div>
    @endforeach
</div>
<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }
</script>
