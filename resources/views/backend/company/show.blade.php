@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">My Company</h1>
            <small class="text-muted">Your company profile</small>
        </div>
        @if($company)
        @can('edit own company')
        <div class="col-md-6 text-right">
            <a href="{{ route('my_company.edit') }}" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-edit me-1"></i> Edit Profile
            </a>
        </div>
        @endcan
        @endif
    </div>
</div>

@if(!$company)
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-building fa-3x text-muted mb-3 d-block"></i>
        <h5 class="fw-bold mb-2">No Company Profile</h5>
        <p class="text-muted">No company is linked to your account yet.</p>
    </div>
</div>
@else

<div class="row g-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6"><i class="fas fa-building me-2"></i>Company Details</h5></div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-4 mb-4">
                    @if($company->logo_path)
                        <img src="{{ asset('storage/' . $company->logo_path) }}"
                             alt="Logo" class="rounded border" style="width:80px;height:80px;object-fit:contain;">
                    @else
                        <div class="bg-light rounded border d-flex align-items-center justify-content-center"
                             style="width:80px;height:80px;">
                            <i class="fas fa-building fa-2x text-muted"></i>
                        </div>
                    @endif
                    <div>
                        <h4 class="fw-bold mb-1">{{ $company->name }}</h4>
                        @if($company->registration_number)
                            <p class="text-muted small mb-0">Reg: {{ $company->registration_number }}</p>
                        @endif
                        @if($company->vat_number)
                            <p class="text-muted small mb-0">VAT: {{ $company->vat_number }}</p>
                        @endif
                    </div>
                </div>

                <div class="row g-3">
                    @if($company->registered_address)
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Registered Address</p>
                        <p class="mb-0">{{ $company->registered_address }}</p>
                    </div>
                    @endif
                    @if($company->communication_address)
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Communication Address</p>
                        <p class="mb-0">{{ $company->communication_address }}</p>
                    </div>
                    @endif
                    @if($company->website)
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Website</p>
                        <a href="{{ $company->website }}" target="_blank">{{ $company->website }}</a>
                    </div>
                    @endif
                    @if(!empty($company->emails))
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Email(s)</p>
                        @foreach($company->emails as $email)
                            <p class="mb-0">{{ $email }}</p>
                        @endforeach
                    </div>
                    @endif
                    @if(!empty($company->phones))
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Phone(s)</p>
                        @foreach($company->phones as $phone)
                            <p class="mb-0">{{ $phone }}</p>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
