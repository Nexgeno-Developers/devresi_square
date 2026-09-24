@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">My tenancy</p>
            <h1>Your lease</h1>
            <p>Occupancy details for the homes linked to your login.</p>
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
                <dl class="tp-dl" data-let-facts="tenant">
                    <dt>Move in</dt>
                    <dd data-let-move-in>{{ rs_date($tenancy->move_in) }}</dd>
                    <dt>Move out</dt>
                    <dd>{{ rs_date($tenancy->move_out) }}</dd>
                    <dt>Rent</dt>
                    <dd data-let-rent>{{ rs_money($tenancy->rent) }}</dd>
                    <dt>Frequency</dt>
                    <dd data-let-frequency>{{ $tenancy->rentFrequencyLabel() }}</dd>
                    <dt>Due day</dt>
                    <dd data-let-due>{{ $tenancy->rentDueLabel() }}</dd>
                    <dt>Deposit</dt>
                    <dd>{{ rs_money($tenancy->deposit) }}</dd>
                    @if((float) $tenancy->deposit > 0)
                        <dt>Deposit protection</dt>
                        <dd data-deposit-tenant="1">
                            @if($tenancy->depositSchemeLabel() && $tenancy->deposit_protected_at)
                                {{ $tenancy->depositSchemeLabel() }}. Protection recorded {{ rs_date($tenancy->deposit_protected_at) }}.
                            @elseif($tenancy->depositSchemeLabel())
                                {{ $tenancy->depositSchemeLabel() }}. Protection has not been recorded yet.
                            @else
                                Your landlord has not recorded the protection scheme yet.
                            @endif
                        </dd>
                    @endif
                    <dt>Type</dt>
                    <dd>{{ $tenancy->tenancyType?->name ?: '—' }}</dd>
                    <dt>Household</dt>
                    <dd>
                        {{ $tenancy->tenantMembers->map(fn ($member) => $member->user?->name)->filter()->implode(', ') ?: '—' }}
                    </dd>
                    <dt>Your confirmation</dt>
                    <dd>
                        @php
                            $mine = $tenancy->tenantMembers->firstWhere('user_id', auth()->id());
                            $status = $mine?->details_status ?: 'pending';
                        @endphp
                        @if($status === 'confirmed')
                            Confirmed{{ $mine?->details_confirmed_at ? ' on '.$mine->details_confirmed_at->format('d M Y') : '' }}
                        @elseif($status === 'correction_requested')
                            Correction sent — waiting for your landlord
                        @else
                            Waiting for you to confirm
                        @endif
                    </dd>
                </dl>
            </div>
        @empty
            <div class="tp-card">
                <p class="tp-empty">No tenancy is linked to this login yet. Ask your landlord to add you as a tenant.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
