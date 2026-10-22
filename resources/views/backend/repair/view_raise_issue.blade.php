@extends('backend.layout.app')

@section('content')
@php
    $property = $repairIssue->property;
    $address = $property
        ? trim(collect([$property->line_1, $property->city, $property->postcode])->filter()->implode(', '))
        : null;
    $title = $property?->short_title ?: ($address ?: 'Repair');
    $statusTone = match (true) {
        in_array($repairIssue->status, ['Completed', 'Work Completed', 'Closed', 'Resolved', 'Invoice Paid'], true) => 'ok',
        in_array($repairIssue->status, ['Cancelled', 'Rejected'], true) => 'idle',
        in_array($repairIssue->priority, ['urgent', 'emergency', 'high'], true) => 'bad',
        default => 'warn',
    };
@endphp
<div class="container-fluid lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h4>{{ $title }}</h4>
            <p>{{ $address ?: 'Repair' }} · reported {{ $repairIssue->created_at?->format('j M Y') ?? '—' }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <x-lw.pill :tone="$statusTone">{{ $repairIssue->status }}</x-lw.pill>
            @php
                $deskGate = app(\App\Services\Repairs\RepairDeskService::class);
                $mayEdit = $repairIssue->reportedByLandlord()
                    && ! $repairIssue->awarded_offer_id
                    && ! $deskGate->openFor($repairIssue)
                    && ! $deskGate->completedFor($repairIssue)
                    && ! in_array($repairIssue->status, ['Work Completed', 'Completed', 'Closed', 'Invoice Received', 'Invoice Paid', 'Cancelled', 'Rejected'], true);
            @endphp
            @if($mayEdit)
                <a href="{{ route('admin.property_repairs.edit', $repairIssue->id) }}" class="btn lw-btn-secondary btn-sm">Edit</a>
            @endif
            <a href="{{ route('admin.property_repairs.index') }}" class="btn lw-btn-ghost btn-sm">Back</a>
        </div>
    </div>

    @include('backend.repair.partials.marketplace')

    <div class="row g-3">
    <div class="col-lg-7">
    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">What needs fixing</div>
            <p class="mb-2">{{ $repairIssue->description }}</p>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <x-lw.pill tone="idle">{{ getRepairCategoryDetails($repairIssue->repair_category_id) ?: 'Category' }}</x-lw.pill>
                <x-lw.pill tone="idle">{{ ucfirst($repairIssue->priority) }}</x-lw.pill>
            </div>
            <p class="mb-1 small text-muted">{{ getFormattedRepairNavigation($repairIssue->repair_navigation) }}</p>
            <p class="mb-0">Tenant available: {{ $repairIssue->tenantAvailabilityLabel() ?: 'To be arranged' }}</p>
            @if($repairIssue->access_details)
                <p class="mb-0 small">Access: {{ $repairIssue->access_details }}</p>
            @endif
        </div>
    </div>

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Photos</div>
            @if($repairIssue->repairPhotos->count())
                <div class="row g-2">
                    @foreach($repairIssue->repairPhotos as $photo)
                        @foreach(explode(',', $photo->photos) as $photoId)
                            @php $photoId = trim($photoId); @endphp
                            @if($photoId)
                                <div class="col-6 col-md-3">
                                    <a href="{{ uploaded_asset($photoId) }}" target="_blank" rel="noopener">
                                        <img src="{{ uploaded_asset($photoId) }}" class="img-fluid rounded" alt="Repair photo">
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    @endforeach
                </div>
            @else
                <x-lw.empty title="No photos yet">Photos help contractors see the issue.</x-lw.empty>
            @endif
        </div>
    </div>
    </div>

    <div class="col-lg-5">
    @include('backend.repair.partials.priority-sla')

    @unless(is_landlord_plan_user())
    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Property managers</div>
            @if($repairIssue->repairIssuePropertyManagers->count())
                <table class="table lw-table align-middle mb-0">
                    <thead><tr><th>#</th><th>Manager</th><th>Assigned</th></tr></thead>
                    <tbody>
                        @foreach($repairIssue->repairIssuePropertyManagers as $index => $assignment)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $assignment->propertyManager->name ?? '—' }} <span class="text-muted small">({{ $assignment->propertyManager->email ?? '—' }})</span></td>
                                <td>{{ \Carbon\Carbon::parse($assignment->assigned_at)->format('j M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="mb-0 text-muted">None assigned.</p>
            @endif
        </div>
    </div>

    @endunless

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">History</div>
            @if($repairIssue->repairHistories->count())
                <ul class="list-unstyled mb-0">
                    @foreach($repairIssue->repairHistories as $history)
                        <li class="mb-2 pb-2 border-bottom">
                            <strong>{{ $history->action }}</strong>
                            <span class="text-muted"> · {{ $history->previous_status }} → {{ $history->new_status }}</span>
                            <div class="small text-muted">{{ \Carbon\Carbon::parse($history->created_at)->format('j M Y, H:i') }}</div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mb-0 text-muted">No history yet.</p>
            @endif
        </div>
    </div>
    </div>
    </div>
</div>
@endsection
