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
                        <label class="form-label text-muted small fw-medium">Postcode</label>
                        <p class="fw-medium mb-0">{{ $authUser->zip ?: 'Not set' }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-medium">Full Address</label>
                        @php
                            $profileAddress = implode(', ', array_filter([
                                $authUser->address_line_1,
                                $authUser->address_line_2,
                                $authUser->city,
                                $countryName ?? null,
                                $authUser->zip,
                            ], fn ($part) => filled($part) && $part !== 'N/A'));
                        @endphp
                        <p class="fw-medium mb-0">{{ $profileAddress !== '' ? $profileAddress : 'Not set' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($authUser->ownedCompany)
        @php
            $company = $authUser->ownedCompany;
            $canTransferCompany = auth()->user()->can('transfer company owner') || auth()->id() === $company->owner_user_id;
        @endphp
        <div class="card border-0 card-shadow mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">Company</h4>
                        <p class="text-muted mb-0">{{ $company->name ?? 'N/A' }}</p>
                    </div>
                    @if($company->logo_path)
                        <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Company Logo" class="img-thumbnail" style="max-height: 72px;">
                    @endif
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="form-label text-muted small fw-medium">Registration Number</label>
                        <p class="fw-medium mb-0">{{ $company->registration_number ?: 'N/A' }}</p>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label text-muted small fw-medium">VAT Number</label>
                        <p class="fw-medium mb-0">{{ $company->vat_number ?: 'N/A' }}</p>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label text-muted small fw-medium">Website</label>
                        <p class="fw-medium mb-0">{{ $company->website ?: 'N/A' }}</p>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label text-muted small fw-medium">Services</label>
                        <p class="fw-medium mb-0">{{ collect($company->services ?? [])->map(fn($service) => Str::headline($service))->implode(', ') ?: 'N/A' }}</p>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label text-muted small fw-medium">Emails</label>
                        <p class="fw-medium mb-0">{{ collect($company->emails ?? [])->implode(', ') ?: 'N/A' }}</p>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label text-muted small fw-medium">Phones</label>
                        <p class="fw-medium mb-0">{{ collect($company->phones ?? [])->implode(', ') ?: 'N/A' }}</p>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label text-muted small fw-medium">Registered Address</label>
                        <p class="fw-medium mb-0">{{ $company->registered_address ?: 'N/A' }}</p>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label text-muted small fw-medium">Communication Address</label>
                        <p class="fw-medium mb-0">{{ $company->communication_address ?: 'N/A' }}</p>
                    </div>
                </div>

                <hr>
                <h5 class="fw-bold mb-3">Branches</h5>
                <div class="row">
                    @forelse($company->branches as $branch)
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold mb-2">{{ $branch->name ?: 'Branch' }}</h6>
                                <p class="mb-1">{{ implode(', ', array_filter([$branch->address_line_1 ?: $branch->address, $branch->address_line_2, $branch->city, $branch->county, $branch->postcode, $branch->country])) ?: 'N/A' }}</p>
                                <p class="mb-1 text-muted">{{ $branch->user_email ?: 'No email' }}</p>
                                <p class="mb-0 text-muted">{{ $branch->user_phone ?: 'No phone' }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <p class="text-muted mb-0">No branches added.</p>
                        </div>
                    @endforelse
                </div>

                @if($canTransferCompany)
                    <hr>
                    <h5 class="fw-bold mb-3">Transfer Owner</h5>
                    <form action="{{ route('admin.companies.transfer-owner', $company->id) }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label">New Owner</label>
                            <select name="new_owner_user_id" class="form-control select2" required>
                                <option value="">Select user</option>
                                @foreach($transferUsers as $transferUser)
                                    <option value="{{ $transferUser->id }}">{{ $transferUser->name }} ({{ $transferUser->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Transfer Note</label>
                            <input type="text" name="note" class="form-control" maxlength="1000">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary w-100">Transfer</button>
                        </div>
                    </form>

                    @if($company->ownerTransfers->isNotEmpty())
                        <div class="table-responsive mt-3">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Old Owner</th>
                                        <th>New Owner</th>
                                        <th>By</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($company->ownerTransfers as $transfer)
                                        <tr>
                                            <td>{{ $transfer->transferred_at?->format('d/m/Y H:i') }}</td>
                                            <td>{{ $transfer->oldOwner?->name ?? 'N/A' }}</td>
                                            <td>{{ $transfer->newOwner?->name ?? 'N/A' }}</td>
                                            <td>{{ $transfer->transferredBy?->name ?? 'N/A' }}</td>
                                            <td>{{ $transfer->note ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@include('backend.partials.assets.select2')
@section('page.scripts')
<script>
    initSelect2('.select2');
</script>
@endsection
