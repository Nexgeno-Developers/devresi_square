@props([
    'tone' => 'idle',
])

@php
    $tone = in_array($tone, ['ok', 'warn', 'bad', 'idle'], true) ? $tone : 'idle';
@endphp

<span {{ $attributes->merge(['class' => 'lw-pill lw-pill-'.$tone]) }}>
    {{ $slot }}
</span>
