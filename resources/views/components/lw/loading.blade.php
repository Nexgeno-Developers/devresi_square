@props([
    'label' => 'Loading…',
])

<div {{ $attributes->merge(['class' => 'lw-loading', 'role' => 'status', 'aria-live' => 'polite']) }}>
    <span class="lw-loading-spinner" aria-hidden="true"></span>
    <span class="lw-loading-label">{{ $label }}</span>
</div>
