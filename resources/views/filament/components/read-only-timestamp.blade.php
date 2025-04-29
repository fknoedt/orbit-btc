@php
    $state = $getState();
@endphp

@if ($state)
    <div>
        {{ $state->toFormattedDateTime() }}
    </div>
@endif
