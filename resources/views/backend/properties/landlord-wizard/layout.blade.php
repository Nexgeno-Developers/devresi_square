@extends('backend.layout.app')

@section('content')
    @php
        $currentStep = $step ?? 1;
        $stepLabels = $steps ?? config('landlord_mvp.wizard_steps', []);
        $progressPercent = count($stepLabels) > 0 ? (int) round(($currentStep / count($stepLabels)) * 100) : 0;
    @endphp

    <div class="lpw-page">
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-10">
                    <nav class="lpw-progress" aria-label="Property setup progress">
                        <div class="lpw-progress-meta">
                            <span>Property Passport setup</span>
                            <span>{{ $progressPercent }}% complete</span>
                        </div>
                        <div class="progress lpw-progress-bar" role="progressbar"
                            aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $progressPercent }}%"></div>
                        </div>
                        <ol class="lpw-progress-steps">
                            @foreach ($stepLabels as $num => $label)
                                @php
                                    $isComplete = $num < $currentStep;
                                    $isCurrent = $num === $currentStep;
                                @endphp
                                <li class="{{ $isCurrent ? 'is-current' : ($isComplete ? 'is-complete' : '') }}">
                                    <span class="lpw-progress-step-num">{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="lpw-progress-step-label">{{ $label }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </nav>

                    <div class="lpw-content">
                        @yield('wizard_content')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="{{ asset('asset/backend/css/property-address-lookup.css') }}" rel="stylesheet">
    <link href="{{ asset('asset/backend/css/landlord-property-wizard.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('asset/backend/js/property-address-lookup.js') }}"></script>
@endpush
