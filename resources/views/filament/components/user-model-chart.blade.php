<!-- powered by grok 🤖 -->
<div class="filament-view-field">
    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
        {{ $label ?? 'Chart' }}
    </div>

    <div wire:ignore>
        <div id="chart-{{ $name ?? 'user-model-chart-' . uniqid() }}" style="width: 100%; height: 350px; min-height: 350px; position: relative; overflow: visible !important;"></div>
    </div>

    @if (isset($hint) && $hint)
        <div class="text-sm text-gray-700 dark:text-gray-300 mt-0.5 flex items-center">
            <x-heroicon-o-information-circle class="w-4 h-4 mr-6" />
            <span>{{ $hint }}</span>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
<script>
    window.chartInstances = window.chartInstances || {};
    const chartId = 'chart-{{ $name ?? 'user-model-chart-' . uniqid() }}';

    function initializeChart(chartId, options) {
        const chartElement = document.querySelector(`#${chartId}`);
        if (chartElement) {
            if (window.chartInstances[chartId]) {
                window.chartInstances[chartId].destroy();
                delete window.chartInstances[chartId];
            }

            const chart = new ApexCharts(chartElement, options);
            chart.render();
            window.chartInstances[chartId] = chart;

            const resizeObserver = new ResizeObserver(() => {
                if (window.chartInstances[chartId]) {
                    window.chartInstances[chartId].updateOptions({ chart: { width: '100%', height: '350px' } }, false, true);
                }
            });
            resizeObserver.observe(chartElement);
        }
    }

    function setupChart() {
        const options = @json($options);
        const extraJsOptions = @json($rawExtraJsOptions);
        const fullDates = extraJsOptions.fullDates || [];
        options.chart = options.chart || {};
        options.chart.events = options.chart.events || {};
        options.chart.events.dataPointSelection = function(event, chartContext, config) {
            if (config.seriesIndex !== undefined && config.dataPointIndex !== undefined) {
                const clickedDate = fullDates[config.dataPointIndex];
                if (clickedDate) {
                    document.dispatchEvent(new CustomEvent("open-chart-modal", { detail: { date: clickedDate } }));
                }
            }
        };

        initializeChart(chartId, options);
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupChart();
    });

    document.addEventListener('livewire:load', function () {
        Livewire.hook('message.processed', () => {
            const chartElement = document.querySelector(`#${chartId}`);
            if (chartElement && !window.chartInstances[chartId]) {
                setTimeout(() => {
                    setupChart();
                }, 200);
            }
        });
    });

    window.addEventListener('refresh-chart', function (event) {
        const eventData = Array.isArray(event.detail) && event.detail.length > 0 ? event.detail[0] : null;
        const chartIdFromEvent = eventData?.chartId;
        let options = eventData?.options;

        if (chartIdFromEvent === chartId && options?.series && options?.chart) {
            const extraJsOptions = @json($rawExtraJsOptions);
            const fullDates = extraJsOptions.fullDates || [];
            options.chart = options.chart || {};
            options.chart.events = options.chart.events || {};
            options.chart.events.dataPointSelection = function(event, chartContext, config) {
                if (config.seriesIndex !== undefined && config.dataPointIndex !== undefined) {
                    const clickedDate = fullDates[config.dataPointIndex];
                    if (clickedDate) {
                        document.dispatchEvent(new CustomEvent("open-chart-modal", { detail: { date: clickedDate } }));
                    }
                }
            };
            setTimeout(() => {
                initializeChart(chartId, options);
            }, 500);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const targetNode = document.body;
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
        observer.observe(targetNode, { childList: true, subtree: true });
    });

    document.addEventListener('livewire:modal-opened', function (event) {
        if (event.detail.id === 'chartDetailModal') {
            const chartElement = document.querySelector(`#${chartId}`);
            if (chartElement && window.chartInstances[chartId]) {
                setTimeout(() => {
                    window.chartInstances[chartId].updateOptions({ chart: { width: '100%', height: '350px' } }, false, true);
                }, 500);
            } else if (chartElement && !window.chartInstances[chartId]) {
                setupChart();
            }
        }
    });

    document.addEventListener('livewire:modal-closed', function (event) {
        if (event.detail.id === 'chartDetailModal') {
            const chartElement = document.querySelector(`#${chartId}`);
            if (chartElement && window.chartInstances[chartId]) {
                setTimeout(() => {
                    window.chartInstances[chartId].updateOptions({ chart: { width: '100%', height: '350px' } }, false, true);
                }, 500);
            } else if (chartElement && !window.chartInstances[chartId]) {
                setupChart();
            }
        }
    });

    document.addEventListener('open-chart-modal', (event) => {
        window.Livewire.dispatch('open-chart-modal', { date: event.detail.date });
    });

    window.addEventListener('beforeunload', () => {
        for (const chartId in window.chartInstances) {
            if (window.chartInstances[chartId]) {
                window.chartInstances[chartId].destroy();
            }
        }
        window.chartInstances = {};
    });
</script>
