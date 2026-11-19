@extends('backend.layout.app')

@push('styles')
<style>
    /* Back link and Print button are comfortable tap targets on phones and tablets. */
    @media (max-width: 991px) {
        body #wrapper .contractor-invoice-bar a,
        body #wrapper .contractor-invoice-bar .btn { display: inline-flex; align-items: center; min-height: 40px; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3" style="max-width: 820px;">
    <div class="d-flex justify-content-between align-items-center mb-2 no-print contractor-invoice-bar">
        <a href="{{ route('contractor.jobs.show', $offer) }}" class="small">← Job</a>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print or save as PDF</button>
    </div>
    @if(session('success')) <div class="alert alert-success no-print">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger no-print">{{ session('error') }}</div> @endif
    @include('backend.repair.partials.job-invoice-document', ['showContractorBank' => true])
</div>
@endsection
