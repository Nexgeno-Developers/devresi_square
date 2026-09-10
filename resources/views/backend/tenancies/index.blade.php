@extends('backend.layout.app')

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
            <p>Lets on your properties. Link any tenancy that has no property before inviting or issuing rent.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="Active"   {{ request('status') === 'Active'   ? 'selected' : '' }}>Active</option>
                    <option value="Archived" {{ request('status') === 'Archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </form>
            <a href="{{ route('admin.tenancies.create') }}" class="btn btn-light btn-sm">Add tenancy</a>
        </div>
    </div>

    <div class="card lw-card">
        <div class="card-body table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Property</th>
                    <th>Tenants</th>
                    <th>Status</th>
                    <th>Move In</th>
                    <th>Move Out</th>
                    <th>Rent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenancies as $tenancy)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        @if($tenancy->property)
                            {{ $tenancy->property->prop_name ?: $tenancy->property->line_1 }},
                            {{ $tenancy->property->city }}
                        @else
                            <span class="text-warning">No property</span>
                            @can('manage tenancies')
                                <a href="{{ route('admin.tenancies.edit', $tenancy->id) }}" class="small d-block">Link property</a>
                            @endcan
                        @endif
                    </td>
                    <td>
                        {{ $tenancy->tenantMembers->map(fn($m) => $m->user?->name)->filter()->implode(', ') ?: '—' }}
                    </td>
                    <td>
                        <span class="badge {{ $tenancy->status === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $tenancy->status }}
                        </span>
                    </td>
                    <td>{{ $tenancy->move_in ? \Carbon\Carbon::parse($tenancy->move_in)->format('d M Y') : '—' }}</td>
                    <td>{{ $tenancy->move_out ? \Carbon\Carbon::parse($tenancy->move_out)->format('d M Y') : '—' }}</td>
                    <td>£{{ number_format((float)$tenancy->rent, 2) }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.tenancies.show', $tenancy->id) }}" class="btn btn-sm btn-outline-info">View</a>
                            @can('manage tenancies')
                            <a href="{{ route('admin.tenancies.edit', $tenancy->id) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                            @endcan
                            <a href="{{ route('admin.tenancies.rent-ledger', $tenancy->id) }}" class="btn btn-sm btn-outline-primary">Rent Ledger</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="lw-empty">
                            <div class="lw-empty-icon"><i class="bi bi-key"></i></div>
                            <div class="lw-empty-title">No tenancies yet</div>
                            <p class="mb-3">Add a let on a property, then invite the tenant and issue rent.</p>
                            <a href="{{ route('admin.tenancies.create') }}" class="btn lw-btn-primary">Add tenancy</a>
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
