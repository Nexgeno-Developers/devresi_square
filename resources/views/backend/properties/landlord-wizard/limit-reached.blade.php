@extends('backend.layout.app')

@section('content')
    <div class="lpw-page">
        <div class="container-fluid py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="lpw-content text-center">
                        <p class="lpw-step-label mb-2">Property limit reached</p>
                        <h1 class="lpw-step-title mb-3">You have used all properties on your plan</h1>
                        <p class="lpw-step-lead mx-auto mb-4">
                            {{ $message ?? 'Your current plan has reached the property limit.' }}
                            @if (! empty($summary['properties']))
                                You are using {{ $summary['properties']['used'] }} of {{ $summary['properties']['limit'] }} properties
                                on the {{ $summary['plan_name'] ?? 'current' }} plan.
                            @endif
                        </p>

                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <a href="{{ route('admin.properties.index') }}" class="btn btn-outline-secondary btn-lg">
                                View my properties
                            </a>
                            @if (Route::has('backend.billing.index'))
                                <a href="{{ route('backend.billing.index') }}" class="btn btn-primary btn-lg lpw-btn-primary">
                                    Upgrade plan
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="{{ asset('asset/backend/css/landlord-property-wizard.css') }}" rel="stylesheet">
@endpush
