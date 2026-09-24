@props([
    'variant' => 'primary',
    'href' => null,
    'size' => null,
    'type' => 'button',
])

@php
    $variant = in_array($variant, ['primary', 'secondary', 'danger', 'ghost'], true) ? $variant : 'primary';
    $classes = trim('lw-btn lw-btn-'.$variant.($size === 'sm' ? ' lw-btn-sm' : '').' '.$attributes->get('class'));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
