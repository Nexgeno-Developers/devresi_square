@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page">
    <div class="lw-hero">
        <h4>Portal Access</h4>
        <p>Invite a tenant to their own portal. They only see their tenancy, rent, documents and repairs.</p>
    </div>

    <div class="card lw-card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Invite a tenant</h5>
            <p class="text-muted mb-3">Pick the tenancy, then their name and email.</p>

            @if(($orphanTenancies ?? collect())->isNotEmpty() && $tenancies->isEmpty())
                <div class="alert alert-lw mb-3">
                    Your active tenancy is not linked to a property, so the invite form is hidden.
                    @foreach($orphanTenancies as $orphan)
                        <a class="alert-link" href="{{ route('admin.tenancies.edit', $orphan->id) }}">Link tenancy #{{ $orphan->id }}</a>@if(! $loop->last), @endif
                    @endforeach
                </div>
            @endif

            @if($tenancies->isEmpty())
                <div class="lw-empty pt-2">
                    <div class="lw-empty-title">Add a tenancy on a property first</div>
                    <p class="mb-3">Then you can invite the tenant here.</p>
                    <a href="{{ route('admin.tenancies.create') }}" class="btn lw-btn-primary">Add tenancy</a>
                </div>
            @else
                <form action="{{ route('admin.portal-access.invite') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="tenancy_id" class="form-label">Tenancy</label>
                        <select name="tenancy_id" id="tenancy_id" class="form-control @error('tenancy_id') is-invalid @enderror" required>
                            <option value="">Select a tenancy</option>
                            @foreach($tenancies as $tenancy)
                                @php
                                    $propertyLabel = $tenancy->property?->full_address
                                        ?: ($tenancy->property?->line_1 ?: 'Property #'.$tenancy->property_id);
                                @endphp
                                <option value="{{ $tenancy->id }}" @selected((string) old('tenancy_id') === (string) $tenancy->id)>
                                    {{ $propertyLabel }} — {{ $tenancy->status ?: 'Tenancy' }} (#{{ $tenancy->id }})
                                </option>
                            @endforeach
                        </select>
                        @error('tenancy_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="invite_name" class="form-label">Name</label>
                        <input type="text" name="name" id="invite_name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="invite_email" class="form-label">Email</label>
                        <input type="email" name="email" id="invite_email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required maxlength="255">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn lw-btn-primary">Invite tenant</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="card lw-card">
        <div class="card-body table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Member Type</th>
                        <th>Can Login</th>
                        <th>Assigned Properties</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($portalUsers as $membership)
                        <tr>
                            <td>{{ $membership->user?->name ?? 'Unknown user' }}</td>
                            <td>{{ $membership->user?->email ?? '-' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $membership->member_type)) }}</td>
                            <td>{{ $membership->can_login ? 'Yes' : 'No' }}</td>
                            <td>{{ (int) $membership->assigned_properties_count }}</td>
                            <td>{{ ucfirst($membership->status) }}</td>
                            <td class="text-end">
                                @if($membership->user)
                                    <a href="{{ route('admin.users.edit', $membership->user_id) }}" class="btn btn-sm btn-outline-primary">View/Edit</a>
                                    @if($membership->status === 'active')
                                        <form action="{{ route('admin.portal-access.revoke', $membership->user) }}" method="POST" class="d-inline" onsubmit="return confirm('Revoke portal access for this person?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button>
                                        </form>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No portal users found for this account.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $portalUsers->links() }}
        </div>
    </div>
</div>
@endsection
