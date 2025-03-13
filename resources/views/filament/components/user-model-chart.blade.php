<!-- powered by grok 🤖 -->
<div class="filament-view-field">
    <!-- Label -->
    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
        {{ $getLabel() }}
    </div>

    <!-- Chart -->
    <div id="chart-{{ $getName() }}" style="width: 100%; height: 350px; min-height: 350px; position: relative; overflow: visible !important;"></div>

    <!-- Hint -->
    @if ($getHint())
        <div class="text-sm text-gray-700 dark:text-gray-300 mt-0.5 flex items-center">
            <x-heroicon-o-information-circle class="w-4 h-4 mr-6" />
            <span>{{ $getHint() }}</span>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
<script>
    let chartInstance = null;
    let resizeObserver = null;

    document.addEventListener('DOMContentLoaded', function () {
        const chartId = '#chart-{{ $getName() }}';
        const options = @json($options);

        initializeChart(chartId, options);
    });

    function initializeChart(chartId, options) {
        const chartElement = document.querySelector(chartId);
        if (chartElement) {
            if (chartInstance) {
                chartInstance.destroy();
            }

            chartInstance = new ApexCharts(chartElement, options);
            chartInstance.render();

            // Add resize observer to handle dynamic size changes
            if (resizeObserver) resizeObserver.disconnect();
            resizeObserver = new ResizeObserver(() => {
                if (chartInstance) {
                    chartInstance.updateOptions({ width: '100%', height: '350px' }, false, true);
                }
            });
            resizeObserver.observe(chartElement);
        }
    }

    window.addEventListener('refresh-chart', function (event) {
        const chartId = '#chart-{{ $getName() }}';
        const eventData = Array.isArray(event.detail) && event.detail.length > 0 ? event.detail[0] : null;
        const chartIdFromEvent = eventData?.chartId;
        const optionsFromEvent = eventData?.options;

        if (chartIdFromEvent === 'chart-daily-score' && document.querySelector(chartId) && optionsFromEvent?.series && optionsFromEvent?.chart) {
            setTimeout(() => {
                initializeChart(chartId, optionsFromEvent);
            }, 200);
        }
    });

    document.addEventListener('livewire:load', function () {
        Livewire.hook('message.processed', () => {
            const chartId = '#chart-{{ $getName() }}';
            const options = @json($options);
            if (document.querySelector(chartId) && !chartInstance) {
                setTimeout(() => {
                    initializeChart(chartId, options);
                }, 200);
            }
        });
    });
</script>
