<!-- powered by grok 🤖 -->
<div class="filament-view-field">
    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
        {{ $label ?? 'Chart' }}
    </div>

    <div wire:ignore style="height: 300px; max-height: 300px; overflow: hidden;">
        <div id="chart-{{ $name }}" data-options="{{ json_encode($options) }}" style="width: 100%; height: 300px; min-height: 300px; max-height: 300px; position: relative; overflow: visible !important;"></div>
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

    // Make initializeChart globally accessible
    window.initializeChart = window.initializeChart || function(chartId, options) {
        const chartElement = document.querySelector(`#${chartId}`);
        if (chartElement) {
            // Destroy existing chart instance if it exists
            if (window.chartInstances[chartId]) {
                window.chartInstances[chartId].destroy();
                delete window.chartInstances[chartId];
            }

            // Set initial selected dates without dispatching event
            const fullDates = options.extraJsOptions?.fullDates || [];
            if (fullDates.length > 0) {
                const startDate = window.selectedDates?.start || fullDates[0];
                const endDate = window.selectedDates?.end || fullDates[fullDates.length - 1];
                window.selectedDates = { start: startDate, end: endDate };
            }

            const chart = new ApexCharts(chartElement, options);
            chart.render();
            window.chartInstances[chartId] = chart;

            // Resize observer to handle dynamic resizing
            const resizeObserver = new ResizeObserver(() => {
                if (window.chartInstances[chartId]) {
                    window.chartInstances[chartId].updateOptions({ chart: { width: '100%', height: '300px' } }, false, true);
                }
            });
            resizeObserver.observe(chartElement);
        } else {
            console.error('Chart element not found:', chartId);
        }
    };

    function setupChart() {
        let options = @json($options);
        const extraJsOptions = @json($rawExtraJsOptions);
        options.extraJsOptions = extraJsOptions;

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

        // Ensure chart events are defined
        options.chart = options.chart || {};
        options.chart.events = options.chart.events || {};

        // Preserve original event handlers
        const originalSelection = options.chart.events.selection;
        const originalZoomed = options.chart.events.zoomed;
        const originalUpdated = options.chart.events.updated;

        options.chart.events.selection = function(chartContext, { xaxis }) {
            if (typeof originalSelection === 'function') originalSelection(chartContext, { xaxis });
            if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                if (window.lastSelectedDates && window.lastSelectedDates.start === startDate && window.lastSelectedDates.end === endDate) {
                    return; // Prevent duplicate events
                }
                window.lastSelectedDates = { start: startDate, end: endDate };
                window.selectedDates = { start: startDate, end: endDate };
                document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: startDate, end: endDate } }));
            }
        };

        options.chart.events.zoomed = function(chartContext, { xaxis }) {
            if (typeof originalZoomed === 'function') originalZoomed(chartContext, { xaxis });
            if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                if (window.lastSelectedDates && window.lastSelectedDates.start === startDate && window.lastSelectedDates.end === endDate) {
                    return; // Prevent duplicate events
                }
                window.lastSelectedDates = { start: startDate, end: endDate };
                window.selectedDates = { start: startDate, end: endDate };
                document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: startDate, end: endDate } }));
            }
        };

        options.chart.events.updated = function(chartContext) {
            if (typeof originalUpdated === 'function') originalUpdated(chartContext);
            const xaxis = chartContext.w.globals;
            if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                const minDate = new Date(xaxis.min).toISOString().split("T")[0];
                const maxDate = new Date(xaxis.max).toISOString().split("T")[0];
                if (window.lastSelectedDates && window.lastSelectedDates.start === minDate && window.lastSelectedDates.end === maxDate) {
                    return; // Prevent duplicate events
                }
                window.lastSelectedDates = { start: minDate, end: maxDate };
                window.selectedDates = { start: minDate, end: maxDate };
                document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: minDate, end: maxDate } }));
            }
        };

        window.initializeChart(chartId, options);
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

    // Handle chart refresh
    window.addEventListener('refresh-chart', function (event) {
        const eventData = Array.isArray(event.detail) && event.detail.length > 0 ? event.detail[0] : event.detail;
        const chartIdFromEvent = eventData?.chartId;
        const options = eventData?.options;

        if (chartIdFromEvent === chartId && options?.series) {
            const chartElement = document.querySelector(`#${chartId}`);
            if (!chartElement) {
                console.error('Chart element not found during refresh:', chartId);
                return;
            }

            const extraJsOptions = @json($rawExtraJsOptions);
            options.extraJsOptions = extraJsOptions;

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

            // Ensure chart events are defined
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
                    if (window.lastSelectedDates && window.lastSelectedDates.start === startDate && window.lastSelectedDates.end === endDate) {
                        return; // Prevent duplicate events
                    }
                    window.lastSelectedDates = { start: startDate, end: endDate };
                    window.selectedDates = { start: startDate, end: endDate };
                    document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: startDate, end: endDate } }));
                }
            };

            options.chart.events.zoomed = function(chartContext, { xaxis }) {
                if (typeof originalZoomed === 'function') originalZoomed(chartContext, { xaxis });
                if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                    const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                    const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                    if (window.lastSelectedDates && window.lastSelectedDates.start === startDate && window.lastSelectedDates.end === endDate) {
                        return; // Prevent duplicate events
                    }
                    window.lastSelectedDates = { start: startDate, end: endDate };
                    window.selectedDates = { start: startDate, end: endDate };
                    document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: startDate, end: endDate } }));
                }
            };

            options.chart.events.updated = function(chartContext) {
                if (typeof originalUpdated === 'function') originalUpdated(chartContext);
                const xaxis = chartContext.w.globals;
                if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                    const minDate = new Date(xaxis.min).toISOString().split("T")[0];
                    const maxDate = new Date(xaxis.max).toISOString().split("T")[0];
                    if (window.lastSelectedDates && window.lastSelectedDates.start === minDate && window.lastSelectedDates.end === maxDate) {
                        return; // Prevent duplicate events
                    }
                    window.lastSelectedDates = { start: minDate, end: maxDate };
                    window.selectedDates = { start: minDate, end: maxDate };
                    document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: minDate, end: maxDate } }));
                }
            };

            // Destroy and re-render the chart to ensure a full refresh
            if (window.chartInstances[chartId]) {
                window.chartInstances[chartId].destroy();
                delete window.chartInstances[chartId];
            }
            // Delay the render to ensure the DOM is ready
            setTimeout(() => {
                window.initializeChart(chartId, options);
            }, 100);
        }
    });
</script>
