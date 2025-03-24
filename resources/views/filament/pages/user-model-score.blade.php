<x-filament-panels::page>
    <div x-data="{ dropdownOpen: false }">
        <!-- Model selection and actions -->
        <div class="mb-4 flex justify-center items-center gap-4">
            <label for="selectedUserModelId" class="text-lg font-medium text-gray-500">Model</label>
            <select
                id="selectedUserModelId"
                wire:model.live="selectedUserModelId"
                class="block bg-gray-800 text-gray-200 border-gray-600 rounded-lg p-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
                style="width: 300px;"
            >
                @foreach ($this->userModels as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                @foreach ($this->getHeaderActions() as $action)
                    {!! $action->render() !!}
                @endforeach
            </div>
        </div>

        @if ($this->selectedUserModelId && $this->modelData)
            <div class="isolate">
                <!-- Model details -->
                <div class="!p-4 !rounded-lg" style="background-color: #161617; padding: 1rem; border-radius: 0.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem 1.5rem; margin-bottom: 20px;">
                        <div style="grid-column: span 2;">
                            <span class="block text-sm font-medium text-gray-500">Description</span>
                            <span class="text-base font-semibold !text-white">{{ $this->modelData['description'] }}</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-500">Current Score</span>
                            <span class="text-3xl font-bold !text-white">{{ $this->modelData['score'] }}</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-500">Threshold</span>
                            <span class="flex items-center gap-1 text-xl font-semibold text-orange-500 border-b-2 border-orange-500 pb-2 mb-4 threshold-value">
                                <span>🎯</span>
                                <span style="color: #F97315">{{ $this->modelData['threshold'] }}</span>
                            </span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-500">Signal</span>
                            <span class="flex items-center gap-1 {{ $this->modelData['signal'] === 'buy' ? 'text-blue-500' : 'text-yellow-700' }} font-semibold">
                                @if ($this->modelData['signal'] === 'buy')
                                    <span>📈</span>
                                @elseif ($this->modelData['signal'] === 'sell')
                                    <span>📉</span>
                                @endif
                                <span>{{ ucwords($this->modelData['signal']) }}</span>
                            </span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-500">Time Horizon</span>
                            <span class="!text-white">{{ $this->modelData['horizon'] }}</span>
                        </div>
                        <div style="grid-column: span 3;">
                            <span class="block text-sm font-medium text-gray-500">Metrics</span>
                            <div class="!text-white h-[100px] overflow-y-auto">
                                @if (!empty($this->modelData['metrics']))
                                    @foreach ($this->modelData['metrics'] as $metric)
                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; padding: 0.25rem 0;">
                                            <span class="!text-white">📐 {{ $metric['metric_name'] }}</span>
                                            <span class="!text-white">⚖️ {{ $metric['weight'] }}</span>
                                            @if ($metric['oscillation_threshold_enabled'] ?? false)
                                                <span class="!text-white">🛑 {{ $metric['operator'] }} {{ $metric['oscillation_threshold'] }}%</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <span class="!text-white">None</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- Chart -->
                    <div class="mt-4">
                        <span class="block text-sm font-medium text-gray-500 mb-1">Performance - tap daily score bars for simulated trade info</span>
                        <div style="min-width: 100%; min-height: 400px;">
                            @include('filament.components.user-model-chart', [
                                'label' => '',
                                'name' => 'user-model-score-chart',
                                'hint' => null,
                                'options' => $this->chartData['options'] ?? [],
                                'rawExtraJsOptions' => $this->chartData['extraJsOptions'] ?? [],
                            ])
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('open-chart-modal', (event) => {
            window.Livewire.dispatch('open-chart-modal', { date: event.detail.date });
        });
    </script>
</x-filament-panels::page>
