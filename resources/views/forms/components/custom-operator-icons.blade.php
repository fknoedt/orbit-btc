@php
    $statePath = $getStatePath();
    $currentValue = $getState();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="dir-pills">
        @foreach ($operators as $operator)
            @php
                $id = $statePath . '-' . str_replace('+', 'plus', str_replace('-', 'minus', $operator->value));
                $isSelected = $currentValue === $operator->value;
                $word = match ($operator->getIcon()) {
                    'arrow-up' => 'Up',
                    'arrow-down' => 'Down',
                    'arrows-up-down' => 'Both',
                    default => '',
                };
                $tone = match ($operator->getIcon()) {
                    'arrow-up' => 'is-up',
                    'arrow-down' => 'is-down',
                    default => 'is-both',
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
                <span class="dir-pill {{ $tone }} {{ $isSelected ? 'is-selected' : '' }}">
                    @switch($operator->getIcon())
                        @case('arrow-up')
                            <x-heroicon-o-arrow-up style="width: 16px; height: 16px; stroke-width: 2; color: currentColor;" />
                            @break
                        @case('arrow-down')
                            <x-heroicon-o-arrow-down style="width: 16px; height: 16px; stroke-width: 2; color: currentColor;" />
                            @break
                        @case('arrows-up-down')
                            <x-heroicon-o-arrows-up-down style="width: 16px; height: 16px; stroke-width: 2; color: currentColor;" />
                            @break
                    @endswitch
                    {{ $word }}
                </span>
            </label>
        @endforeach
    </div>
</x-dynamic-component>
