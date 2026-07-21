@extends('backend.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1">Portal Access</h4>
            <div class="text-muted">{{ $account->account_name ?? 'Current account' }}</div>
        </div>
    </div>

    <div class="card">
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
