@props([
    'title' => 'Nothing here yet',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'lw-empty']) }}>
    @if($icon)
        <div class="lw-empty-icon">{!! $icon !!}</div>
    @else
        <div class="lw-empty-icon" aria-hidden="true">✓</div>
    @endif
    <div class="lw-empty-title">{{ $title }}</div>
    @if(trim((string) $slot) !== '')
        <p class="mb-0">{{ $slot }}</p>
    @endif
    @isset($actions)
        <div class="mt-3">{{ $actions }}</div>
    @endisset
</div>
