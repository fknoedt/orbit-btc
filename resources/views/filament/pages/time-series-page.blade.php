<x-filament-panels::page>
    <div class="p-6">
        <div class="mb-4 flex justify-end items-center gap-4">
            <div class="flex items-center gap-4">
                <label for="selectedMetric" class="text-lg font-medium text-gray-500">Metric</label>
                <select
                    id="selectedMetric"
                    wire:model.live="selectedMetric"
                    class="block bg-white text-gray-900 border-gray-300 rounded-lg p-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
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
                <button id="custom-date-button" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        style="background-color: #1f2937;">
                    Search for Similar BTC Price Time Series
                </button>
                <span id="selected-dates" class="text-sm text-gray-500 dark:text-gray-400"></span>
            </div>
            <label class="text-lg font-medium text-gray-500">
                Select time ranges and use the button to search for time series similar to the displayed range
            </label>
        </div>
    </div>
</x-filament-panels::page>
