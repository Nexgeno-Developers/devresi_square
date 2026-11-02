@extends('backend.layout.app')

@section('content')
@php
    $property = $booking->property;
    $address = trim(collect([$property?->line_1, $property?->city, $property?->postcode])->filter()->implode(', '));
    $tradeLabel = $trades[$required] ?? $required;
    $yourContractors = $candidates->where('mine', true)->values();
    $marketplaceContractors = $candidates->filter(fn ($row) => $row->marketplace && ! $row->mine)->values();
@endphp
<div class="container-fluid lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h4>Find a contractor</h4>
            <p>{{ $booking->type?->name }} · {{ $address ?: 'Property' }}</p>
        </div>
        <a href="{{ route('admin.compliance.index') }}" class="btn lw-btn-secondary">Back to compliance</a>
    </div>

    @foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $flash => $tone)
        @if(session($flash))
            <div class="alert alert-{{ $tone }}">{{ session($flash) }}</div>
        @endif
    @endforeach

    <div class="card lw-card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Trade</label>
                    <input class="form-control form-control-sm" value="{{ $tradeLabel }} (required)" disabled>
                </div>
                <div class="col-6 col-md-3">
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
                <div class="col-12 col-md-1">
                    <button class="btn btn-sm lw-btn-secondary" type="submit">Filter</button>
                </div>
            </form>
        </div>
    </div>

    @if($emptyReason)
        <div class="alert alert-light border">{{ $emptyReason }}</div>
    @endif

    <form method="POST" action="{{ route('admin.compliance.bookings.send', $booking) }}">
        @csrf
        <div class="card lw-card mb-3">
            <div class="card-body">
                <h5 class="mb-3">Your contractors</h5>
                @include('backend.compliance.bookings.candidates', ['rows' => $yourContractors])
            </div>
        </div>
        <div class="card lw-card mb-3">
            <div class="card-body">
                <h5 class="mb-3">Resisquare marketplace</h5>
                @include('backend.compliance.bookings.candidates', ['rows' => $marketplaceContractors])
            </div>
        </div>
        <div class="card lw-card mb-3">
            <div class="card-body">
                <label class="form-label" for="access_notes">Access notes</label>
                <textarea class="form-control mb-3" id="access_notes" name="access_notes" rows="2" maxlength="1000">{{ old('access_notes', $booking->access_notes) }}</textarea>
                <button class="btn lw-btn-primary" type="submit">Send to selected</button>
                <span class="small text-muted ms-2">Up to {{ $cap }} contractors. {{ $openCount }} already invited.</span>
            </div>
        </div>
    </form>

    <div class="card lw-card">
        <div class="card-body">
            <h5>Invite my own contractor</h5>
            <p class="text-muted">They join your list. You can send this check once they are approved for {{ strtolower($tradeLabel) }}.</p>
            <form method="POST" action="{{ route('admin.compliance.bookings.invite', $booking) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label" for="invite_name">Name</label>
                    <input class="form-control" id="invite_name" name="name" value="{{ old('name') }}" maxlength="120">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="invite_email">Email</label>
                    <input class="form-control" id="invite_email" type="email" name="email" value="{{ old('email') }}" required maxlength="190">
                </div>
                <div class="col-md-4">
                    <button class="btn lw-btn-secondary" type="submit">Send invite</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
