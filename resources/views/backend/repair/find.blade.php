@extends('backend.layout.app')

@section('content')
@php
    $property = $repairIssue->property;
    $address = $property?->short_title
        ? trim($property->short_title.($property->short_address ? ' · '.$property->short_address : ''))
        : trim(collect([$property?->line_1, $property?->city, $property?->postcode])->filter()->implode(', '));
    $trades = \App\Support\ContractorCompliance::trades();
    $guidance = config('contractor_marketplace.emergency_guidance');
    $remaining = max(0, $cap - $openCount);
@endphp
<div class="container-fluid lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h4>Find a contractor</h4>
            <p>{{ $address ?: 'Repair' }} · {{ \Illuminate\Support\Str::limit($repairIssue->description, 80) }}</p>
        </div>
        <a href="{{ route('admin.property_repairs.show', $repairIssue->id) }}" class="btn lw-btn-ghost btn-sm">Back to repair</a>
    </div>

    @foreach(['success', 'error'] as $flash)
        @if(session($flash))
            <div class="alert alert-{{ $flash === 'success' ? 'success' : 'danger' }}">{{ session($flash) }}</div>
        @endif
    @endforeach

    @if($urgency === 'emergency' || $emergency)
        <div class="alert alert-danger">
            <strong>Is anyone in danger?</strong>
            <div>{{ $guidance[$emergency ?: 'gas'] ?? $guidance['gas'] }}</div>
            @if($emergency && $emergency !== 'gas')
                <div class="small mt-1">{{ $guidance['gas'] }}</div>
            @endif
        </div>
    @endif

    <div class="card lw-card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="urgency" value="{{ $urgency }}">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Trade</label>
                    @if($required)
                        <input class="form-control form-control-sm" value="{{ $trades[$required] ?? $required }} (required)" disabled>
                    @else
                        <select name="trade" class="form-select form-select-sm">
                            <option value="" @selected(($filters['trade'] ?? '') === '')>All trades</option>
                            @foreach($trades as $key => $label)
                                <option value="{{ $key }}" @selected(($filters['trade'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Distance</label>
                    <select name="distance" class="form-select form-select-sm">
                        <option value="">Any distance</option>
                        @foreach([5, 10, 15, 25] as $miles)
                            <option value="{{ $miles }}" @selected(($filters['distance'] ?? '') == $miles)>Within {{ $miles }} miles</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Show</label>
                    <select name="only" class="form-select form-select-sm">
                        <option value="">Everyone</option>
                        <option value="favourite" @selected(($filters['only'] ?? '') === 'favourite')>Favourites</option>
                        <option value="worked" @selected(($filters['only'] ?? '') === 'worked')>Worked with me before</option>
                        <option value="mine" @selected(($filters['only'] ?? '') === 'mine')>My contractors</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 small">
                    <label class="d-block"><input type="checkbox" name="badges[]" value="gas_safe" @checked(in_array('gas_safe', (array) ($filters['badges'] ?? []), true))> Gas Safe</label>
                    <label class="d-block"><input type="checkbox" name="badges[]" value="dbs" @checked(in_array('dbs', (array) ($filters['badges'] ?? []), true))> DBS checked</label>
                </div>
                @if(! $required && $trade)
                    <div class="col-12 small text-muted">This repair looks like {{ strtolower($trades[$trade] ?? $trade) }}. Anyone outside that is marked below and cannot be sent this job.</div>
                @endif
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button class="btn lw-btn-secondary btn-sm" type="submit">Filter</button>
                    @if(array_filter($filters))
                        <a class="btn lw-btn-ghost btn-sm" href="{{ route('admin.property_repairs.marketplace.find', $repairIssue) }}">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($candidates->isEmpty())
        <div class="card lw-card mb-3">
            <div class="card-body">
                <x-lw.empty title="No contractors found">{{ $emptyReason }}@if($gap) We have told our team so we can recruit in your area.@endif</x-lw.empty>
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('admin.property_repairs.job_offers.store', $repairIssue) }}" id="send-form">
            @csrf
            <div class="card lw-card mb-3">
                <div class="card-body">
                    <div class="lw-section-title mt-0">How urgent?</div>
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        @foreach(\App\Models\RepairContractorRequest::URGENCIES as $key => $label)
                            <label class="btn btn-sm {{ $urgency === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <input type="radio" class="d-none" name="urgency" value="{{ $key }}" data-hours="{{ $inviteHours[$key] }}" @checked(old('urgency', $urgency) === $key)>
                                {{ $label }} <span class="small opacity-75">· reply within {{ $inviteHours[$key] }}h</span>
                            </label>
                        @endforeach
                    </div>
                    @if(! $repairIssue->tenant_availability)
                        <p class="small text-muted mb-0">The tenant has not given a time. Contractors will propose one and the tenant confirms it.</p>
                    @else
                        <p class="small text-muted mb-0">Tenant is available {{ $repairIssue->tenantAvailabilityLabel() }}.</p>
                    @endif
                </div>
            </div>

            <div class="card lw-card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="lw-section-title m-0">{{ $ready }} can take this · {{ $candidates->count() }} listed</div>
                        <span class="small text-muted" id="pick-count" data-cap="{{ $remaining }}">Pick up to {{ $remaining }}</span>
                    </div>
                    @if($ready === 0 && $emptyReason)
                        <p class="small text-muted">{{ $emptyReason }}</p>
                    @endif
                    @if($openCount)
                        <p class="small text-muted">{{ $openCount }} already invited. You can add more.</p>
                    @endif
                    <div class="list-group mb-2">
                        @foreach($candidates as $row)
                            <div class="list-group-item d-flex gap-3 align-items-start">
                                @if($row->can_take)
                                    <input type="checkbox" class="form-check-input mt-1 pick" name="contractor_ids[]" value="{{ $row->user_id }}" @checked(in_array($row->user_id, (array) old('contractor_ids', [])))>
                                @endif
                                <span class="flex-grow-1">
                                    <strong>{{ $row->name }}</strong>
                                    <x-lw.pill :tone="$row->tone">{{ $row->scope }}</x-lw.pill>
                                    @if($row->mine)<x-lw.pill tone="ok">My contractor</x-lw.pill>@endif
                                    @if($row->worked)<x-lw.pill tone="idle">Worked with you</x-lw.pill>@endif
                                    <span class="d-block small text-muted">
                                        {{ $row->miles !== null ? $row->miles.' miles away' : 'Distance unknown' }}
                                        @foreach($row->badges as $badge) · {{ $badge }} @endforeach
                                        @if(($row->rating ?? null) !== null) · {{ $row->rating }}★ ({{ $row->ratings }}) @endif
                                        @if(! $row->can_take && $row->reason) · {{ $row->reason }} @endif
                                    </span>
                                </span>
                                <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none" title="{{ $row->favourite ? 'Remove favourite' : 'Add favourite' }}"
                                    formaction="{{ route('admin.property_repairs.marketplace.favourite', $row->user_id) }}" formnovalidate>{{ $row->favourite ? '★' : '☆' }}</button>
                            </div>
                        @endforeach
                    </div>
                    <label class="small d-none" id="send-all-wrap">
                        <input type="checkbox" name="send_all" value="1" id="send-all"> Send to all {{ $ready }} who can take this (emergency)
                    </label>
                </div>
            </div>

            <div class="card lw-card mb-3">
                <div class="card-body">
                    <details @if($errors->hasAny(['respond_by', 'budget_ceiling', 'access_notes'])) open @endif>
                        <summary class="lw-section-title m-0" style="cursor:pointer">More options</summary>
                        <div class="row g-2 mt-2">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Respond by</label>
                                <input type="datetime-local" name="respond_by" id="respond-by" class="form-control form-control-sm" value="{{ old('respond_by') }}">
                                @error('respond_by')<div class="small text-danger">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Budget ceiling (£)</label>
                                <input type="number" step="1" min="1" name="budget_ceiling" class="form-control form-control-sm" value="{{ old('budget_ceiling', $contractorRequest?->budget_ceiling) }}">
                                <label class="small"><input type="checkbox" name="show_budget" value="1" @checked(old('show_budget', $contractorRequest?->show_budget))> Show it to contractors</label>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Access notes (shared after you accept)</label>
                                <textarea name="access_notes" rows="2" class="form-control form-control-sm">{{ old('access_notes', $contractorRequest?->access_notes ?? $repairIssue->access_details) }}</textarea>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <p class="small text-muted">Before you accept, contractors see the description, photos, postcode district ({{ \App\Support\ContractorCompliance::postcodeDistrict($property?->postcode) ?: '—' }}), urgency and tenant availability. Never the full address or tenant contact.</p>
            @if($ready > 0)
                <button class="btn btn-primary mb-4" type="submit">Send to selected</button>
            @endif
        </form>
    @endif

    <div class="card lw-card mb-4">
        <div class="card-body">
            <details @if($candidates->isEmpty() || $errors->has('email')) open @endif>
                <summary class="lw-section-title m-0" style="cursor:pointer">Invite my own contractor</summary>
                <form method="POST" action="{{ route('admin.property_repairs.marketplace.invite', $repairIssue) }}" class="row g-2 mt-2">
                    @csrf
                    <div class="col-md-5"><input type="email" name="email" required class="form-control form-control-sm" placeholder="Their email" value="{{ old('email') }}"></div>
                    <div class="col-md-4"><input type="text" name="name" class="form-control form-control-sm" placeholder="Name (optional)" value="{{ old('name') }}"></div>
                    <div class="col-md-3"><button class="btn lw-btn-secondary btn-sm w-100" type="submit">Send invite</button></div>
                    @error('email')<div class="col-12 small text-danger">{{ $message }}</div>@enderror
                    <div class="col-12 small text-muted">They sign up free and go through the same checks. Once approved they appear here tagged “My contractor”.</div>
                </form>
            </details>
        </div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('send-form');
    if (!form) return;
    const counter = document.getElementById('pick-count');
    const cap = parseInt(counter.dataset.cap, 10);
    const picks = Array.from(form.querySelectorAll('.pick'));
    const sendAllWrap = document.getElementById('send-all-wrap');
    const respondBy = document.getElementById('respond-by');
    const pad = n => String(n).padStart(2, '0');
    const local = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    const urgency = () => form.querySelector('input[name="urgency"]:checked');

    function refresh() {
        const emergency = urgency() && urgency().value === 'emergency';
        const chosen = picks.filter(p => p.checked).length;
        picks.forEach(p => { p.disabled = !emergency && !p.checked && chosen >= cap; });
        counter.textContent = emergency ? chosen + ' selected' : chosen + ' of ' + cap + ' selected';
        sendAllWrap.classList.toggle('d-none', !emergency);
        form.querySelectorAll('input[name="urgency"]').forEach(r => {
            r.parentElement.classList.toggle('btn-primary', r.checked);
            r.parentElement.classList.toggle('btn-outline-secondary', !r.checked);
        });
    }
    form.querySelectorAll('input[name="urgency"]').forEach(r => r.addEventListener('change', () => {
        if (!respondBy.dataset.touched) {
            respondBy.value = local(new Date(Date.now() + parseInt(r.dataset.hours, 10) * 3600000));
        }
        refresh();
    }));
    respondBy.addEventListener('input', () => { respondBy.dataset.touched = '1'; });
    if (!respondBy.value && urgency()) {
        respondBy.value = local(new Date(Date.now() + parseInt(urgency().dataset.hours, 10) * 3600000));
    }
    picks.forEach(p => p.addEventListener('change', refresh));
    refresh();
})();
</script>
@endsection
