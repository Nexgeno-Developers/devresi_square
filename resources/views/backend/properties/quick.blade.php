<!-- resources/views/backend/properties/quick.blade.php -->
@extends('backend.layout.app')

@section('content')
    @php
        $stepNames = [
            1 => 'Property Address',
            2 => 'Property Type',
            3 => 'Property Information',
            4 => 'Current Status',
            5 => 'Features',
            6 => 'Price',
            7 => 'Valid EPC',
            8 => 'Media',
        ];
        $currentStep = isset($property) && isset($property->quick_step) ? (int) $property->quick_step + 1 : 1;
        $progressPercent = round(($currentStep / count($stepNames)) * 100);
    @endphp
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0">Add New Property</h5>
                                <p class="text-muted small mb-0">Step {{ min($currentStep, count($stepNames)) }} of {{ count($stepNames) }}: {{ $stepNames[min($currentStep, count($stepNames))] ?? '' }}</p>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="progress" style="width: 200px; height: 8px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $progressPercent }}%;" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="small text-muted">{{ $progressPercent }}%</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($stepNames as $step => $name)
                                    @php
                                        $stepNum = (int) $step;
                                        $isComplete = $stepNum < $currentStep;
                                        $isCurrent = $stepNum === $currentStep;
                                        $isPending = $stepNum > $currentStep;
                                    @endphp
                                    <div class="step-indicator d-flex align-items-center gap-1 {{ $isCurrent ? 'text-primary fw-semibold' : ($isComplete ? 'text-success' : 'text-muted') }}">
                                        @if($isComplete)
                                            <i class="bi bi-check-circle-fill"></i>
                                        @else
                                            <span class="step-number-badge">{{ $stepNum }}</span>
                                        @endif
                                        <span class="d-none d-sm-inline small">{{ $name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('backend.properties.quick_form_components.form',['countries' => $countries])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .step-indicator {
        opacity: {{ $currentStep >= 1 ? '1' : '0.5' }};
    }
    .step-number-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        font-size: 0.75rem;
        font-weight: 600;
        border: 2px solid currentColor;
    }
    .step-indicator.text-primary .step-number-badge {
        background-color: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
    }
</style>
@endpush
