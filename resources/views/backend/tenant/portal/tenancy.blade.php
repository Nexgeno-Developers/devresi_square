@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">My tenancy</p>
            <h1>Your lease</h1>
            <p>Occupancy details for properties linked to your account. Staff-only tools are not shown.</p>
        </div>
    </div>

    <div class="tp-stack">
        @forelse($tenancies as $tenancy)
            @php
                $property = $tenancy->property;
                $address = $property?->full_address ?: ($property?->prop_name ?: 'Property unavailable');
            @endphp
            <div class="tp-card">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h2 class="mb-1">{{ $address }}</h2>
                        @if($property?->deleted_at)
                            <p class="tp-muted mb-0">This property record is archived. Your tenancy is still listed here.</p>
                        @endif
                    </div>
                    <span class="tp-pill {{ $tenancy->status === 'Active' ? 'is-active' : '' }}">{{ $tenancy->status }}</span>
                </div>
                <dl class="tp-dl">
                    <dt>Move in</dt>
                    <dd>{{ $tenancy->move_in?->format('d M Y') ?: '—' }}</dd>
                    <dt>Move out</dt>
                    <dd>{{ $tenancy->move_out?->format('d M Y') ?: '—' }}</dd>
                    <dt>Rent</dt>
                    <dd>£{{ number_format((float) $tenancy->rent, 2) }}{{ $tenancy->frequency ? ' / '.$tenancy->frequency : '' }}</dd>
                    <dt>Deposit</dt>
                    <dd>£{{ number_format((float) $tenancy->deposit, 2) }}</dd>
                    <dt>Type</dt>
                    <dd>{{ $tenancy->tenancyType?->name ?: '—' }}</dd>
                    <dt>Household</dt>
                    <dd>
                        {{ $tenancy->tenantMembers->map(fn ($member) => $member->user?->name)->filter()->implode(', ') ?: '—' }}
                    </dd>
                </dl>
            </div>
        @empty
            <div class="tp-card">
                <p class="tp-empty">No tenancy is linked to this login yet. Ask your letting agent or landlord to add you as a tenant.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
