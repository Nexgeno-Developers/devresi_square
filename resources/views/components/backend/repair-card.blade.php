@props(['repair', 'selectedRepairId' => null])

@php
    $photoId = (int) trim(explode(',', (string) ($repair->repairPhotos->first()?->photos ?? ''))[0] ?? '');
    $photoUrl = $photoId > 0 ? uploaded_asset($photoId) : null;
    $address = rs_property_title($repair->property);
    $issue = \Illuminate\Support\Str::limit($repair->description ?: 'Repair', 90);
    $category = getRepairCategoryDetails($repair->repair_category_id);
    if ($category === 'Staging General Repair') {
        $category = 'General repair';
    }
    $statusLabel = rs_repair_status_label($repair->status);
    $pill = match ($statusLabel) {
        'In Progress', 'Reviewing', 'Awaiting Parts', 'Awaiting Approval' => 'lw-pill-warn',
        'Completed', 'Closed' => 'lw-pill-ok',
        default => 'lw-pill-idle',
    };
    $priority = match (strtolower((string) $repair->priority)) {
        'urgent' => 'Urgent',
        'high' => 'High',
        'low' => 'Low',
        'normal', 'medium' => 'Normal',
        default => $repair->priority ? ucfirst((string) $repair->priority) : 'Normal',
    };
@endphp

<article
    class="lw-repair-card {{ isset($selectedRepairId) && $repair->id == $selectedRepairId ? 'is-open' : '' }}"
    data-url="{{ route('admin.property_repairs.show', $repair->id) }}"
    data-title="{{ $address }}"
    role="button"
    tabindex="0"
    onclick="loadRepairDetailByUrl(event, this)"
    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); loadRepairDetailByUrl(event, this); }"
>
    @if($photoUrl)
        <img src="{{ $photoUrl }}" alt="" class="lw-repair-thumb">
    @else
        <div class="lw-repair-thumb lw-repair-thumb-empty" aria-hidden="true"></div>
    @endif
    <div class="lw-repair-copy">
        <div class="lw-repair-title-row">
            <p class="lw-repair-title">{{ $issue }}</p>
            <span class="lw-pill {{ $pill }}">{{ $statusLabel }}</span>
        </div>
        <p class="lw-repair-address">{{ $address }}</p>
    </div>
    <div class="lw-repair-meta">
        @if($category)
            <span>{{ $category }}</span>
        @endif
        <span>{{ $priority }}</span>
        <span>{{ rs_date($repair->created_at) }}</span>
        @if($repair->reference_number)
            <span class="lw-repair-ref">{{ $repair->reference_number }}</span>
        @endif
    </div>
    <div class="lw-repair-actions" onclick="event.stopPropagation()">
        <a href="{{ route('admin.property_repairs.edit', $repair->id) }}">Edit</a>
    </div>
</article>
