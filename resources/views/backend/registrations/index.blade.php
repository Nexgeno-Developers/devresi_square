@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">Registrations</h1>
            <small class="text-muted">Only verified registrations are shown here.</small>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.registrations.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search name, email, phone…"
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="verified"  {{ request('status') == 'verified'  ? 'selected' : '' }}>Verified (Pending Review)</option>
                    <option value="approved"  {{ request('status') == 'approved'  ? 'selected' : '' }}>Approved</option>
                    <option value="rejected"  {{ request('status') == 'rejected'  ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.registrations.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0 h6">Verified Registrations</h5>
        @php
            $pendingCount = \App\Models\Registration::where('status', 'verified')->count();
        @endphp
        @if($pendingCount > 0)
            <span class="badge bg-warning text-dark">
                {{ $pendingCount }} awaiting review
            </span>
        @endif
    </div>
    <div class="card-body p-0">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Verified via</th>
                    <th>OTP Verified</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th width="8%" class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registrations as $i => $reg)
                    <tr>
                        <td>{{ ($registrations->currentPage() - 1) * $registrations->perPage() + $i + 1 }}</td>
                        <td>
                            <a href="{{ route('admin.registrations.show', $reg->id) }}" class="fw-semibold">
                                {{ $reg->full_name }}
                            </a>
                        </td>
                        <td>{{ $reg->email }}</td>
                        <td>{{ $reg->phone ?? '—' }}</td>
                        <td>{{ Str::headline(str_replace('_', ' ', $reg->type)) }}</td>
                        <td>
                            @if($reg->verify_via === 'email')
                                <i class="fas fa-envelope text-primary"></i> Email
                            @elseif($reg->verify_via === 'phone')
                                <i class="fas fa-mobile-alt text-primary"></i> Phone
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($reg->otp_verified_at)
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>{{ $reg->otp_verified_at->format('d/m/Y H:i') }}
                                </span>
                            @else
                                <span class="badge bg-secondary">Not verified</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $badgeMap = [
                                    'verified' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                ];
                                $badge = $badgeMap[$reg->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $badge }}">
                                {{ $reg->status === 'verified' ? 'Pending Review' : Str::headline($reg->status) }}
                            </span>
                        </td>
                        <td>{{ $reg->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-right">
                            {{-- Only View button — approve/reject is done on the detail page --}}
                            <a href="{{ route('admin.registrations.show', $reg->id) }}"
                               class="btn btn-sm btn-outline-primary" title="Review">
                                <i class="fas fa-eye"></i>
                                @if($reg->status === 'verified')
                                    Review
                                @endif
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            No verified registrations found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $registrations->appends(request()->input())->links() }}
    </div>
</div>
@endsection
