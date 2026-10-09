@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3" style="max-width: 820px;">
    <div class="d-flex justify-content-between align-items-center mb-2 no-print">
        <a href="{{ route('contractor.jobs.show', $offer) }}" class="small">← Job</a>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print or save as PDF</button>
    </div>
    @if(session('success')) <div class="alert alert-success no-print">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger no-print">{{ session('error') }}</div> @endif
    @include('backend.repair.partials.job-invoice-document')
</div>
@endsection
