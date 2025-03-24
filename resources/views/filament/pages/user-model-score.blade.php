<x-filament-panels::page>
    <div x-data="{ dropdownOpen: false }">
        <div class="mb-4">
            <label for="selectedUserModelId" class="block text-sm font-medium text-gray-400 mb-1">Model</label>
            <select
                id="selectedUserModelId"
                wire:model.live="selectedUserModelId"
                class="block mx-auto bg-gray-800 text-gray-200 border-gray-600 rounded-lg p-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
                style="width: 300px;"
            >
                @foreach ($this->userModels as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        @if ($this->selectedUserModelId && $this->modelData)
            <div class="isolate">
                <div class="!p-4 !rounded-lg" style="background-color: #161617; padding: 1rem; border-radius: 0.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.5rem 1.5rem;">
                        <div>
                            <span class="block text-sm font-medium text-gray-400">Model Name</span>
                            <span class="text-lg font-semibold !text-white">{{ $this->modelData['name'] }}</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-400">Score</span>
                            <span class="text-lg font-semibold !text-white">{{ $this->modelData['score'] }}</span>
                        </div>
                        <div style="grid-column: span 2;">
                            <span class="block text-sm font-medium text-gray-400">Metrics</span>
                            <div class="!text-white h-[100px] overflow-y-auto">
                                @if (!empty($this->modelData['metrics']))
                                    @foreach ($this->modelData['metrics'] as $metric)
                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; padding: 0.25rem 0;">
                                            <span class="!text-white">ID: {{ $metric['id'] }}</span>
                                            <span class="!text-white">Metric ID: {{ $metric['metric_id'] }}</span>
                                            <span class="!text-white">Weight: {{ $metric['weight'] }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="!text-white">None</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-400">Threshold</span>
                            <span class="!text-white">{{ $this->modelData['threshold'] }}</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-400">Signal</span>
                            <span class="!text-white">{{ $this->modelData['signal'] }}</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-400">Horizon</span>
                            <span class="!text-white">{{ $this->modelData['horizon'] }}</span>
                        </div>
                        <div style="grid-column: span 2;">
                            <span class="block text-sm font-medium text-gray-400">Info</span>
                            <span class="!text-white">{{ $this->modelData['info'] }}</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <span class="block text-sm font-medium text-gray-400 mb-1">Chart</span>
                        <div style="min-width: 100%; min-height: 400px;">
                            @include('filament.components.user-model-chart', [
                                'label' => 'Chart',
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
