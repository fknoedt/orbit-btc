<div class="pt-6" x-data="{ open: false }">
    <div class="pt-6">
        <div class="mb-4 flex items-center justify-between gap-4">
            <!-- Centered content -->
            <div class="flex justify-center items-center gap-4 w-full">
                @if (!empty($this->userSignals))
                    <label for="selectedUserSignalId" class="text-lg font-medium text-gray-500">Signal</label>
                    <select
                        id="selectedUserSignalId"
                        wire:model.live="selectedUserSignalId"
                        class="block bg-gray-800 text-gray-200 border-gray-600 rounded-lg p-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
                        style="width: 300px;"
                    >
                        @foreach ($this->userSignals as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        @foreach ($this->getHeaderActions() as $action)
                            {!! $action->render() !!}
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Help Icon and Modal (only show if there are User Signals) -->
            @if (!empty($this->userSignals))
                <div x-data="{ open: false }">
                    <!-- Help Button -->
                    <button
                        @click="open = true"
                        class="p-2 rounded-md hover:bg-gray-600 focus:outline-none"
                        style="background-color: #008FFB; height: 40px; width: 40px;"
                        title="Help"
                    >
                        <x-heroicon-o-lifebuoy class="w-6 h-6 text-white"/>
                    </button>

                    <!-- Modal -->
                    <div x-show="open"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                         style="background-color: rgba(0, 0, 0, 0.5);" @click="open = false">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-4xl p-6 relative"
                             style="max-height: 70vh; overflow-y: auto;" @click.stop="">
                            <!-- Close Button -->
                            <button
                                @click="open = false"
                                class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white text-2xl"
                            >
                                ×
                            </button>
                            <!-- Content -->
                            <div style="overflow-y: scroll">
                                @include('help.user-signal')
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if (empty($this->userSignals))
            <div class="flex items-center justify-center h-64">
                <p class="text-lg font-medium text-gray-500 dark:text-gray-400">
                    You need to create a Model <a href="/app/user-signals" class="text-primary-500 hover:underline">here</a>
                    in order to see its performance
                </p>
            </div>
        @elseif ($this->selectedUserSignalId && $this->signalData)
            <div class="isolate">
                <!-- Model details -->
                <div class="!p-4 !rounded-lg" style="background-color: #161617; padding: 1rem; border-radius: 0.5rem;">
                    @if ($this->signalData['paused'])
                        <div class="text-center text-gray-400 text-lg py-8">
                            Model Paused. Edit to unpause.
                        </div>
                    @else
                        <div
                            style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem 1.5rem; margin-bottom: 20px;">
                            <div style="grid-column: span 2;">
                                <span class="block text-sm font-medium text-gray-500">Description</span>
                                <span
                                    class="text-base font-semibold !text-white">{{ $this->signalData['description'] }}</span>
                            </div>
                            <div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-500">Total Score</span>
                                    <span class="text-3xl font-bold"
                                          style="color: {{ $this->signalData['total_score'] >= 0 ? '#22c55e' : '#ef4444' }}">{{ $this->signalData['total_score'] }}</span>
                                </div>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Daily Threshold</span>
                                <span
                                    class="flex items-center gap-1 text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4 threshold-value">
                                    <span>🎯</span>
                                    <span style="color: #F97315">{{ $this->signalData['threshold'] }}</span>
                                </span>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Signal</span>
                                <span
                                    class="flex items-center gap-1 {{ $this->signalData['signal'] === 'buy' ? 'text-blue-500' : 'text-yellow-700' }} font-semibold">
                                    @if ($this->signalData['signal'] === 'buy')
                                        <span>📈</span>
                                    @elseif ($this->signalData['signal'] === 'sell')
                                        <span>📉</span>
                                    @endif
                                    <span>{{ ucwords($this->signalData['signal']) }}</span>
                                </span>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Time Horizon</span>
                                <span class="!text-white">{{ $this->signalData['horizon'] }}</span>
                            </div>
                            <div>
                                <span
                                    class="block text-sm font-medium text-gray-500"># Simulated Trades (Threshold Hit)</span>
                                <span
                                    class="flex items-center gap-1 text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4 threshold-value">
                                    <span>{{ $this->signalData['total_simulated_trades'] }} (${{ $this->signalData['total_stake'] }})</span>
                                </span>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Last Trade Signal</span>
                                <span class="flex items-center gap-1 font-semibold"
                                      style="color: {{ $this->signalData['last_score'] > 0 ? '#22c55e' : ($this->signalData['last_score'] == 0 ? 'inherit' : '#ef4444') }}">
                                    {{ $this->signalData['last_score'] }}
                                    @if ($this->signalData['last_date_calculated'])
                                        <span class="font-light"
                                              style="color: white">in {{ \Carbon\Carbon::parse($this->signalData['last_date_calculated'])->format('M d Y') }}</span>
                                    @endif
                                </span>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Start of Time Series</span>
                                <span class="flex items-center gap-1 font-semibold text-white">
                                    @if ($this->signalData['first_date_calculated'])
                                        {{ \Carbon\Carbon::parse($this->signalData['first_date_calculated'])->format('M d Y') }}
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                            <div style="grid-column: span 3;">
                                <span class="block text-sm font-medium text-gray-500">Metrics</span>
                                <div class="!text-white h-[100px] overflow-y-auto">
                                    @if (!empty($this->signalData['metrics']))
                                        @foreach ($this->signalData['metrics'] as $metric)
                                            <div
                                                style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; padding: 0.25rem 0;">
                                                <span class="!text-white">💡 {{ $metric['metric_name'] }} ({{ $metric['frequency'] }})</span>
                                                <span class="!text-white">{{ $metric['operator']->value == '+' ? '↑ up variations only' : ($metric['operator'] === '-' ? '↓ down variations only' : '↑↓ up or down variations') }}</span>
                                                <span class="!text-white">⚖️ x{{ $metric['weight'] }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="!text-white">None</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!-- Chart -->
                        <div class="mt-6">
                            <span class="block text-sm font-medium text-gray-500 mb-1">Performance - tap daily score bars for simulated trade info</span>
                            <div style="min-width: 100%; min-height: 400px;">
                                @include('filament.components.user-signal-chart', [
                                    'label' => '',
                                    'name' => 'daily-score',
                                    'hint' => null,
                                    'options' => $this->chartData['options'] ?? [],
                                    'rawExtraJsOptions' => $this->chartData['extraJsOptions'] ?? [],
                                ])
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Chart Detail Modal -->
        @if ($showChartModal)
            <div class="fixed inset-0 z-50 bg-gray-950/50 dark:bg-gray-950/75" wire:click="closeChartModal"></div>
            <div class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Signal</h2>
                        <button wire:click="closeChartModal"
                                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">×
                        </button>
                    </div>
                    <div>
                        {!! $chartDetailModal !!}
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button wire:click="closeChartModal"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg dark:bg-gray-700 dark:text-gray-200">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@livewireScripts

<script>
    document.addEventListener('open-chart-modal', (event) => {
        window.Livewire.dispatch('open-chart-modal', {date: event.detail.date});
    });

    // Listen for the update-url event to change the browser URL
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('update-url', (event) => {
            const newId = event[0].id;
            const newUrl = `/app/user-signal-score/${newId}`;
            window.history.pushState({}, document.title, newUrl);
        });
    });
</script>
