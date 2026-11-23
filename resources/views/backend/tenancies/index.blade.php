@extends('backend.layout.app')

@section('title', 'Tenancies · Resisquare')

@section('content')
<div class="container-fluid pt-4 lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h4>Tenancies</h4>
            <p>Manage tenancies, occupants and notices.</p>
        </div>
        <a href="{{ route('admin.tenancies.create') }}" class="btn lw-btn-primary btn-sm">Add Tenancy</a>
    </div>

    <div class="lw-chip-filters" role="navigation" aria-label="Tenancy status">
        <a href="{{ route('admin.tenancies.all') }}" class="{{ ! request('status') ? 'is-active' : '' }}">All</a>
        <a href="{{ route('admin.tenancies.all', ['status' => 'Active']) }}" class="{{ request('status') === 'Active' ? 'is-active' : '' }}">Active</a>
        <a href="{{ route('admin.tenancies.all', ['status' => 'Inactive']) }}" class="{{ request('status') === 'Inactive' ? 'is-active' : '' }}">Ending</a>
        <a href="{{ route('admin.tenancies.all', ['status' => 'Terminated']) }}" class="{{ request('status') === 'Terminated' ? 'is-active' : '' }}">Ended</a>
        <a href="{{ route('admin.tenancies.all', ['status' => 'Archived']) }}" class="{{ request('status') === 'Archived' ? 'is-active' : '' }}">Archived</a>
    </div>

    <div class="card lw-card">
        <div class="card-body table-responsive">
        <table class="rs-stack-table table lw-table align-middle">
            <thead>
                <tr>
                    <th>Property</th>
                    <th>Tenant</th>
                    <th>How often</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Rent</th>
                    <th>Deposit</th>
                    <th>Notice</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenancies as $tenancy)
                <tr>
                    <td class="rs-stack-title" data-label="Property">
                        @if($tenancy->property)
                            {{ rs_property_title($tenancy->property) }}
                        @else
                            <span class="text-warning">No property</span>
                            @if(is_landlord_plan_user() || auth()->user()?->can('manage tenancies'))
                                <a href="{{ route('admin.tenancies.edit', $tenancy->id) }}" class="small d-block">Link property</a>
                            @endif
                        @endif
                    </td>
                    <td data-label="Tenant">
                        {{ $tenancy->tenantMembers->map(fn($m) => rs_person($m->user))->filter(fn ($n) => $n !== 'Someone')->implode(', ') ?: '—' }}
                        @php($rtrMissing = $tenancy->status === 'Active' ? $tenancy->tenantMembers->whereNull('right_to_rent_checked_at')->count() : 0)
                        @if($rtrMissing > 0)
                            <small class="text-danger d-block" data-right-to-rent="incomplete">Right to rent not checked{{ $rtrMissing > 1 ? ' ('.$rtrMissing.')' : '' }}</small>
                        @endif
                    </td>
                    <td data-label="How often">{{ $tenancy->rentFrequencyLabel() }}</td>
                    <td data-label="Start Date">{{ rs_date($tenancy->move_in) }}</td>
                    <td data-label="End Date">{{ rs_date($tenancy->move_out) }}</td>
                    <td data-label="Rent">{{ rs_money($tenancy->rent) }}</td>
                    <td data-label="Deposit">{{ rs_money($tenancy->deposit) }}</td>
                    <td data-label="Notice">
                        @if($tenancy->correctionRequests->isNotEmpty())
                            Notice Given
                        @elseif(in_array($tenancy->status, ['Inactive', 'Terminated'], true))
                            {{ \App\Support\CrmLanguage::tenancyStatus($tenancy->status) }}
                        @else
                            —
                        @endif
                    </td>
                    <td data-label="Status">
                        <span class="badge {{ $tenancy->status === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ \App\Support\CrmLanguage::tenancyStatus($tenancy->status) }}
                        </span>
                    </td>
                    <td class="rs-stack-actions">
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.tenancies.show', $tenancy->id) }}" class="btn btn-sm btn-outline-info">View</a>
                            @if(is_landlord_plan_user() || auth()->user()?->can('manage tenancies'))
                            <a href="{{ route('admin.tenancies.edit', $tenancy->id) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                            @endif
                            <a href="{{ route('admin.tenancies.rent-ledger', $tenancy->id) }}" class="btn btn-sm btn-outline-primary">Rent</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">
                        <div class="lw-empty">
                            <div class="lw-empty-icon"><i class="bi bi-key"></i></div>
                            <div class="lw-empty-title">No Tenancies Yet</div>
                            <p class="mb-3">Add a tenancy to connect tenants, rent and documents to a property.</p>
                            <a href="{{ route('admin.tenancies.create') }}" class="btn lw-btn-primary" data-next-action="add-tenancy">Add Tenancy</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $tenancies->withQueryString()->links() }}
    </div>
</div>
@endsection
