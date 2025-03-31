<!-- powered by grok 🤖 -->
<div class="filament-view-field">
    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
        {{ $label ?? 'Chart' }}
    </div>

    <div wire:ignore style="height: 300px; max-height: 300px; overflow: hidden;">
        <div id="chart-{{ $name ?? 'time-series-' . uniqid() }}" style="width: 100%; height: 300px; min-height: 300px; max-height: 300px; position: relative; overflow: visible !important;"></div>
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
    const chartId = 'chart-{{ $name ?? 'time-series-' . uniqid() }}';

    // Format dates as "MMM dd yyyy"
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }).replace(/(\d+)/g, '$1').replace(',', '');
    }

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
                    window.chartInstances[chartId].updateOptions({ chart: { width: '100%', height: '300px' } }, false, true);
                }
            });
            resizeObserver.observe(chartElement);

            const fullDates = options.extraJsOptions?.fullDates || [];
            if (fullDates.length > 0) {
                // Preserve selected dates if they exist, else use full range
                const startDate = window.selectedDates?.start || fullDates[0];
                const endDate = window.selectedDates?.end || fullDates[fullDates.length - 1];
                window.selectedDates = { start: startDate, end: endDate };
                document.getElementById('selected-dates').textContent = `${formatDate(startDate)} to ${formatDate(endDate)}`;
                document.dispatchEvent(new CustomEvent("selectionUpdated", {
                    detail: { start: startDate, end: endDate }
                }));
            }
        }
    }

    function setupChart() {
        const options = @json($options);
        const extraJsOptions = @json($rawExtraJsOptions);
        options.extraJsOptions = extraJsOptions;
        const fullDates = extraJsOptions.fullDates || [];

        options.chart = options.chart || {};
        options.chart.events = options.chart.events || {};

        // Y-axis formatting
        options.yaxis = options.yaxis || [];
        if (Array.isArray(options.yaxis)) {
            options.yaxis.forEach(axis => {
                axis.labels = axis.labels || {};
                axis.labels.formatter = function(value) {
                    if (value >= 1_000_000_000_000) return (value / 1_000_000_000_000).toFixed(1) + 'T';
                    if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1) + 'B';
                    if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'M';
                    if (value >= 1_000) return (value / 1_000).toFixed(1) + 'k';
                    return value.toFixed(0);
                };
            });
        } else {
            options.yaxis.labels = options.yaxis.labels || {};
            options.yaxis.labels.formatter = function(value) {
                if (value >= 1_000_000_000_000) return (value / 1_000_000_000_000).toFixed(1) + 'T';
                if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1) + 'B';
                if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'M';
                if (value >= 1_000) return (value / 1_000).toFixed(1) + 'k';
                return value.toFixed(0);
            };
        }

        const originalSelection = options.chart.events.selection;
        const originalZoomed = options.chart.events.zoomed;
        const originalUpdated = options.chart.events.updated;

        options.chart.events.selection = function(chartContext, { xaxis }) {
            if (typeof originalSelection === 'function') originalSelection(chartContext, { xaxis });
            if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                window.selectedDates = { start: startDate, end: endDate };
                document.getElementById('selected-dates').textContent = `${formatDate(startDate)} to ${formatDate(endDate)}`;
                document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: startDate, end: endDate } }));
            }
        };

        options.chart.events.zoomed = function(chartContext, { xaxis }) {
            if (typeof originalZoomed === 'function') originalZoomed(chartContext, { xaxis });
            if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                window.selectedDates = { start: startDate, end: endDate };
                document.getElementById('selected-dates').textContent = `${formatDate(startDate)} to ${formatDate(endDate)}`;
                document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: startDate, end: endDate } }));
            }
        };

        options.chart.events.updated = function(chartContext) {
            if (typeof originalUpdated === 'function') originalUpdated(chartContext);
            const xaxis = chartContext.w.globals.initialConfig.xaxis;
            if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                const minDate = new Date(xaxis.min).toISOString().split("T")[0];
                const maxDate = new Date(xaxis.max).toISOString().split("T")[0];
                window.selectedDates = { start: minDate, end: maxDate };
                document.getElementById('selected-dates').textContent = `${formatDate(minDate)} to ${formatDate(maxDate)}`;
                document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: minDate, end: maxDate } }));
            }
        };

        initializeChart(chartId, options);
    }

    document.addEventListener('DOMContentLoaded', setupChart);

    document.addEventListener('livewire:load', function () {
        Livewire.hook('message.processed', () => {
            const chartElement = document.querySelector(`#${chartId}`);
            if (chartElement && !window.chartInstances[chartId]) {
                setTimeout(setupChart, 200);
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
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

    window.addEventListener('beforeunload', () => {
        for (const id in window.chartInstances) {
            if (window.chartInstances[id]) window.chartInstances[id].destroy();
        }
        window.chartInstances = {};
    });

    document.addEventListener('selectionUpdated', function (event) {
        const { start, end } = event.detail;
        document.getElementById('selected-dates').textContent = `${formatDate(start)} to ${formatDate(end)}`;
    });

    window.addEventListener('refresh-chart', function (event) {
        const eventData = Array.isArray(event.detail) && event.detail.length > 0 ? event.detail[0] : event.detail;
        const chartIdFromEvent = eventData?.chartId;
        const options = eventData?.options;

        if (chartIdFromEvent === chartId && options?.series) {
            const extraJsOptions = @json($rawExtraJsOptions);
            options.extraJsOptions = extraJsOptions;

            options.yaxis = options.yaxis || [];
            if (Array.isArray(options.yaxis)) {
                options.yaxis.forEach(axis => {
                    axis.labels = axis.labels || {};
                    axis.labels.formatter = function(value) {
                        if (value >= 1_000_000_000_000) return (value / 1_000_000_000_000).toFixed(1) + 'T';
                        if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1) + 'B';
                        if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'M';
                        if (value >= 1_000) return (value / 1_000).toFixed(1) + 'k';
                        return value.toFixed(0);
                    };
                });
            } else {
                options.yaxis.labels = options.yaxis.labels || {};
                options.yaxis.labels.formatter = function(value) {
                    if (value >= 1_000_000_000_000) return (value / 1_000_000_000_000).toFixed(1) + 'T';
                    if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1) + 'B';
                    if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'M';
                    if (value >= 1_000) return (value / 1_000).toFixed(1) + 'k';
                    return value.toFixed(0);
                };
            }

            options.chart = options.chart || {};
            options.chart.events = options.chart.events || {};

            const originalSelection = options.chart.events.selection;
            const originalZoomed = options.chart.events.zoomed;
            const originalUpdated = options.chart.events.updated;

            options.chart.events.selection = function(chartContext, { xaxis }) {
                if (typeof originalSelection === 'function') originalSelection(chartContext, { xaxis });
                if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                    const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                    const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                    window.selectedDates = { start: startDate, end: endDate };
                    document.getElementById('selected-dates').textContent = `${formatDate(startDate)} to ${formatDate(endDate)}`;
                    document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: startDate, end: endDate } }));
                }
            };

            options.chart.events.zoomed = function(chartContext, { xaxis }) {
                if (typeof originalZoomed === 'function') originalZoomed(chartContext, { xaxis });
                if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                    const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                    const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                    window.selectedDates = { start: startDate, end: endDate };
                    document.getElementById('selected-dates').textContent = `${formatDate(startDate)} to ${formatDate(endDate)}`;
                    document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: startDate, end: endDate } }));
                }
            };

            options.chart.events.updated = function(chartContext) {
                if (typeof originalUpdated === 'function') originalUpdated(chartContext);
                const xaxis = chartContext.w.globals.initialConfig.xaxis;
                if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                    const minDate = new Date(xaxis.min).toISOString().split("T")[0];
                    const maxDate = new Date(xaxis.max).toISOString().split("T")[0];
                    window.selectedDates = { start: minDate, end: maxDate };
                    document.getElementById('selected-dates').textContent = `${formatDate(minDate)} to ${formatDate(maxDate)}`;
                    document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: minDate, end: maxDate } }));
                }
            };

            if (window.chartInstances[chartId]) {
                window.chartInstances[chartId].updateOptions(options, true, true);
            } else {
                initializeChart(chartId, options);
            }
        }
    });
</script>
