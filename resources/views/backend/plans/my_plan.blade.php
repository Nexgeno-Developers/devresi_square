@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">My Plans</h1>
            <small class="text-muted">Your active subscription details</small>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

        @if($userPlan && $userPlan->isActive())

        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between"
                 style="background:#0b1f3a;">
                <h5 class="mb-0 text-white fw-bold">
                    <i class="fas fa-crown me-2 text-warning"></i>{{ $userPlan->plan->name }}
                </h5>
                <span class="badge bg-success px-3 py-2">Active</span>
            </div>
            <div class="card-body p-4">

                @php
                    $daysLeft  = $userPlan->daysRemaining();
                    $totalDays = $userPlan->starts_at && $userPlan->ends_at
                        ? $userPlan->starts_at->diffInDays($userPlan->ends_at)
                        : 30;
                    $percent   = $totalDays > 0 ? min(100, round(($daysLeft / $totalDays) * 100)) : 0;
                    $barColor  = $daysLeft <= 7 ? 'bg-danger' : ($daysLeft <= 30 ? 'bg-warning' : 'bg-success');
                @endphp

                <div class="text-center mb-4">
                    <p class="text-muted small mb-1">Days Remaining</p>
                    <h1 class="fw-bold mb-0" style="font-size:3.5rem;color:#0b1f3a;">{{ $daysLeft }}</h1>
                    <p class="text-muted small">out of {{ $totalDays }} days</p>
                    <div class="progress mt-2" style="height:10px;border-radius:10px;">
                        <div class="progress-bar {{ $barColor }}"
                             role="progressbar"
                             style="width:{{ $percent }}%;border-radius:10px;">
                        </div>
                    </div>
                    @if($daysLeft <= 7)
                        <p class="text-danger small mt-2 fw-semibold">
                            <i class="fas fa-exclamation-triangle me-1"></i>Your plan expires soon!
                        </p>
                    @endif
                </div>

                <hr>

                <div class="row g-3 text-center">
                    <div class="col-6">
                        <p class="text-muted small mb-1">Plan</p>
                        <p class="fw-semibold mb-0">{{ $userPlan->plan->name }}</p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small mb-1">Billing</p>
                        <p class="fw-semibold mb-0">{{ ucfirst($userPlan->billing_cycle) }}</p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small mb-1">Started</p>
                        <p class="fw-semibold mb-0">{{ $userPlan->starts_at?->format('d M Y') ?? '—' }}</p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small mb-1">Expires</p>
                        <p class="fw-semibold mb-0">{{ $userPlan->ends_at?->format('d M Y') ?? 'No expiry' }}</p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small mb-1">Properties</p>
                        <p class="fw-semibold mb-0">
                            {{ $userPlan->plan->max_properties ? 'Up to ' . $userPlan->plan->max_properties : 'Unlimited' }}
                        </p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small mb-1">Staff</p>
                        <p class="fw-semibold mb-0">
                            {{ $userPlan->plan->max_staff ? 'Up to ' . $userPlan->plan->max_staff : 'Unlimited' }}
                        </p>
                    </div>
                </div>

                @if(!empty($userPlan->plan->features))
                <hr>
                <p class="text-muted small mb-2 fw-semibold">Included Features</p>
                <ul class="list-unstyled mb-0">
                    @foreach($userPlan->plan->features as $feature)
                    <li class="mb-1">
                        <i class="fas fa-check-circle text-success me-2"></i>{{ ucfirst($feature) }}
                    </li>
                    @endforeach
                </ul>
                @endif

            </div>
            <div class="card-footer text-center text-muted small">
                To upgrade or renew, contact us at
                <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
            </div>
        </div>

        @else

        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-crown fa-3x text-muted mb-3 d-block"></i>
                <h5 class="fw-bold mb-2">No Active Plan</h5>
                <p class="text-muted mb-4">
                    You don't have an active subscription yet.<br>
                    Please contact your administrator to get started.
                </p>
                <a href="mailto:{{ config('mail.from.address') }}?subject={{ urlencode('Plan Activation Request') }}"
                   class="btn btn-primary">
                    <i class="fas fa-envelope me-1"></i> Contact Admin
                </a>
            </div>
        </div>

        @endif

    </div>
</div>
@endsection
