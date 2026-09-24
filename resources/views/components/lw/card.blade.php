@props([
    'body' => true,
])

<div {{ $attributes->merge(['class' => 'card lw-card']) }}>
    @if($body)
        <div class="card-body">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
