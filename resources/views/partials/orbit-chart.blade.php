@php
    $prefix = $prefix ?? 'orbit-chart';
@endphp
<svg viewBox="0 0 1440 420" preserveAspectRatio="none" aria-hidden="true">
    <defs>
        <linearGradient id="{{ $prefix }}-line" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#f54900" stop-opacity="0"/>
            <stop offset="0.15" stop-color="#ff6a2c"/>
            <stop offset="1" stop-color="#ffc7a3"/>
        </linearGradient>
        <linearGradient id="{{ $prefix }}-fill" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#f54900" stop-opacity="0.34"/>
            <stop offset="1" stop-color="#f54900" stop-opacity="0"/>
        </linearGradient>
        <filter id="{{ $prefix }}-glow" x="-10%" y="-40%" width="120%" height="180%">
            <feGaussianBlur stdDeviation="6" result="blur"/>
            <feMerge>
                <feMergeNode in="blur"/>
                <feMergeNode in="SourceGraphic"/>
            </feMerge>
        </filter>
    </defs>
    <path d="M0 360 C160 350 220 300 320 286 C460 266 500 330 640 250 C780 170 860 210 980 150 C1100 90 1220 120 1440 36 L1440 420 L0 420 Z" fill="url(#{{ $prefix }}-fill)"/>
    <path d="M0 360 C160 350 220 300 320 286 C460 266 500 330 640 250 C780 170 860 210 980 150 C1100 90 1220 120 1440 36" fill="none" stroke="url(#{{ $prefix }}-line)" stroke-width="3.5" filter="url(#{{ $prefix }}-glow)"/>
</svg>
