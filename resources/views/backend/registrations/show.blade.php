@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ $registration->full_name }}</h1>
            <small class="text-muted">Registration #{{ $registration->id }}</small>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('admin.registrations.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
</div>

<div class="row">

    {{-- ── Left column: details + approve/reject ── --}}
    <div class="col-lg-4">

        {{-- Registration info --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 h6">Registration Details</h5>
                @php
                    $badgeMap = [
                        'pending'        => 'warning',
                        'email_verified' => 'info',
                        'phone_verified' => 'info',
                        'verified'       => 'primary',
                        'approved'       => 'success',
                        'rejected'       => 'danger',
                    ];
                @endphp
                <span class="badge bg-{{ $badgeMap[$registration->status] ?? 'secondary' }}">
                    {{ Str::headline($registration->status) }}
                </span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="text-muted ps-3" width="42%">Name</th>
                        <td>{{ $registration->full_name }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted ps-3">Email</th>
                        <td>{{ $registration->email }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted ps-3">Phone</th>
                        <td>{{ $registration->phone ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted ps-3">Type</th>
                        <td>{{ Str::headline(str_replace('_', ' ', $registration->type)) }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted ps-3">Verified via</th>
                        <td>
                            @if($registration->verify_via === 'email')
                                <i class="fas fa-envelope text-primary"></i> Email
                            @elseif($registration->verify_via === 'phone')
                                <i class="fas fa-mobile-alt text-primary"></i> Phone
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted ps-3">OTP Verified</th>
                        <td>
                            @if($registration->otp_verified_at)
                                <span class="text-success">
                                    <i class="fas fa-check-circle"></i>
                                    {{ $registration->otp_verified_at->format('d/m/Y H:i') }}
                                </span>
                            @else
                                <span class="text-muted">Not verified</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted ps-3">Registered</th>
                        <td>{{ $registration->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @if($registration->approved_at)
                        <tr>
                            <th class="text-muted ps-3">Approved At</th>
                            <td>{{ $registration->approved_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted ps-3">Approved By</th>
                            <td>{{ $registration->approvedBy?->name ?? '—' }}</td>
                        </tr>
                    @endif
                    @if($registration->rejected_at)
                        <tr>
                            <th class="text-muted ps-3">Rejected At</th>
                            <td>{{ $registration->rejected_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endif
                    @if($registration->ip)
                        <tr>
                            <th class="text-muted ps-3">IP</th>
                            <td>{{ $registration->ip }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Approve form (with role picker) --}}
        @if(!in_array($registration->status, ['approved','rejected']))
            <div class="card mb-3 border-success">
                <div class="card-header bg-success bg-opacity-10">
                    <h5 class="mb-0 h6 text-success">
                        <i class="fas fa-check-circle me-1"></i> Approve Registration
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.registrations.approve', $registration->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Assign Role</label>
                            <select name="role_id" class="form-select" required>
                                <option value="">Select role…</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}"
                                        {{ $role->name === $suggestedRole ? 'selected' : '' }}>
                                        {{ $role->name }}
                                        @if($role->name === $suggestedRole)
                                            (suggested)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Auto-suggested based on type: <strong>{{ $suggestedRole }}</strong>.
                                You can change it before approving.
                            </small>
                        </div>
                        <button type="submit" class="btn btn-success w-100"
                                onclick="return confirm('Approve this registration and send welcome email with credentials?')">
                            <i class="fas fa-check me-1"></i> Approve &amp; Send Credentials
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mb-3 border-danger">
                <div class="card-header bg-danger bg-opacity-10">
                    <h5 class="mb-0 h6 text-danger">
                        <i class="fas fa-times-circle me-1"></i> Reject Registration
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        The user will receive a rejection notification email.
                    </p>
                    <form action="{{ route('admin.registrations.reject', $registration->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100"
                                onclick="return confirm('Reject this registration? A rejection email will be sent.')">
                            <i class="fas fa-times me-1"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Linked user account --}}
        @if($registration->user)
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0 h6">Linked User Account</h5></div>
                <div class="card-body">
                    <p class="mb-1 fw-semibold">{{ $registration->user->name }}</p>
                    <p class="mb-1 text-muted small">{{ $registration->user->email }}</p>
                    <p class="mb-2 text-muted small">
                        Role: <strong>{{ $registration->user->roles->first()?->name ?? 'None' }}</strong>
                    </p>
                    <a href="{{ route('admin.users.show', $registration->user->id) }}"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-user me-1"></i> View User Profile
                    </a>
                </div>
            </div>
        @endif

    </div>

    {{-- ── Right column: permissions ── --}}
    <div class="col-lg-8">
        @if($registration->status === 'approved' && $registration->user)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">
                        Custom Permissions
                        <small class="text-muted fw-normal ms-2">
                            Role: <strong>{{ $registration->user->roles->first()?->name ?? 'None' }}</strong>
                            — Role permissions are locked <i class="fas fa-lock fa-xs"></i>.
                            Toggle additional permissions below.
                        </small>
                    </h5>
                </div>
                <form action="{{ route('admin.registrations.permissions', $registration->id) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @php
                            $rolePermissions = $registration->user->roles->first()
                                ? $registration->user->roles->first()->permissions->pluck('name')->toArray()
                                : [];
                        @endphp

                        @foreach($permissions as $section => $sectionPerms)
                            <div class="mb-4">
                                <h6 class="text-uppercase text-muted small fw-bold border-bottom pb-1 mb-2">
                                    {{ Str::headline($section) }}
                                </h6>
                                <div class="row g-2">
                                    @foreach($sectionPerms as $perm)
                                        @php
                                            $isRole    = in_array($perm->name, $rolePermissions);
                                            $isChecked = in_array($perm->name, $userPermissions) || $isRole;
                                        @endphp
                                        <div class="col-lg-3 col-md-4 col-sm-6">
                                            <div class="p-2 border rounded {{ $isRole ? 'bg-light' : '' }}">
                                                <label class="d-flex align-items-center gap-2 mb-0"
                                                       style="cursor:{{ $isRole ? 'default' : 'pointer' }}">
                                                    @if($isRole)
                                                        <input type="checkbox" class="form-check-input" checked disabled>
                                                    @else
                                                        <input type="checkbox" class="form-check-input"
                                                               name="permissions[]" value="{{ $perm->name }}"
                                                               {{ $isChecked ? 'checked' : '' }}>
                                                    @endif
                                                    <span class="small {{ $isRole ? 'text-muted' : '' }}">
                                                        {{ Str::headline($perm->name) }}
                                                        @if($isRole)
                                                            <i class="fas fa-lock fa-xs text-muted"></i>
                                                        @endif
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Permissions
                        </button>
                    </div>
                </form>
            </div>
        @elseif($registration->status === 'rejected')
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-ban fa-3x mb-3 text-danger"></i>
                    <p class="mb-0">This registration was rejected.</p>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-user-clock fa-3x mb-3"></i>
                    <p class="mb-0">Permissions will be available after the registration is approved.</p>
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
