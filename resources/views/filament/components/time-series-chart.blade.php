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
            <x-heroicon-o-information-circle class="w-4 h-4 mr-6" style="margin-right: 6px;" />
            <span>{{ $hint }}</span>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
<script>
    window.chartInstances = window.chartInstances || {};
    const chartId = 'chart-{{ $name ?? 'time-series-' . uniqid() }}';
    // Store original bounds globally
    window.chartOriginalBounds = window.chartOriginalBounds || {};

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

            // Store original bounds before any modifications
            window.chartOriginalBounds[chartId] = {
                min: options.xaxis.min,
                max: options.xaxis.max
            };

            // Ensure zoom reset uses original bounds
            options.chart = options.chart || {};
            options.chart.events = options.chart.events || {};
            options.chart.events.beforeResetZoom = function(chartContext, opts) {
                return {
                    xaxis: {
                        min: window.chartOriginalBounds[chartId].min,
                        max: window.chartOriginalBounds[chartId].max
                    }
                };
            };

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
                axis.labels.formatter = function (value) {
                    if (value >= 1_000_000_000_000_000_000_000) return (value / 1_000_000_000_000_000_000_000).toFixed(1) + 'Z'; // Zetta (10^21)
                    if (value >= 1_000_000_000_000_000_000) return (value / 1_000_000_000_000_000_000).toFixed(1) + 'E';    // Exa (10^18)
                    if (value >= 1_000_000_000_000_000) return (value / 1_000_000_000_000_000).toFixed(1) + 'P';       // Peta (10^15)
                    if (value >= 1_000_000_000_000) return (value / 1_000_000_000_000).toFixed(1) + 'T';              // Tera (10^12)
                    if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1) + 'B';                      // Billion (10^9)
                    if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'M';                              // Million (10^6)
                    if (value >= 1_000) return (value / 1_000).toFixed(1) + 'k';                                      // Kilo (10^3)
                    return value.toFixed(0);                                                                          // Raw value (no suffix)
                };
            });
        } else {
            options.yaxis.labels = options.yaxis.labels || {};
            options.yaxis.labels.formatter = function (value) {
                if (value >= 1_000_000_000_000_000_000_000) return (value / 1_000_000_000_000_000_000_000).toFixed(1) + 'Z'; // Zetta (10^21)
                if (value >= 1_000_000_000_000_000_000) return (value / 1_000_000_000_000_000_000).toFixed(1) + 'E';    // Exa (10^18)
                if (value >= 1_000_000_000_000_000) return (value / 1_000_000_000_000_000).toFixed(1) + 'P';       // Peta (10^15)
                if (value >= 1_000_000_000_000) return (value / 1_000_000_000_000).toFixed(1) + 'T';              // Tera (10^12)
                if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1) + 'B';                      // Billion (10^9)
                if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'M';                              // Million (10^6)
                if (value >= 1_000) return (value / 1_000).toFixed(1) + 'k';                                      // Kilo (10^3)
                return value.toFixed(0);                                                                          // Raw value (no suffix)
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
        const zoomToAnnotation = eventData?.zoomToAnnotation || false;

        if (chartIdFromEvent === chartId && options?.series) {
            const chartElement = document.querySelector(`#${chartId}`);
            if (!chartElement) {
                console.error('Chart element not found during refresh:', chartId);
                return;
            }

            options.extraJsOptions = @json($rawExtraJsOptions);

            // Preserve original bounds if not set
            if (!window.chartOriginalBounds[chartId]) {
                window.chartOriginalBounds[chartId] = {
                    min: options.xaxis.min,
                    max: options.xaxis.max
                };
            }

            // Ensure reset uses original bounds
            options.chart = options.chart || {};
            options.chart.events = options.chart.events || {};
            options.chart.events.beforeResetZoom = function(chartContext, opts) {
                return {
                    xaxis: {
                        min: window.chartOriginalBounds[chartId].min,
                        max: window.chartOriginalBounds[chartId].max
                    }
                };
            };

            // Y-axis formatting
            options.yaxis = options.yaxis || [];
            if (Array.isArray(options.yaxis)) {
                options.yaxis.forEach(axis => {
                    axis.labels = axis.labels || {};
                    axis.labels.formatter = function (value) {
                        if (value >= 1_000_000_000_000_000_000_000) return (value / 1_000_000_000_000_000_000_000).toFixed(1) + 'Z'; // Zetta (10^21)
                        if (value >= 1_000_000_000_000_000_000) return (value / 1_000_000_000_000_000_000).toFixed(1) + 'E';    // Exa (10^18)
                        if (value >= 1_000_000_000_000_000) return (value / 1_000_000_000_000_000).toFixed(1) + 'P';       // Peta (10^15)
                        if (value >= 1_000_000_000_000) return (value / 1_000_000_000_000).toFixed(1) + 'T';              // Tera (10^12)
                        if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1) + 'B';                      // Billion (10^9)
                        if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'M';                              // Million (10^6)
                        if (value >= 1_000) return (value / 1_000).toFixed(1) + 'k';                                      // Kilo (10^3)
                        return value.toFixed(0);                                                                          // Raw value (no suffix)
                    };
                });
            } else {
                options.yaxis.labels = options.yaxis.labels || {};
                options.yaxis.labels.formatter = function (value) {
                    if (value >= 1_000_000_000_000_000_000_000) return (value / 1_000_000_000_000_000_000_000).toFixed(1) + 'Z'; // Zetta (10^21)
                    if (value >= 1_000_000_000_000_000_000) return (value / 1_000_000_000_000_000_000).toFixed(1) + 'E';    // Exa (10^18)
                    if (value >= 1_000_000_000_000_000) return (value / 1_000_000_000_000_000).toFixed(1) + 'P';       // Peta (10^15)
                    if (value >= 1_000_000_000_000) return (value / 1_000_000_000_000).toFixed(1) + 'T';              // Tera (10^12)
                    if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1) + 'B';                      // Billion (10^9)
                    if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'M';                              // Million (10^6)
                    if (value >= 1_000) return (value / 1_000).toFixed(1) + 'k';                                      // Kilo (10^3)
                    return value.toFixed(0);                                                                          // Raw value (no suffix)
                };
            }

            // Preserve event handlers
            const originalSelection = options.chart.events.selection;
            const originalZoomed = options.chart.events.zoomed;
            const originalUpdated = options.chart.events.updated;

            options.chart.events.selection = function(chartContext, { xaxis }) {
                if (typeof originalSelection === 'function') originalSelection(chartContext, { xaxis });
                if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                    const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                    const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                    if (window.lastSelectedDates && window.lastSelectedDates.start === startDate && window.lastSelectedDates.end === endDate) {
                        return;
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
                        return;
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
                        return;
                    }
                    window.lastSelectedDates = { start: minDate, end: maxDate };
                    window.selectedDates = { start: minDate, end: maxDate };
                    document.dispatchEvent(new CustomEvent("selectionUpdated", { detail: { start: minDate, end: maxDate } }));
                }
            };

            // Destroy and re-render
            if (window.chartInstances[chartId]) {
                window.chartInstances[chartId].destroy();
                delete window.chartInstances[chartId];
            }
            setTimeout(() => {
                window.initializeChart(chartId, options);
                // Zoom to annotation if requested
                if (zoomToAnnotation && options.annotations?.xaxis?.[0]) {
                    const annotation = options.annotations.xaxis[0];
                    const startMs = annotation.x;
                    const endMs = annotation.x2;
                    const bufferMs = 30 * 24 * 60 * 60 * 1000; // 30 days in ms
                    const newMin = Math.max(startMs - bufferMs, window.chartOriginalBounds[chartId].min);
                    const newMax = Math.min(endMs + bufferMs, window.chartOriginalBounds[chartId].max);
                    window.chartInstances[chartId].zoomX(newMin, newMax);
                }
            }, 100);
        }
    });
</script>
