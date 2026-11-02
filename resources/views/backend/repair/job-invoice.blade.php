@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3" style="max-width: 820px;">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-2 flex-wrap no-print">
        <a href="{{ route('admin.property_repairs.show', $offer->repair_issue_id) }}#contractors" class="small">← Repair</a>
        <span class="d-flex gap-2 align-items-center">
            @if($invoice->status === 'issued')
                @if($cardPaymentsReady)
                    <form method="POST" action="{{ route('admin.property_repairs.job_offers.invoice.pay', $offer) }}">@csrf
                        <button class="btn btn-primary btn-sm" type="submit">Pay £{{ number_format($charge['landlord_amount'], 2) }}</button>
                    </form>
                @else
                    <span class="small text-muted">Card payments are not available yet.</span>
                @endif
            @endif
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print or save as PDF</button>
        </span>
    </div>
    @if(session('success')) <div class="alert alert-success no-print">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger no-print">{{ session('error') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger no-print">{{ $errors->first() }}</div> @endif
    @if(request('checkout') === 'cancelled') <div class="alert alert-warning no-print">No payment was taken.</div> @endif
    @if($invoice->status === 'issued' && $cardPaymentsReady)
        <div class="small text-muted no-print mb-2">
            Job total £{{ number_format($charge['gross'], 2) }}.
            @if($charge['bearer'] === 'landlord' && $charge['commission'] >= 0.01)
                Resisquare commission ({{ rtrim(rtrim(number_format($charge['percent'], 2), '0'), '.') }}%) £{{ number_format($charge['commission'], 2) }}.
                You pay £{{ number_format($charge['landlord_amount'], 2) }}.
            @endif
            Resisquare takes the card payment and pays the contractor.
        </div>
    @elseif($invoice->cardPayment && $invoice->cardPayment->isPaid())
        <p class="small no-print mb-2">Charged £{{ number_format((float) $invoice->cardPayment->landlord_amount, 2) }}. Resisquare pays the contractor.</p>
    @endif
    @include('backend.repair.partials.job-invoice-document')
</div>
@endsection
