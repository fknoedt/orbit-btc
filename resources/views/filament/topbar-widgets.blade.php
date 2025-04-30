<div class="topbar-widgets-wrapper flex-1">
    <div class="topbar-widgets flex items-center overflow-x-auto">
        @foreach($widgets as $widget)
            @if(!empty($widget['link']))
                <a target="_blank" href="{{ $widget['link'] }}">
                    @endif
                    <div class="topbar-widget rounded-md shadow-sm p-1.5 relative">
                        <!-- Value: Top-left -->
                        <span class="absolute top-0 left-0 text-xl font-semibold widget-value"
                              data-widget-id="{{ $widget['id'] ?? '' }}"
                              style="color: {{ $widget['color'] ?? 'inherit' }};">
                    {{ $widget['value'] }}
                </span>
                        <!-- Label: Bottom-left -->
                        <span class="absolute bottom-0 left-0 text-xs" style="color: {{ $widget['label_color'] ?? 'inherit' }};">
                    {{ $widget['label'] }}
                </span>
                        <!-- Icon: Top-right -->
                        <div class="absolute top-0 right-0 flex items-center">
                            @if (str_contains($widget['icon'], 'heroicon'))
                                <span class="h-5 w-5 mt-1" style="color: {{ $widget['icon_color'] ?? 'inherit' }};">
                            <x-dynamic-component :component="$widget['icon']" class="h-full w-full" />
                        </span>
                            @else
                                <img src="{{ asset($widget['icon']) }}" alt="Widget Icon" style="max-height: 20px; width: auto; color: {{ $widget['icon_color'] ?? 'inherit' }};" class="mt-1" />
                            @endif
                        </div>
                        <!-- Description: Bottom-right -->
                        <p class="absolute bottom-0 right-0 text-xs text-right break-words" style="color: {{ $widget['description_color'] ?? 'inherit' }};">
                            {{ $widget['description'] }}
                        </p>
                        <!-- Pass update endpoint and polling interval to JavaScript -->
                        @if(!empty($widget['update_endpoint']))
                            <script type="application/json" class="widget-config" data-widget-id="{{ $widget['id'] ?? '' }}">
                                {
                                    "update_endpoint": "{{ $widget['update_endpoint'] }}",
                            "polling_interval": "{{ $widget['polling_interval'] ?? '5s' }}"
                        }
                            </script>
                        @endif
                    </div>
                    @if(!empty($widget['link']))
                </a>
            @endif
        @endforeach
    </div>
</div>

<!-- JavaScript to handle polling and updates -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const widgets = document.querySelectorAll('.widget-config');
        const pollingIntervals = new Map();

        function parsePollingInterval(interval) {
            const match = interval.match(/^(\d+)(s|m|h)$/);
            if (!match) return 5000;

            const value = parseInt(match[1], 10);
            const unit = match[2];

            switch (unit) {
                case 's': return value * 1000;
                case 'm': return value * 60 * 1000;
                case 'h': return value * 60 * 60 * 1000;
                default: return 5000;
            }
        }

        async function updateWidget(widgetId, endpoint) {
            const response = await fetch(endpoint, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                const valueElement = document.querySelector(`.widget-value[data-widget-id="${widgetId}"]`);

                if (valueElement && data.value !== undefined) {
                    const oldValue = valueElement.textContent.trim();
                    valueElement.textContent = data.value;

                    if (oldValue !== data.value) {
                        valueElement.classList.remove('glow');
                        void valueElement.offsetWidth;
                        valueElement.classList.add('glow');
                    }
                }
            }
        }

        widgets.forEach(widgetConfig => {
            const widgetId = widgetConfig.getAttribute('data-widget-id');
            const config = JSON.parse(widgetConfig.textContent);
            const { update_endpoint, polling_interval } = config;

            if (update_endpoint && widgetId && polling_interval) {
                const intervalMs = parsePollingInterval(polling_interval);

                setTimeout(() => {
                    updateWidget(widgetId, update_endpoint);

                    const intervalId = setInterval(() => {
                        updateWidget(widgetId, update_endpoint);
                    }, intervalMs);

                    pollingIntervals.set(widgetId, intervalId);
                }, intervalMs);
            }
        });

        window.addEventListener('unload', () => {
            pollingIntervals.forEach(intervalId => clearInterval(intervalId));
        });
    });
</script>

<style>
    /* Glow effect animation */
    .widget-value.glow {
        animation: glow 0.5s ease-in-out;
    }

    @keyframes glow {
        0% { text-shadow: 0 0 5px rgba(255, 255, 255, 0.8); }
        50% { text-shadow: 0 0 10px rgba(255, 255, 255, 1); }
        100% { text-shadow: 0 0 5px rgba(255, 255, 255, 0.8); }
    }
</style>
