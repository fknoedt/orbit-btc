{{--
    viewData cell keys: tooltip, text, color, bold, icon.
    Omit text to show the raw column state. An explicit null text stays blank.
    icon is a Heroicon name and replaces the text.
--}}
@php
    $cell = $cell ?? [];
    $color = $cell['color'] ?? null;
    $icon = $cell['icon'] ?? null;
    $text = array_key_exists('text', $cell) ? $cell['text'] : $getState();
    $hasColor = filled($color);
    $isIcon = filled($icon);

    $class = \Illuminate\Support\Arr::toCssClasses([
        'text-sm leading-6' => ! $isIcon,
        'font-bold' => ! $isIcon && (bool) ($cell['bold'] ?? false),
        'text-gray-950 dark:text-white' => ! $isIcon && ! $hasColor,
        'fi-color-custom text-custom-600 dark:text-custom-400' => ! $isIcon && $hasColor,
        'fi-color-'.$color => ! $isIcon && $hasColor,
    ]);

    $style = ! $isIcon && $hasColor
        ? \Filament\Support\get_color_css_variables($color, shades: [400, 600])
        : null;

    $iconClass = \Illuminate\Support\Arr::toCssClasses([
        'fi-ta-icon-item fi-ta-icon-item-size-lg h-6 w-6',
        'text-gray-400 dark:text-gray-500' => ! $hasColor,
        'fi-color-custom text-custom-500 dark:text-custom-400' => $hasColor,
        'fi-color-'.$color => $hasColor,
    ]);

    $iconStyle = $hasColor
        ? \Filament\Support\get_color_css_variables(
            $color,
            shades: [400, 500],
            alias: 'tables::columns.icon-column.item',
        )
        : null;
@endphp

<div class="px-3 py-4">
    <x-pinned-tooltip
        :tooltip="$cell['tooltip'] ?? null"
        :class="$class"
        :style="$style"
    >
        @if ($isIcon)
            <x-filament::icon :icon="$icon" :class="$iconClass" :style="$iconStyle ?: false" />
        @else
            {{ $text }}
        @endif
    </x-pinned-tooltip>
</div>
