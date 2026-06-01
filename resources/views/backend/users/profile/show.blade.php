@extends('backend.layout.app')

@section('content')
<style>
    .profile-avatar {
        width: 150px;
        height: 150px;
        border: 4px solid #e9ecef;
    }
    .card-shadow {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    .icon-box {
        width: 40px;
        height: 40px;
        background: rgba(102, 126, 234, 0.1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .badge-custom {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }
</style>

<div class="container py-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">User Profile</h2>
            <p class="text-muted mb-0">View and manage user information</p>
        </div>
        <div class="d-flex gap-2">
            @can('view contacts')
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-2"></i>Back to Users
            </a>
            @endcan
            @if(auth()->user()->id === $authUser->id)
            <a href="{{ route('admin.users.profile.edit', $authUser->id) }}" class="btn btn-primary gradient-bg border-0">
                <i class="bi bi-pencil-square me-2"></i>Edit Profile
            </a>
            @endif
        </div>
    </div>

    <!-- Profile Card -->
    <div class="card border-0 card-shadow">
        <div class="card-header bg-transparent border-0 pb-0">
            <div class="row align-items-start">
                <!-- Avatar -->
                <div class="col-md-3 text-center text-md-start mb-4 mb-md-0">
                    @if ($authUser->profile_picture)
                        <img src="{{ asset('storage/' . $authUser->profile_picture) }}" alt="Profile Picture" class="rounded-circle profile-avatar">
                    @else
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center profile-avatar">
                            <i class="bi bi-person text-muted" style="font-size: 60px;"></i>
                        </div>
                    @endif
                </div>

                <!-- Basic Info -->
                <div class="col-md-9">
                    <h3 class="fw-bold mb-3">{{ $authUser->title ? $authUser->title . ' ' : '' }}{{ $authUser->name ?? 'N/A' }}</h3>

                    @if(method_exists($authUser, 'getRoleNames') && $authUser->getRoleNames()->count() > 0)
                        <div class="mb-3">
                            @foreach($authUser->getRoleNames() as $role)
                                <span class="badge badge-custom me-2">{{ $role }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="row text-muted">
                        <div class="col-lg-6 mb-2">
                            <i class="bi bi-envelope text-primary me-2"></i>
                            {{ $authUser->email ?? 'N/A' }}
                        </div>
                        <div class="col-lg-6 mb-2">
                            <i class="bi bi-telephone text-primary me-2"></i>
                            {{ $authUser->phone ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="mx-4">

        <div class="card-body pt-4">
            <div class="row">
                <!-- Contact Information -->
                <div class="col-lg-6 mb-5">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-box me-3">
                            <i class="bi bi-person text-primary"></i>
                        </div>
                        <h4 class="mb-0">Contact Information</h4>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-medium">Email Address</label>
                        <p class="fw-medium mb-0">{{ $authUser->email ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-medium">Phone Number</label>
                        <p class="fw-medium mb-0">{{ $authUser->phone ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-medium">Roles</label>
                        <p class="fw-medium mb-0">{{ $authUser->getRoleNames()->implode(', ') ?: 'N/A' }}</p>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="col-lg-6 mb-5">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-box me-3">
                            <i class="bi bi-geo-alt text-primary"></i>
                        </div>
                        <h4 class="mb-0">Address Information</h4>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-medium">Address Line 1</label>
                        <p class="fw-medium mb-0">{{ $authUser->address_line_1 ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-medium">Address Line 2</label>
                        <p class="fw-medium mb-0">{{ $authUser->address_line_2 ?? 'N/A' }}</p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-medium">Country</label>
                            <p class="fw-medium mb-0">{{ $countryName ?? 'N/A' }}</p>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-medium">City</label>
                            <p class="fw-medium mb-0">{{ $authUser->city ?? 'N/A' }}</p>
                        </div>
                        {{-- <div class="col-6">
                            <label class="form-label text-muted small fw-medium">State</label>
                            <p class="fw-medium mb-0">{{ $authUser->state ?? 'N/A' }}</p>
                        </div> --}}
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-medium">ZIP Code</label>
                        <p class="fw-medium mb-0">{{ $authUser->zip ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-medium">Full Address</label>
                        <p class="fw-medium mb-0">{{ $authUser->address_line_1 }}, {{ $authUser->address_line_2 }}, {{ $countryName }}, {{ $authUser->city }}, {{ $authUser->zip }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
