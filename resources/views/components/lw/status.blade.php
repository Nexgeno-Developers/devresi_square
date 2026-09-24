@props([
    'tone' => 'idle',
])

@php
    $tone = in_array($tone, ['ok', 'warn', 'bad', 'idle'], true) ? $tone : 'idle';
@endphp

<span {{ $attributes->merge(['class' => 'lw-status lw-status-'.$tone]) }}>
    {{ $slot }}
</span>
