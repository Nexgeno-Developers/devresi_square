@extends('backend.layout.app')

@push('styles')
<style>
    /* Buttons and the back link are real tap targets on phones and tablets. */
    @media (max-width: 991px) {
        body #wrapper .contractor-compliance-page .btn-sm,
        body #wrapper .contractor-compliance-page > a,
        body #wrapper .contractor-compliance-page > p > a,
        body #wrapper .contractor-compliance-page > div > a:only-child {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 contractor-compliance-page" style="max-width: 820px;">
    <div class="d-flex justify-content-between align-items-center mb-2 no-print">
        <a href="{{ route('contractor.jobs.compliance.show', $offer) }}" class="small">← Job</a>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print or save as PDF</button>
    </div>
    @if(session('success')) <div class="alert alert-success no-print">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger no-print">{{ session('error') }}</div> @endif
    @include('backend.repair.partials.job-invoice-document', ['showContractorBank' => true, 'jobLabel' => $jobLabel])
</div>
@endsection
