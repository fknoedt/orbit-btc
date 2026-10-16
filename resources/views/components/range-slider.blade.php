<div class="relative" x-data="{ sliderValue: {{ $value ?? 0 }} }">
    <input
        type="range"
        wire:model.debounce.500ms="{{ $name }}"
        min="{{ $min }}"
        max="{{ $max }}"
        step="{{ $step }}"
        value="{{ $value }}"
        class="w-full h-2 rounded-lg appearance-none cursor-pointer bg-gray-200 dark:bg-gray-700 {{ isset($disabled) && $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
        style="
            accent-color: #f97316;
            background: linear-gradient(to right, #f97316 0%, #f97316 var(--range-progress), #e5e7eb var(--range-progress), #e5e7eb 100%);
        "
        x-on:input="
            if (!@js($disabled)) {
                $el.style.setProperty('--range-progress', ((sliderValue - {{ $min }}) / ({{ $max }} - {{ $min }}) * 100) + '%');
                sliderValue = $event.target.value;
            }
        "
        x-model="sliderValue"
        {{ isset($disabled) && $disabled ? 'disabled' : '' }}
    >
    <div class="mt-2">
        <div class="flex items-baseline gap-2 text-sm text-gray-700 dark:text-gray-200">
            <span>{{ $label }}</span>
            <span x-text="sliderValue" class="font-extrabold text-gray-900 dark:text-gray-100"></span>
        </div>
        @if (filled($hint ?? null))
            <p class="mt-1 text-sm text-gray-500">{{ $hint }}</p>
        @endif
    </div>
</div>
