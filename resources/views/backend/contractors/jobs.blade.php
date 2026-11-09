@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page">
    @include('backend.contractors._nav')

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Disputed ({{ $disputed->count() }})</div>
            @forelse($disputed as $offer)
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <span>
                            <strong>{{ $offer->repairIssue?->reference_number ?: 'Repair #'.$offer->repair_issue_id }}</strong>
                            · {{ $offer->contractor?->contractorProfile?->displayName() }} · {{ $offer->statusLabel() }}
                            <div class="small text-danger">{{ $offer->dispute_reason }} · {{ $offer->disputed_at?->diffForHumans() }}</div>
                        </span>
                        @include('backend.contractors._override', ['actions' => ['resolve_dispute' => 'Mark resolved', 'cancel_award' => 'Cancel award']])
                    </div>
                </div>
            @empty
                <p class="small text-muted mb-0">None.</p>
            @endforelse
        </div>
    </div>

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Disputed compliance checks ({{ $complianceDisputes->count() }})</div>
            @forelse($complianceDisputes as $offer)
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <span>
                            <strong>{{ $offer->booking?->type?->name ?: 'Compliance check' }}</strong>
                            · {{ $offer->contractor?->contractorProfile?->displayName() }} · {{ $offer->statusLabel() }}
                            <div class="small text-danger">{{ $offer->dispute_reason }} · {{ $offer->disputed_at?->diffForHumans() }}</div>
                        </span>
                        <form method="POST" action="{{ route('admin.registrations.contractors.jobs.compliance.resolve', $offer) }}" class="d-flex gap-1 flex-wrap align-items-center">
                            @csrf
                            <input name="note" required maxlength="1000" class="form-control form-control-sm" style="width:200px" placeholder="Note for both sides">
                            <button class="btn btn-sm btn-outline-primary" type="submit">Mark resolved</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="small text-muted mb-0">None.</p>
            @endforelse
        </div>
    </div>

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Awarded but not finished after 7 days ({{ $overdue->count() }})</div>
            @forelse($overdue as $offer)
                <div class="border rounded p-2 mb-2 d-flex justify-content-between flex-wrap gap-2">
                    <span>
                        <strong>{{ $offer->repairIssue?->reference_number ?: 'Repair #'.$offer->repair_issue_id }}</strong>
                        · {{ $offer->contractor?->contractorProfile?->displayName() }} · {{ $offer->statusLabel() }}
                        <span class="small text-muted">· visit {{ $offer->scheduled_for?->format('j M') ?: 'not booked' }}</span>
                    </span>
                    @include('backend.contractors._override', ['actions' => ['cancel_award' => 'Cancel award']])
                </div>
            @empty
                <p class="small text-muted mb-0">None.</p>
            @endforelse
        </div>
    </div>

    <div class="card lw-card">
        <div class="card-body">
            <div class="lw-section-title mt-0">Requests with no live replies ({{ $noReplies->count() }})</div>
            @forelse($noReplies as $request)
                <div class="border rounded p-2 mb-2 small">
                    <strong>{{ $request->repairIssue?->reference_number ?: 'Repair #'.$request->repair_issue_id }}</strong>
                    · {{ $request->statusLabel() }} · {{ \App\Models\RepairContractorRequest::URGENCIES[$request->urgency] ?? '' }}
                    · sent {{ $request->first_sent_at?->diffForHumans() }}
                    · {{ $request->offers->count() }} invited:
                    {{ $request->offers->map(fn ($o) => ($o->contractor?->contractorProfile?->displayName() ?: 'Contractor').' ('.$o->statusLabel().')')->implode(', ') }}
                </div>
            @empty
                <p class="small text-muted mb-0">None.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
