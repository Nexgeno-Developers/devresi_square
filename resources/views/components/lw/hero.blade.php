@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'lw-hero d-flex align-items-center justify-content-between gap-3 flex-wrap']) }}>
    <div>
        @if($title)
            <h4>{{ $title }}</h4>
        @endif
        @if($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
    @isset($actions)
        <div class="d-flex gap-2 align-items-center flex-wrap">{{ $actions }}</div>
    @endisset
</div>
