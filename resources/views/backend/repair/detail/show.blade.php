@php
    $detailCategory = getRepairCategoryDetails($repairIssue->repair_category_id);
    if ($detailCategory === 'Staging General Repair') {
        $detailCategory = 'General repair';
    }
    $address = $repairIssue->property
        ? trim(collect([$repairIssue->property->line_1, $repairIssue->property->city, $repairIssue->property->postcode])->filter()->implode(', '))
        : null;
    $desk = app(\App\Services\Repairs\RepairDeskService::class);
    $openDesk = $desk->openFor($repairIssue);
    $deskFinished = $desk->completedFor($repairIssue);
    $finished = $deskFinished || in_array((string) $repairIssue->status, ['Work Completed', 'Completed', 'Closed', 'Invoice Received', 'Invoice Paid', 'Cancelled', 'Rejected'], true);
    $awarded = (bool) $repairIssue->awarded_offer_id;
    $marketplaceOpen = $repairIssue->contractorRequest && in_array($repairIssue->contractorRequest->status, ['sent', 'awaiting_contractor'], true);
    $holds = ! $finished && ! $openDesk && ! $awarded;
    $detailStatus = rs_repair_status_label($repairIssue->status);
    $detailPill = match ($detailStatus) {
        'In progress' => 'lw-pill-warn',
        'Work Completed', 'Closed' => 'lw-pill-ok',
        default => 'lw-pill-idle',
    };
@endphp
<div class="lw-repair-summary">
    <div class="lw-repair-summary-row">
        <h2>{{ \Illuminate\Support\Str::limit($repairIssue->description ?: 'Repair', 140) }}</h2>
        <span class="lw-pill {{ $detailPill }}">{{ $detailStatus }}</span>
    </div>
    <p class="mb-2">{{ $address ?: 'No property linked' }}</p>
</div>

<dl class="lw-repair-dl">
    @if($detailCategory)
        <dt>Area</dt>
        <dd>{{ $detailCategory }}</dd>
    @endif
    <dt>What is wrong</dt>
    <dd>{{ $repairIssue->description ?: '—' }}</dd>
    <dt>Priority</dt>
    <dd>{{ $repairIssue->priority ? ucfirst($repairIssue->priority) : 'Medium' }}</dd>
    <dt>Tenant available</dt>
    <dd>{{ $repairIssue->tenantAvailabilityLabel() ?: 'To be arranged' }}</dd>
    @if(filled($repairIssue->access_details))
        <dt>Access</dt>
        <dd>{{ $repairIssue->access_details }}</dd>
    @endif
    <dt>Reported</dt>
    <dd>{{ rs_date($repairIssue->created_at) }}{{ $repairIssue->reportedByLandlord() ? ' · by the landlord' : ' · by the tenant' }}</dd>
</dl>

@if($repairIssue->repairPhotos->count())
    <div class="d-flex flex-wrap gap-2 my-3">
        @foreach($repairIssue->repairPhotos as $photo)
            @foreach(explode(',', (string) $photo->photos) as $photoId)
                @if(trim($photoId) !== '')
                    <img src="{{ uploaded_asset(trim($photoId)) }}" alt="Repair photo" width="96" height="96" style="object-fit:cover" class="rounded border">
                @endif
            @endforeach
        @endforeach
    </div>
@endif

@if($openDesk)
    <p class="mb-2"><strong>{{ $openDesk->statusLabel() }}</strong>@if($openDesk->assignee) · {{ $openDesk->assignee->name }}@endif</p>
    <p class="text-muted mb-0">The Resisquare team has this repair. A contractor can be invited only if they return it.</p>
@elseif($deskFinished || $finished)
    <p class="mb-0">This repair is finished.</p>
@elseif($awarded)
    <p class="mb-2">A contractor has this job.</p>
    <a class="btn lw-btn-secondary btn-sm" href="{{ route('admin.property_repairs.show', $repairIssue) }}">Open the job</a>
@elseif($holds)
    <div class="d-flex flex-wrap gap-2 mt-3">
        <a class="btn lw-btn-primary btn-sm" href="{{ route('admin.property_repairs.marketplace.find', $repairIssue) }}">Assign a contractor</a>
        @unless($marketplaceOpen)
            <form method="POST" action="{{ route('admin.property_repairs.desk.store', $repairIssue) }}">
                @csrf
                <button class="btn lw-btn-secondary btn-sm" type="submit">Send to Resisquare</button>
            </form>
        @endunless
        <form method="POST" action="{{ route('admin.property_repairs.close', $repairIssue) }}">
            @csrf
            <button class="btn lw-btn-ghost btn-sm" type="submit">Close</button>
        </form>
    </div>
    @if($marketplaceOpen)
        <p class="small text-muted mt-2 mb-0">Contractors have already been asked. Close this repair, or wait for an offer.</p>
    @endif
@endif
