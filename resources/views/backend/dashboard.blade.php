@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Admin Dashboard</h2>
    <div class="row g-4">

        {{-- Users --}}
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 text-primary">
                        <i class="bi bi-people-fill fs-1"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-muted">Users</h6>
                        <h4 class="mb-0">{{ $usersCount }}</h4>
                    </div>
                </div>
            </div>
        </div>

        {{-- Properties --}}
        @canany(['view properties', 'edit properties'])
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 text-info">
                        <i class="bi bi-house-door-fill fs-1"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-muted">Properties</h6>
                        <h4 class="mb-0">{{ $propertiesCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
        @endcanany

        @can('view invoices')
        {{-- Invoices --}}
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 text-danger">
                        <i class="bi bi-receipt fs-1"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-muted">Invoices</h6>
                        <h4 class="mb-0">{{ $invoicesCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        
        {{-- Work Orders --}}
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 text-warning">
                        <i class="bi bi-clipboard-check-fill fs-1"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-muted">Work Orders</h6>
                        <h4 class="mb-0">{{ $workOrdersCount }}</h4>
                    </div>
                </div>
            </div>
        </div>

        {{-- Repair Issues --}}
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 text-secondary">
                        <i class="bi bi-tools fs-1"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-muted">Repair Issues</h6>
                        <h4 class="mb-0">{{ $repairIssuesCount }}</h4>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
