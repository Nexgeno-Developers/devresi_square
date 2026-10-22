@extends('backend.layout.app')

@php
    $repair = $desk->repairIssue;
    $property = $repair?->property;
    $tenant = $repair?->tenant_id ? \App\Models\User::query()->find($repair->tenant_id) : null;
    $title = $property?->short_title ?: ($repair?->reference_number ?: 'Repair');
    $where = $property?->full_address ?: trim(collect([$property?->line_1, $property?->city, $property?->postcode])->filter()->implode(', '));
    $events = \Illuminate\Support\Facades\Schema::hasTable('repair_desk_events') ? $desk->events : collect();
@endphp

@section('content')
<div class="container-fluid lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <p class="mb-1"><a href="{{ route('backend.saas.repair_desk.index') }}">Repair desk</a></p>
            <h4 class="mb-1">{{ $title }}</h4>
            <p class="mb-0">{{ $where ?: 'No address on the repair' }}</p>
        </div>
        <div class="text-end">
            <div class="fw-semibold">{{ $desk->statusLabel() }}</div>
            <div class="small text-muted">{{ $desk->assignee?->name ?: 'Not taken yet' }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card lw-card mb-3">
                <div class="card-body">
                    <div class="lw-section-title mb-2">The repair</div>
                    <p class="mb-2">{{ $repair?->description ?: 'No description.' }}</p>
                    <dl class="row small mb-0">
                        <dt class="col-sm-4">Account</dt>
                        <dd class="col-sm-8">{{ $desk->account?->account_name ?: ('Account '.$desk->account_id) }}</dd>
                        <dt class="col-sm-4">Reference</dt>
                        <dd class="col-sm-8">{{ $repair?->reference_number ?: '—' }}</dd>
                        <dt class="col-sm-4">Category</dt>
                        <dd class="col-sm-8">{{ $repair?->repairCategory?->name ?: '—' }}</dd>
                        <dt class="col-sm-4">Priority</dt>
                        <dd class="col-sm-8">{{ $repair?->priority ? ucfirst($repair->priority) : '—' }}</dd>
                        <dt class="col-sm-4">Repair status</dt>
                        <dd class="col-sm-8">{{ $repair?->status ?: '—' }}</dd>
                        <dt class="col-sm-4">When someone can be in</dt>
                        <dd class="col-sm-8">{{ $repair?->tenantAvailabilityLabel() ?: 'Not given' }}</dd>
                        <dt class="col-sm-4">Access</dt>
                        <dd class="col-sm-8">{{ $repair?->access_details ?: 'Not given' }}</dd>
                        <dt class="col-sm-4">Tenant</dt>
                        <dd class="col-sm-8">
                            {{ $tenant?->name ?: 'No tenant on this repair' }}
                            @if($tenant?->phone)<div>{{ $tenant->phone }}</div>@endif
                            @if($tenant?->email)<div>{{ $tenant->email }}</div>@endif
                        </dd>
                        <dt class="col-sm-4">Sent by</dt>
                        <dd class="col-sm-8">{{ $desk->requester?->name ?: 'Landlord' }} · {{ rs_date($desk->created_at) }}</dd>
                    </dl>
                    @if($desk->landlord_note)
                        <div class="alert alert-light border mt-3 mb-0">
                            <div class="fw-semibold">Note from the landlord</div>
                            {{ $desk->landlord_note }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="card lw-card">
                <div class="card-body">
                    <div class="lw-section-title mb-2">What has happened</div>
                    @include('backend.repair.partials.desk-story', [
                        'events' => $events,
                        'fallback' => $events->isEmpty() && filled($desk->team_note) ? $desk->team_note : null,
                    ])
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card lw-card">
                <div class="card-body">
                    <div class="lw-section-title mb-2">Next step</div>
                    @if($desk->status === \App\Models\RepairDeskRequest::REQUESTED)
                        <p class="small">Taking it tells the landlord and the tenant that Resisquare is handling the repair.</p>
                        <form method="POST" action="{{ route('backend.saas.repair_desk.take', $desk) }}" class="d-grid gap-2">
                            @csrf
                            <textarea name="note" class="form-control" rows="3" maxlength="2000" placeholder="Optional note, for example a visit time"></textarea>
                            <button class="btn btn-primary" type="submit">Take this repair</button>
                        </form>
                    @elseif($desk->status === \App\Models\RepairDeskRequest::IN_PROGRESS)
                        <form method="POST" action="{{ route('backend.saas.repair_desk.progress', $desk) }}" class="d-grid gap-2 mb-3">
                            @csrf
                            <label class="small fw-semibold" for="desk-update">Update for the landlord and tenant</label>
                            <textarea id="desk-update" name="note" class="form-control" rows="3" maxlength="2000" required placeholder="What has happened"></textarea>
                            <button class="btn btn-outline-primary" type="submit">Send update</button>
                        </form>
                        <form method="POST" action="{{ route('backend.saas.repair_desk.complete', $desk) }}" class="d-grid gap-2 mb-3">
                            @csrf
                            <label class="small fw-semibold" for="desk-done">What was done</label>
                            <textarea id="desk-done" name="note" class="form-control" rows="3" maxlength="2000" required placeholder="Boiler replaced and tested"></textarea>
                            <button class="btn btn-primary" type="submit">Mark complete</button>
                        </form>
                        <form method="POST" action="{{ route('backend.saas.repair_desk.return', $desk) }}" class="d-grid gap-2">
                            @csrf
                            <label class="small fw-semibold" for="desk-back">Hand it back</label>
                            <textarea id="desk-back" name="note" class="form-control" rows="2" maxlength="2000" required placeholder="Why the landlord should take it from here"></textarea>
                            <button class="btn btn-outline-secondary" type="submit">Return to the landlord</button>
                        </form>
                    @elseif($desk->status === \App\Models\RepairDeskRequest::COMPLETED)
                        <p class="mb-0">This repair is complete. The landlord and tenant have the outcome on their repair.</p>
                    @else
                        <p class="mb-0">This repair is back with the landlord. They can send it to a contractor.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
