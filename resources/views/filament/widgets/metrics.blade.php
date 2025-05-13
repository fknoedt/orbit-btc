@php use Carbon\Carbon;use App\Helpers\NumberHelper; @endphp
<x-filament-widgets::widget :class="implode(' ', $this->getWidgetClasses())">
    <x-filament::card class="p-0">
        <div class="metrics-table-container" style="height: 260px !important; overflow-y: auto !important;">
            <table class="metrics-table">
                <thead>
                <tr>
                    <th>Metric</th>
                    <th>Current Value</th>
                    <th>Change</th>
                    <th>Interval</th>
                    <!--th>Date</th-->
                    <th>Ref. Date</th>
                    <th>Ref. Value</th>
                    <th>Info</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($metrics as $metric)
                    <tr>
                        <td>
                            <a href="#" x-on:click="$dispatch('open-modal', { id: 'metric-details-{{ $metric->id }}' })" class="text-gray-900 dark:text-white cursor-pointer">{{ $metric->name }}</a>
                        </td>
                        <td>{{ NumberHelper::formatWithSuffix($metric->current_value, 2) }}</td>
                        <td style="color: {{ $metric->change > 0 ? '#3b82f6' : ($metric->change == 0 ? '#cccccc' : '#ef4444') }} !important">
                            <strong>
                                {{ Number::percentage($metric->change, 2) }}
                            </strong>
                        </td>
                        <td>
                            <x-filament::badge style="display: inline-block; width: auto; vertical-align: middle;">
                                {{ $metric->number_of_days }}d
                            </x-filament::badge>
                        </td>
                        {{--<td>
                            <span class="text-gray-500">
                                {{ Carbon::createFromFormat('Y-m-d', $metric->current_date)->format('M d y') }}
                            </span>
                        </td>--}}
                        <td>
                            <span class="text-gray-500">
                                {{ Carbon::createFromFormat('Y-m-d', $metric->reference_date)->format('M d y') }}
                            </span>
                        </td>
                        <td>
                            <span class="text-gray-500">
                                {{ NumberHelper::formatWithSuffix($metric->reference_value, 2) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ $metric->chart_url }}" class="chart-link">
                                <x-heroicon-o-presentation-chart-bar class="inline w-5 h-5" />Chart
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::card>
    @foreach ($metrics as $metric)
        <x-filament::modal id="metric-details-{{ $metric->id }}" heading="Metric Details" class="metric-details-modal">
            <div class="p-4">
                <div class="mb-4">
                    <p class="text-white">{{ $metric->name }}</p>
                </div>
                <div class="mb-4">
                    <p class="text-white">{{ $metric->description }}</p>
                </div>
                <div class="mb-4 ml-0 pl-0">
                    <a href="{{ $metric->chart_url }}" class="chart-link inline-flex items-center text-white">
                        <x-heroicon-o-presentation-chart-bar class="w-5 h-5 mr-1" /> Chart
                    </a>
                </div>
            </div>
        </x-filament::modal>
    @endforeach
</x-filament-widgets::widget>
