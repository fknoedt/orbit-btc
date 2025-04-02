@php use Carbon\Carbon; @endphp
<x-filament-panels::page>
    <div class="p-6">
        <div class="mb-4 flex justify-between items-center gap-6">
            <div class="flex items-center gap-6">
                <details class="relative">
                    <summary class="text-lg font-medium text-gray-500 cursor-pointer flex items-center gap-1">
                        <span>
                            {{ count($selectedMetrics) === 1 ? 'Metric: ' : 'Metrics: ' }}
                            <span class="text-white">
                                {{ collect($selectedMetrics)->map(fn($metric) => match($metric) {
                                    'market_cap' => 'Market Cap',
                                    'total_volume' => 'Total Volume',
                                    'close' => 'BTC Price',
                                    'average_fee' => 'Average Fee',
                                    'exchanges_reserve' => 'Exchanges Reserve',
                                    'fear_and_greed' => 'Fear & Greed',
                                    'mayer_multiple' => 'Mayer Multiple',
                                    default => 'BTC Price'
                                })->implode(count($selectedMetrics) === 1 ? '' : ' x ') }}
                            </span>
                        </span>
                    </summary>
                    <select
                        id="selectedMetrics"
                        wire:model.live="selectedMetrics"
                        multiple
                        size="7"
                        class="absolute top-full mt-1 block bg-white text-gray-900 border-gray-300 rounded-lg p-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 opacity-90 z-10 appearance-none"
                        style="width: 150px;"
                    >
                        <option value="market_cap">Market Cap</option>
                        <option value="total_volume">Total Volume Traded</option>
                        <option value="close">BTC Price</option>
                        <option value="average_fee">Average BTC Fee</option>
                        <option value="exchanges_reserve">Exchanges Reserve</option>
                        <option value="fear_and_greed">Fear & Greed</option>
                        <option value="mayer_multiple">Mayer Multiple</option>
                    </select>
                </details>
                <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                    <x-heroicon-o-question-mark-circle class="w-6 h-6" title="Ctrl+click for up to 2 metrics" />
                </span>
            </div>
            <div class="flex items-center gap-6">
                <label for="selectedPeriod" class="text-lg font-medium text-gray-500">Period</label>
                <select
                    id="selectedPeriod"
                    wire:model.live="selectedPeriod"
                    class="block bg-white text-gray-900 border-gray-300 rounded-lg p-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
                    style="width: 150px;"
                >
                    <option value="7d">1 Week</option>
                    <option value="14d">2 Weeks</option>
                    <option value="30d">1 Month</option>
                    <option value="90d">3 Months</option>
                    <option value="180d">6 Months</option>
                    <option value="365d">1 Year</option>
                    <option value="1095d">3 Years</option>
                    <option value="1826d">5 Years</option>
                    <option value="3652d">10 Years</option>
                    <option value="0d">All-Time</option>
                </select>
            </div>
        </div>
        <!-- Hidden Button to Trigger Date Range Update -->
        <div wire:ignore style="display: none;">
            <button id="update-date-range" wire:click="updateDateRange($event.target.dataset.start, $event.target.dataset.end)"></button>
        </div>
        <div class="w-full">
            @include('filament.components.time-series-chart', [
                'label' => '',
                'name' => 'btc-price',
                'hint' => null,
                'options' => $chartData['options'] ?? [],
                'rawExtraJsOptions' => $chartData['extraJsOptions'] ?? [],
                'hint' => 'Zoom in/out and click below to pattern match time series similar to the displayed range'
            ])
        </div>
        <div class="w-full flex flex-col gap-3 mt-6">
            <div class="flex items-center gap-3">
                <button wire:click="searchSimilar" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        style="background-color: #1f2937;">
                    Search for Similar Time Series Pattern
                </button>
                <span id="selected-dates" class="text-sm text-gray-500 dark:text-gray-400">{{ $dateLabel }}</span>
            </div>
        </div>

        <div wire:key="additional-charts-{{ md5(json_encode($additionalCharts)) }}">
            @foreach($additionalCharts as $index => $chart)
                <hr class="my-4">
                <div class="w-full" wire:key="additional-chart-{{ $index }}">
                    @include('filament.components.time-series-chart', [
                        'label' => sprintf(
                            'From %s to %s (%s days)',
                            $chart['startDate']->format('M d Y'),
                            $chart['endDate']->format('M d Y'),
                            $chart['startDate']->diffInDays($chart['endDate']),
                        ),
                        'name' => 'additional-chart-' . $index,
                        'hint' => "Euclidean Distance: {$chart['distance']}",
                        'options' => $chart['options'],
                        'rawExtraJsOptions' => [],
                        'distance' => $chart['distance'] ?? null,
                    ])
                </div>
            @endforeach
        </div>
    </div>

    <style>
        details summary::-webkit-details-marker {
            display: none;
        }
        details summary::after {
            content: '▾';
            margin-left: 0.5rem;
            display: inline-block;
            transition: transform 0.2s;
        }
        details[open] summary::after {
            transform: rotate(180deg);
        }
        #selectedMetrics {
            background-image: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }
    </style>

    <!-- JavaScript to Handle Chart Updates -->
    <script>
        // Store the selected dates globally to persist across renders
        window.selectedDates = window.selectedDates || { start: null, end: null };
        let debounceTimeout = null;

        // Debounce function to prevent multiple rapid updates
        function debounce(func, wait) {
            return function(...args) {
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Handle selectionUpdated event for zoom/selection
        const handleSelectionUpdated = debounce(function(event) {
            const { start, end } = event.detail;

            // Update the global selectedDates
            window.selectedDates.start = start;
            window.selectedDates.end = end;

            // Update the UI with selected dates
            document.getElementById('selected-dates').textContent = `${start} to ${end}`;

            // Trigger the hidden button to update the server
            const updateButton = document.getElementById('update-date-range');
            updateButton.dataset.start = start;
            updateButton.dataset.end = end;
            updateButton.click();
        }, 300);

        document.addEventListener('selectionUpdated', handleSelectionUpdated);

        // Ensure the label persists after Livewire re-renders
        document.addEventListener('livewire:load', function() {
            if (window.selectedDates.start && window.selectedDates.end) {
                document.getElementById('selected-dates').textContent = `${window.selectedDates.start} to ${window.selectedDates.end}`;
            }
        });

        // Store additional chart options globally
        window.additionalChartOptions = window.additionalChartOptions || {};

        document.addEventListener('additional-chart-added', function() {
            setTimeout(() => {
                // Destroy existing additional chart instances to prevent stale references
                document.querySelectorAll('[id^=chart-additional-chart-]').forEach(function(chartElement) {
                    const chartId = chartElement.id;
                    if (window.chartInstances[chartId]) {
                        window.chartInstances[chartId].destroy();
                        delete window.chartInstances[chartId];
                    }
                });
                // Initialize all additional charts
                document.querySelectorAll('[id^=chart-additional-chart-]').forEach(function(chartElement) {
                    const chartId = chartElement.id;
                    if (!window.chartInstances[chartId]) {
                        const options = JSON.parse(chartElement.getAttribute('data-options') || '{}');
                        if (Object.keys(options).length > 0) {
                            window.initializeChart(chartId, options);
                        } else {
                            console.warn('No options found for chart:', chartId);
                        }
                    }
                });
            }, 200); // Increased delay to ensure DOM is ready
        });

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Livewire !== 'undefined') {
                Livewire.hook('message.processed', () => {
                    const chartElement = document.querySelector(`#${chartId}`);
                    if (chartElement && !window.chartInstances[chartId]) {
                        setTimeout(setupChart, 200);
                    }
                });
            } else {
                console.warn('Livewire not defined, skipping hook');
            }

            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    const chartElement = document.querySelector(`#${chartId}`);
                    if (!chartElement && window.chartInstances[chartId]) {
                        window.chartInstances[chartId].destroy();
                        delete window.chartInstances[chartId];
                    } else if (chartElement && !window.chartInstances[chartId]) {
                        setupChart();
                    }
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        });

        document.addEventListener('livewire:init', function() {
            Livewire.on('confirm-clear-charts', (data) => {
                if (window.confirm('This will remove the Similar Time Series Charts, do you want to continue?')) {
                    Livewire.dispatch(data.method);
                }
            });
        });
    </script>
</x-filament-panels::page>
