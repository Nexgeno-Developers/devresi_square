@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3" style="max-width: 820px;">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-2 flex-wrap">
        <a href="{{ route('admin.property_repairs.show', $offer->repair_issue_id) }}#contractors" class="small">← Repair</a>
        <span class="d-flex gap-2">
            @if($invoice->status === 'issued')
                <form method="POST" action="{{ route('admin.property_repairs.job_offers.invoice.paid', $offer) }}">@csrf
                    <button class="btn btn-primary btn-sm" type="submit" onclick="return confirm('Mark this invoice as paid? The contractor will be told.')">Mark as paid</button>
                </form>
            @endif
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print or save as PDF</button>
        </span>
    </div>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @include('backend.repair.partials.job-invoice-document')
</div>
@endsection
