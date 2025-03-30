<x-filament-panels::page>
    <div class="p-6">
        <div class="mb-4 flex items-center gap-4">
            <label for="selectedPeriod" class="text-lg font-medium text-gray-500">Period</label>
            <select
                id="selectedPeriod"
                wire:model.live="selectedPeriod"
                class="block bg-gray-800 text-gray-200 border-gray-600 rounded-lg p-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
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
                <option value="0d">All-Time</option>
            </select>
        </div>
        <div class="w-full">
            @include('filament.components.btc-chart', [
                'label' => '',
                'name' => 'btc-price',
                'hint' => null,
                'options' => $this->chartData['options'] ?? [],
                'rawExtraJsOptions' => $this->chartData['extraJsOptions'] ?? [],
            ])
        </div>
        <div class="w-full flex items-center gap-3 mt-6">
            <button id="custom-date-button" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                style="background-color: #1f2937;">
                Search Similar Time Series
            </button>
            <span id="selected-dates" class="text-sm text-gray-500 dark:text-gray-400"></span>
        </div>
    </div>
</x-filament-panels::page>
