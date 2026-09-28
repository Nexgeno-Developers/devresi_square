@extends('backend.layout.app')

@section('title', 'Tenancies · Resisquare')

@section('content')
<div class="container-fluid pt-4 lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h4>
                @if(request('status') === 'Active') Active tenancies
                @elseif(request('status') === 'Archived') Archived tenancies
                @elseif(request('status') === 'Inactive') Inactive tenancies
                @elseif(request('status') === 'Terminated') Terminated tenancies
                @else Tenancies
                @endif
            </h4>
            <p>Tenancies on your properties. Link any tenancy that has no property before inviting or issuing rent.</p>
        </div>
        <a href="{{ route('admin.tenancies.create') }}" class="btn lw-btn-primary btn-sm">Add tenancy</a>
    </div>

    <div class="lw-chip-filters" role="navigation" aria-label="Tenancy status">
        <a href="{{ route('admin.tenancies.all') }}" class="{{ ! request('status') ? 'is-active' : '' }}">All</a>
        <a href="{{ route('admin.tenancies.all', ['status' => 'Active']) }}" class="{{ request('status') === 'Active' ? 'is-active' : '' }}">Active</a>
        <a href="{{ route('admin.tenancies.all', ['status' => 'Archived']) }}" class="{{ request('status') === 'Archived' ? 'is-active' : '' }}">Archived</a>
        <a href="{{ route('admin.tenancies.all', ['status' => 'Inactive']) }}" class="{{ request('status') === 'Inactive' ? 'is-active' : '' }}">Inactive</a>
        <a href="{{ route('admin.tenancies.all', ['status' => 'Terminated']) }}" class="{{ request('status') === 'Terminated' ? 'is-active' : '' }}">Terminated</a>
    </div>

    <div class="card lw-card">
        <div class="card-body table-responsive">
        <table class="table lw-table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Property</th>
                    <th>Tenants</th>
                    <th>Status</th>
                    <th>Move In</th>
                    <th>Move Out</th>
                    <th>Rent</th>
                    <th>Confirmation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenancies as $tenancy)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        @if($tenancy->property)
                            {{ rs_property_title($tenancy->property) }}
                        @else
                            <span class="text-warning">No property</span>
                            @if(is_landlord_plan_user() || auth()->user()?->can('manage tenancies'))
                                <a href="{{ route('admin.tenancies.edit', $tenancy->id) }}" class="small d-block">Link property</a>
                            @endif
                        @endif
                    </td>
                    <td>
                        {{ $tenancy->tenantMembers->map(fn($m) => rs_person($m->user))->filter(fn ($n) => $n !== 'Someone')->implode(', ') ?: '—' }}
                    </td>
                    <td>
                        <span class="badge {{ $tenancy->status === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $tenancy->status }}
                        </span>
                    </td>
                    <td>{{ rs_date($tenancy->move_in) }}</td>
                    <td>{{ rs_date($tenancy->move_out) }}</td>
                    <td>{{ rs_money($tenancy->rent) }}</td>
                    <td>
                        @if($tenancy->correctionRequests->isNotEmpty())
                            <span class="badge bg-warning text-dark">Correction pending</span>
                        @else
                            @php
                                $waiting = $tenancy->tenantMembers->contains(fn ($member) => ($member->details_status ?: 'pending') !== 'confirmed');
                            @endphp
                            @if($waiting)
                                <span class="badge bg-secondary">Awaiting tenant</span>
                            @else
                                <span class="badge bg-success">Confirmed</span>
                            @endif
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.tenancies.show', $tenancy->id) }}" class="btn btn-sm btn-outline-info">View</a>
                            @if(is_landlord_plan_user() || auth()->user()?->can('manage tenancies'))
                            <a href="{{ route('admin.tenancies.edit', $tenancy->id) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                            @endif
                            <a href="{{ route('admin.tenancies.rent-ledger', $tenancy->id) }}" class="btn btn-sm btn-outline-primary">Rent Ledger</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="lw-empty">
                            <div class="lw-empty-icon"><i class="bi bi-key"></i></div>
                            <div class="lw-empty-title">No tenancies yet</div>
                            <p class="mb-3">Add a let on a property, then invite the tenant and issue rent.</p>
                            <a href="{{ route('admin.tenancies.create') }}" class="btn lw-btn-primary" data-next-action="add-tenancy">Add tenancy</a>
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
