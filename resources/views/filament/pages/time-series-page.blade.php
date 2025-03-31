<x-filament-panels::page>
    <div class="p-6">
        <div class="mb-4 flex justify-between items-center gap-6">
            <div class="flex items-center gap-6">
                <details class="relative">
                    <summary class="text-lg font-medium text-gray-500 cursor-pointer flex items-center gap-1">
                        <span>
                            {{ count($selectedMetric) === 1 ? 'Metric: ' : 'Metrics: ' }}
                            <span class="text-white">
                                {{ collect($selectedMetric)->map(fn($metric) => match($metric) {
                                    'market_cap' => 'Market Cap',
                                    'total_volume' => 'Total Volume',
                                    'close' => 'BTC Price',
                                    'average_fee' => 'Average Fee',
                                    'exchanges_reserve' => 'Exchanges Reserve',
                                    'fear_and_greed' => 'Fear & Greed',
                                    'mayer_multiple' => 'Mayer Multiple',
                                    default => 'BTC Price'
                                })->implode(count($selectedMetric) === 1 ? '' : ' x ') }}
                            </span>
                        </span>
                    </summary>
                    <select
                        id="selectedMetric"
                        wire:model.live="selectedMetric"
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
        <div class="w-full">
            @include('filament.components.time-series-chart', [
                'label' => '',
                'name' => 'btc-price',
                'hint' => null,
                'options' => $chartData['options'] ?? [],
                'rawExtraJsOptions' => $chartData['extraJsOptions'] ?? [],
            ])
        </div>
        <div class="w-full flex flex-col gap-3 mt-6">
            <div class="flex items-center gap-3">
                <button wire:click="searchSimilar" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        style="background-color: #1f2937;">
                    Search for Similar Time Series Pattern
                </button>
                <span id="selected-dates" class="text-sm text-gray-500 dark:text-gray-400"></span>
                <span class="text-lg font-medium text-gray-500 flex items-center gap-1">
                    <x-heroicon-o-question-mark-circle class="w-6 h-6" title="Select time ranges and use the button to search for time series similar to the displayed range" />
                </span>
            </div>
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
        #selectedMetric {
            background-image: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }
    </style>
</x-filament-panels::page>
