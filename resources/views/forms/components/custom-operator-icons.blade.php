@php
    $statePath = $getStatePath();
    $currentValue = $getState();
@endphp

<div class="space-y-2">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Up, Down or Both *
    </label>
    <div class="flex" style="gap: 8px;">
        @foreach ($operators as $operator)
            @php
                $id = $statePath . '-' . str_replace('+', 'plus', str_replace('-', 'minus', $operator->value));
                $isSelected = $currentValue === $operator->value;
                // Define tooltip based on the icon
                $tooltip = match ($operator->getIcon()) {
                    'arrow-up' => 'Metric value moved up',
                    'arrow-down' => 'Metric value moved down',
                    'arrows-up-down' => 'Metric value moved either way',
                    default => '',
                };
                // Define selected color based on the icon
                $selectedColor = match ($operator->getIcon()) {
                    'arrow-up' => '#34D399', // Light green
                    'arrow-down' => '#F87171', // Light red
                    'arrows-up-down' => '#2563eb', // Blue
                    'default' => '#2563eb',
                };
            @endphp
            <label for="{{ $id }}" class="flex items-center cursor-pointer">
                <input
                    type="radio"
                    name="{{ $statePath }}"
                    id="{{ $id }}"
                    value="{{ $operator->value }}"
                    wire:model.live="{{ $statePath }}"
                    style="display: none;"
                    {{ $isSelected ? 'checked' : '' }}
                >
                <span
                    title="{{ $tooltip }}"
                    style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 9999px; background-color: {{ $isSelected ? '#dbeafe' : 'transparent' }}; transition: all 0.2s;"
                >
                    <span class="inline-block">
                        @switch($operator->getIcon())
                            @case('arrow-up')
                                <x-heroicon-o-arrow-up style="width: 20px; height: 20px; stroke-width: 2; color: {{ $isSelected ? $selectedColor : $operator->getColor() }};" />
                                @break
                            @case('arrow-down')
                                <x-heroicon-o-arrow-down style="width: 20px; height: 20px; stroke-width: 2; color: {{ $isSelected ? $selectedColor : $operator->getColor() }};" />
                                @break
                            @case('arrows-up-down')
                                <x-heroicon-o-arrows-up-down style="width: 20px; height: 20px; stroke-width: 2; color: {{ $isSelected ? $selectedColor : $operator->getColor() }};" />
                                @break
                        @endswitch
                    </span>
                </span>
            </label>
        @endforeach
    </div>

    <!-- Add validation error feedback -->
    @error($statePath)
    <span class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</span>
    @enderror
</div>
