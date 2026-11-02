@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page">
    @include('backend.contractors._nav')

    <p class="small text-muted">Send the contractor’s share from the operating account to the bank details on the invoice, then record the reference. Recording it does not send the money.</p>

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Waiting to send ({{ $waiting->count() }})</div>
            @forelse($waiting as $payment)
                @php
                    $invoice = $payment->invoice;
                    $bank = is_array($invoice?->payment) ? $invoice->payment : [];
                    $offer = $invoice?->offer;
                    $name = $offer?->contractor?->contractorProfile?->displayName() ?: 'Contractor';
                @endphp
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between flex-wrap gap-3">
                        <div>
                            <strong>{{ $invoice?->number ?: 'Invoice' }}</strong>
                            · {{ $name }}
                            · {{ $offer?->repairIssue?->reference_number ?: 'Repair' }}
                            <div class="small mt-1">Send <strong>£{{ number_format((float) $payment->contractor_amount, 2) }}</strong>. Commission kept £{{ number_format((float) $payment->commission_amount, 2) }}. Landlord paid £{{ number_format((float) $payment->landlord_amount, 2) }}.</div>
                            @if(filled($bank['account_number'] ?? null))
                                <div class="small text-muted">{{ $bank['account_name'] ?? '' }}@if(filled($bank['bank_name'] ?? null)), {{ $bank['bank_name'] }}@endif · Sort code {{ $bank['sort_code'] ?? '' }} · Account {{ $bank['account_number'] }} · Reference {{ $bank['reference'] ?? $invoice?->number }}</div>
                            @else
                                <div class="small text-danger">No bank details were saved on this invoice.</div>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.registrations.contractors.payouts.sent', $payment) }}" class="d-flex flex-wrap gap-2 align-items-start">
                            @csrf
                            <input name="payout_reference" value="{{ old('payout_reference') }}" required maxlength="64" class="form-control form-control-sm" placeholder="Bank reference" style="max-width:180px">
                            <button class="btn btn-primary btn-sm" type="submit" onclick="return confirm('Record this transfer? This does not send the money.')">Mark transfer sent</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="small text-muted mb-0">None. Card payments show here after a landlord pays.</p>
            @endforelse
        </div>
    </div>

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Recorded</div>
            @forelse($sent as $payment)
                @php
                    $invoice = $payment->invoice;
                    $name = $invoice?->offer?->contractor?->contractorProfile?->displayName() ?: 'Contractor';
                @endphp
                <div class="small border-bottom py-2">
                    {{ $invoice?->number ?: 'Invoice' }} · {{ $name }} · £{{ number_format((float) $payment->contractor_amount, 2) }}
                    · {{ $payment->payout_reference }}
                    · {{ $payment->payout_paid_at?->format('j M Y') }}
                </div>
            @empty
                <p class="small text-muted mb-0">None yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
