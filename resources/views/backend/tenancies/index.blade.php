@extends('backend.layout.app')

@section('content')
<div class="container-fluid pt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>All Tenancies</h4>
        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="Active"   {{ request('status') === 'Active'   ? 'selected' : '' }}>Active</option>
                <option value="Archived" {{ request('status') === 'Archived' ? 'selected' : '' }}>Archived</option>
            </select>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table rs_table">
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
                            N/A
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
                <tr><td colspan="8" class="text-center text-muted">No tenancies found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $tenancies->withQueryString()->links() }}
    </div>
</div>
@endsection
