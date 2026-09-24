@php
    $label = $repair->tenantStatusLabel();
    $step = $repair->tenantStatusStep();
    $pillClass = match ($label) {
        'Done' => 'is-active',
        'In progress' => 'is-warn',
        'Cancelled' => '',
        default => 'is-open',
    };
    $photoUploadId = (int) trim(explode(',', (string) ($repair->repairPhotos->first()?->photos ?? ''))[0] ?? '');
    $photoUrl = $photoUploadId > 0 ? uploaded_asset($photoUploadId) : null;
@endphp
<article class="tp-card tp-repair-card" data-repair-{{ $list }}="{{ $repair->id }}" data-repair-home="{{ $repair->property?->line_1 }}">
    <div class="tp-repair-card-top">
        @if($photoUrl)
            <img src="{{ $photoUrl }}" alt="" class="tp-repair-thumb">
        @else
            <div class="tp-repair-thumb tp-repair-thumb--empty" aria-hidden="true"><i class="bi bi-image"></i></div>
        @endif
        <div class="tp-repair-card-body">
            <div class="tp-repair-head">
                <p class="tp-repair-title">{{ $repair->description ? \Illuminate\Support\Str::limit($repair->description, 90) : 'Repair #'.$repair->id }}</p>
                <span class="tp-pill {{ $pillClass }}">{{ $label }}</span>
            </div>
            <p class="tp-muted mb-0">
                Reported {{ $repair->created_at?->format('d M Y') ?: '—' }}
                @if($repair->isPriorityComplaint())
                    · {{ $repair->classification_snapshot['title'] ?? 'Priority' }}
                @elseif($repair->repairCategory?->name)
                    · {{ $repair->repairCategory->name }}
                @endif
            </p>
            @if($repair->isPriorityComplaint())
                <p class="tp-muted mb-0">{{ $repair->classification_snapshot['tenant_phrase'] ?? '' }}</p>
            @endif
            @if(filled($repair->landlord_note))
                <p class="mb-0">Landlord: {{ $repair->landlord_note }}</p>
            @endif
            @if($repair->tenantAvailabilityLabel())
                <p class="tp-muted mb-0">Available {{ $repair->tenantAvailabilityLabel() }}</p>
            @endif
            @if(filled($repair->access_details))
                <p class="tp-muted mb-0">Access: {{ $repair->access_details }}</p>
            @endif
        </div>
    </div>
    <ol class="tp-status-steps" aria-label="Request progress">
        <li class="{{ $step >= 0 ? 'is-done' : '' }}">Reported</li>
        <li class="{{ $step >= 1 ? 'is-done' : '' }}">In progress</li>
        <li class="{{ $step >= 2 ? 'is-done' : '' }}">Done</li>
    </ol>
</article>
