{{--
    Hover on a mouse. Tap to pin on a phone, without following a parent link.
    The panel registers pinnedTooltip before Alpine boots.
--}}
@props([
    'tooltip' => null,
])

@php
    // class() finishes a missing style with ";", so drop it before merging.
    if (blank($attributes->get('style'))) {
        $attributes = $attributes->except('style');
    }
@endphp

<span
    {{ $attributes->class([
        'inline-block w-max',
        'cursor-help' => filled($tooltip),
    ]) }}
    @if (filled($tooltip))
        data-pinned-tooltip
        x-data="pinnedTooltip()"
        x-tooltip="{
            content: @js($tooltip),
            theme: $store.theme,
            trigger: 'manual',
            hideOnClick: false,
            placement: placement,
            maxWidth: maxWidth,
        }"
        @mouseenter="onHoverStart()"
        @mouseleave="onHoverEnd()"
        @click="onPress($event)"
    @endif
>{{ $slot }}</span>
