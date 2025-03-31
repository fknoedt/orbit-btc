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
        }
    }

    function setupChart() {
        const options = @json($options);
        const extraJsOptions = @json($rawExtraJsOptions);
        const fullDates = extraJsOptions.fullDates || [];

        // Ensure chart events are set up
        options.chart = options.chart || {};
        options.chart.events = options.chart.events || {};

        // Add y-axis formatter with extended human-readable scales
        options.yaxis = options.yaxis || [];
        if (Array.isArray(options.yaxis)) {
            options.yaxis.forEach(axis => {
                axis.labels = axis.labels || {};
                axis.labels.formatter = function(value) {
                    if (value >= 1_000_000_000_000) {
                        return (value / 1_000_000_000_000).toFixed(1) + 'T';
                    } else if (value >= 1_000_000_000) {
                        return (value / 1_000_000_000).toFixed(1) + 'B';
                    } else if (value >= 1_000_000) {
                        return (value / 1_000_000).toFixed(1) + 'M';
                    } else if (value >= 1_000) {
                        return (value / 1_000).toFixed(1) + 'k';
                    } else {
                        return value.toFixed(0);
                    }
                };
            });
        } else {
            options.yaxis.labels = options.yaxis.labels || {};
            options.yaxis.labels.formatter = function(value) {
                if (value >= 1_000_000_000_000) {
                    return (value / 1_000_000_000_000).toFixed(1) + 'T';
                } else if (value >= 1_000_000_000) {
                    return (value / 1_000_000_000).toFixed(1) + 'B';
                } else if (value >= 1_000_000) {
                    return (value / 1_000_000).toFixed(1) + 'M';
                } else if (value >= 1_000) {
                    return (value / 1_000).toFixed(1) + 'k';
                } else {
                    return value.toFixed(0);
                }
            };
        }

        // Keep the existing events from the options
        const originalSelection = options.chart.events.selection;
        const originalZoomed = options.chart.events.zoomed;
        const originalUpdated = options.chart.events.updated;

        options.chart.events.selection = function(chartContext, { xaxis, yaxis }) {
            if (typeof originalSelection === 'function') {
                originalSelection(chartContext, { xaxis, yaxis });
            }
            if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                window.selectedDates = { start: startDate, end: endDate };
                document.dispatchEvent(new CustomEvent("selectionUpdated", {
                    detail: { start: startDate, end: endDate }
                }));
            }
        };

        options.chart.events.zoomed = function(chartContext, { xaxis, yaxis }) {
            if (typeof originalZoomed === 'function') {
                originalZoomed(callbackContext, { xaxis, yaxis });
            }
            if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                window.selectedDates = { start: startDate, end: endDate };
                document.dispatchEvent(new CustomEvent("selectionUpdated", {
                    detail: { start: startDate, end: endDate }
                }));
            }
        };

        options.chart.events.updated = function(chartContext) {
            if (typeof originalUpdated === 'function') {
                originalUpdated(chartContext);
            }
            const xaxis = chartContext.w.globals.initialConfig.xaxis;
            if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                const minDate = new Date(xaxis.min).toISOString().split("T")[0];
                const maxDate = new Date(xaxis.max).toISOString().split("T")[0];
                window.selectedDates = { start: minDate, end: maxDate };
                document.dispatchEvent(new CustomEvent("selectionUpdated", {
                    detail: { start: minDate, end: maxDate }
                }));
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

    window.addEventListener('beforeunload', () => {
        for (const chartId in window.chartInstances) {
            if (window.chartInstances[chartId]) {
                window.chartInstances[chartId].destroy();
            }
        }
        window.chartInstances = {};
    });

    // Add the button event listener
    document.addEventListener('DOMContentLoaded', function () {
        const button = document.getElementById('custom-date-button');
        if (button) {
            button.addEventListener('click', function () {
                const { start, end } = window.selectedDates || {};
                if (start && end) {
                    document.getElementById('selected-dates').textContent = start && end
                        ? `Selected: ${start} to ${end}`
                        : 'No dates selected';
                } else {
                    alert('Please select or zoom into an area on the chart first.');
                }
            });
        } else {
            console.error('Custom date button not found');
        }
    });

    // Listen for selection updates from the chart
    document.addEventListener('selectionUpdated', function (event) {
        const { start, end } = event.detail;
        document.getElementById('selected-dates').textContent = start && end
            ? `Selected: ${start} to ${end}`
            : 'No dates selected';
    });

    window.addEventListener('refresh-chart', function (event) {
        const eventData = Array.isArray(event.detail) && event.detail.length > 0 ? event.detail[0] : event.detail;
        const chartIdFromEvent = eventData?.chartId;
        const options = eventData?.options;

        if (chartIdFromEvent === chartId && options?.series) {
            const extraJsOptions = @json($rawExtraJsOptions);
            const fullDates = extraJsOptions.fullDates || [];

            // Add y-axis formatter with extended human-readable scales for updates
            options.yaxis = options.yaxis || [];
            if (Array.isArray(options.yaxis)) {
                options.yaxis.forEach(axis => {
                    axis.labels = axis.labels || {};
                    axis.labels.formatter = function(value) {
                        if (value >= 1_000_000_000_000) {
                            return (value / 1_000_000_000_000).toFixed(1) + 'T';
                        } else if (value >= 1_000_000_000) {
                            return (value / 1_000_000_000).toFixed(1) + 'B';
                        } else if (value >= 1_000_000) {
                            return (value / 1_000_000).toFixed(1) + 'M';
                        } else if (value >= 1_000) {
                            return (value / 1_000).toFixed(1) + 'k';
                        } else {
                            return value.toFixed(0);
                        }
                    };
                });
            } else {
                options.yaxis.labels = options.yaxis.labels || {};
                options.yaxis.labels.formatter = function(value) {
                    if (value >= 1_000_000_000_000) {
                        return (value / 1_000_000_000_000).toFixed(1) + 'T';
                    } else if (value >= 1_000_000_000) {
                        return (value / 1_000_000_000).toFixed(1) + 'B';
                    } else if (value >= 1_000_000) {
                        return (value / 1_000_000).toFixed(1) + 'M';
                    } else if (value >= 1_000) {
                        return (value / 1_000).toFixed(1) + 'k';
                    } else {
                        return value.toFixed(0);
                    }
                };
            }

            // Update options with event handlers
            options.chart = options.chart || {};
            options.chart.events = options.chart.events || {};

            const originalSelection = options.chart.events.selection;
            const originalZoomed = options.chart.events.zoomed;
            const originalUpdated = options.chart.events.updated;

            options.chart.events.selection = function(chartContext, { xaxis, yaxis }) {
                if (typeof originalSelection === 'function') {
                    originalSelection(chartContext, { xaxis, yaxis });
                }
                if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                    const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                    const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                    window.selectedDates = { start: startDate, end: endDate };
                    document.dispatchEvent(new CustomEvent("selectionUpdated", {
                        detail: { start: startDate, end: endDate }
                    }));
                }
            };

            options.chart.events.zoomed = function(chartContext, { xaxis, yaxis }) {
                if (typeof originalZoomed === 'function') {
                    originalZoomed(chartContext, { xaxis, yaxis });
                }
                if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                    const startDate = new Date(xaxis.min).toISOString().split("T")[0];
                    const endDate = new Date(xaxis.max).toISOString().split("T")[0];
                    window.selectedDates = { start: startDate, end: endDate };
                    document.dispatchEvent(new CustomEvent("selectionUpdated", {
                        detail: { start: startDate, end: endDate }
                    }));
                }
            };

            options.chart.events.updated = function(chartContext) {
                if (typeof originalUpdated === 'function') {
                    originalUpdated(chartContext);
                }
                const xaxis = chartContext.w.globals.initialConfig.xaxis;
                if (xaxis.min && xaxis.max && !isNaN(xaxis.min) && !isNaN(xaxis.max)) {
                    const minDate = new Date(xaxis.min).toISOString().split("T")[0];
                    const maxDate = new Date(xaxis.max).toISOString().split("T")[0];
                    window.selectedDates = { start: minDate, end: maxDate };
                    document.dispatchEvent(new CustomEvent("selectionUpdated", {
                        detail: { start: minDate, end: maxDate }
                    }));
                }
            };

            if (window.chartInstances[chartId]) {
                window.chartInstances[chartId].updateOptions(options, true, true);
            } else {
                initializeChart(chartId, options);
            }
        }
    });

    // Update the Livewire hook to use the newer syntax
    document.addEventListener('livewire:init', function () {
        Livewire.on('message.processed', (message, component) => {
            const chartElement = document.querySelector(`#${chartId}`);
            if (chartElement && !window.chartInstances[chartId]) {
                setTimeout(() => {
                    setupChart();
                }, 200);
            }
        });
    });
</script>
