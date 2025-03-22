<x-filament-panels::page>
    <div x-data="{ dropdownOpen: false }">
        <x-filament-apex-charts::chart
            :chartId="'dailyScoresChart'"
            :chartOptions="$options"
            :contentHeight="300"
            :deferLoading="false"
            :readyToLoad="true"
            :darkMode="true"
            :pollingInterval="null"
            extraJsOptions="{!! $extraJsOptions !!}"
        />
    </div>

    <script>
        document.addEventListener('open-chart-modal', (event) => {
            window.Livewire.dispatch('open-chart-modal', { date: event.detail.date });
        });
    </script>
</x-filament-panels::page>
