@props([
    'class' => '',
    'propertyName' => '',
    'bed' => '',
    'bath' => '',
    'floor' => '',
    'living' => '',
    'type' => '',
    'available' => '',
    'price' => '',
    'lettingPrice' => '',
    'cardStyle' => '',
    'propertyId' => '',
    'brochureUrl' => '',
    'importantNote' => '',
    'status' => '',
    'actions' => [],
])

@php
    $brochureUrl = $brochureUrl ?: ($propertyId ? route('admin.properties.brochure', $propertyId) : '');
    $statusClass = match(strtolower((string) $status)) {
        'for sale', 'available' => 'bg-success',
        'on hold', 'under offer', 'let agreed' => 'bg-warning text-dark',
        'sold', 'sold stc', 'sold by other', 'exchanged', 'let by other' => 'bg-danger',
        'not available' => 'bg-secondary',
        default => 'bg-light text-dark border',
    };
@endphp

<div class="property-card {{ $class }}" data-property-id="{{ $propertyId }}" data-important-note="{{ e($importantNote) }}">
    <div class="property-card-body">
        <div class="property-card-header">
            <div class="property-name">{{ $propertyName ?: 'Property address not available' }}</div>
            @if($status)
                <span class="badge {{ $statusClass }}">{{ $status }}</span>
            @endif
        </div>

        @if($bed || $bath || $floor || $living)
            <div class="property-stats">
                @if($bed)
                    <span class="stat-item"><i class="bi bi-door-closed"></i> {{ $bed }} Bed</span>
                @endif
                @if($bath)
                    <span class="stat-item"><i class="bi bi-droplet"></i> {{ $bath }} Bath</span>
                @endif
                @if($floor)
                    <span class="stat-item"><i class="bi bi-layers"></i> {{ $floor }} Floor</span>
                @endif
                @if($living)
                    <span class="stat-item"><i class="bi bi-house"></i> {{ $living }} Living</span>
                @endif
            </div>
        @endif

        <div class="property-meta">
            @if($type)
                <span class="meta-item"><strong>Type:</strong> {{ $type }}</span>
            @endif
            @if($available)
                <span class="meta-item"><strong>Available:</strong> {{ $available }}</span>
            @endif
            @if($price)
                <span class="meta-item"><strong>Price:</strong> £{{ number_format($price, 2) }}</span>
            @endif
            @if($lettingPrice)
                <span class="meta-item"><strong>Letting:</strong> £{{ number_format($lettingPrice, 2) }}/mo</span>
            @endif
        </div>

        <div class="property-actions">
            @if($brochureUrl)
                <a href="{{ $brochureUrl }}" class="btn btn-sm btn-outline-primary" onclick="event.preventDefault(); event.stopPropagation(); window.open(this.href, '_blank');">
                    <i class="bi bi-file-earmark-pdf"></i>
                </a>
            @endif
            @if($actions && is_iterable($actions) && count($actions) > 0)
                @foreach($actions as $action)
                    <a href="{{ $action['url'] ?? '#' }}"
                       class="btn btn-sm {{ $action['class'] ?? 'btn-outline-secondary' }}"
                       @if(!empty($action['onclick'])) onclick="{{ $action['onclick'] }}" @endif
                       @if(!empty($action['target'])) target="{{ $action['target'] }}" @endif
                       @if(empty($action['onclick']) && empty($action['target'])) onclick="event.stopPropagation();" @endif>
                        @if(!empty($action['icon'])) <i class="{{ $action['icon'] }}"></i> @endif
                    </a>
                @endforeach
            @endif
        </div>
    </div>
</div>
