@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">My Landlords</h1>
            <small class="text-muted">Landlord clients you manage</small>
        </div>
        @can('add landlord contacts')
        <div class="col-md-6 text-right">
            <a href="{{ route('estate_agent.landlords.create') }}" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-plus me-1"></i> Add Landlord
            </a>
        </div>
        @endcan
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('estate_agent.landlords.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by name, email, phone…"
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100" type="submit">Search</button>
            </div>
            @if(request('search'))
            <div class="col-md-2">
                <a href="{{ route('estate_agent.landlords.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">Landlord Contacts</h5>
    </div>
    <div class="card-body p-0">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Login Access</th>
                    <th width="10%" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($landlords as $i => $landlord)
                <tr>
                    <td>{{ ($landlords->currentPage() - 1) * $landlords->perPage() + $i + 1 }}</td>
                    <td class="fw-semibold">{{ $landlord->name }}</td>
                    <td>{{ $landlord->email }}</td>
                    <td>{{ $landlord->phone ?? '—' }}</td>
                    <td>
                        @can('manage landlord login')
                        <form action="{{ route('estate_agent.landlords.toggleLogin', $landlord->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm {{ $landlord->can_login ? 'btn-success' : 'btn-outline-secondary' }}"
                                    onclick="return confirm('{{ $landlord->can_login ? 'Disable login for this landlord?' : 'Enable login and send credentials?' }}')">
                                <i class="fas fa-{{ $landlord->can_login ? 'check' : 'times' }}"></i>
                                {{ $landlord->can_login ? 'Enabled' : 'Disabled' }}
                            </button>
                        </form>
                        @else
                            <span class="badge bg-{{ $landlord->can_login ? 'success' : 'secondary' }}">
                                {{ $landlord->can_login ? 'Enabled' : 'Disabled' }}
                            </span>
                        @endcan
                    </td>
                    <td class="text-right">
                        <div class="d-flex justify-content-end" style="gap:4px;">
                            @can('edit landlord contacts')
                            <a href="{{ route('estate_agent.landlords.edit', $landlord->id) }}"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endcan
                            @can('delete landlord contacts')
                            <form action="{{ route('estate_agent.landlords.destroy', $landlord->id) }}"
                                  method="POST" class="d-inline"
                                  onsubmit="return confirm('Remove this landlord contact?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        No landlord contacts yet.
                        @can('add landlord contacts')
                            <a href="{{ route('estate_agent.landlords.create') }}">Add your first landlord</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $landlords->appends(request()->query())->links() }}
    </div>
</div>
@endsection
