@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
@php
    $statusClass = match (true) {
        $invoice->isOverdue() => 'is-overdue',
        $invoice->status === \App\Models\RentInvoice::STATUS_PAID => 'is-paid',
        $invoice->status === \App\Models\RentInvoice::STATUS_PARTIAL => 'is-partial',
        $invoice->status === \App\Models\RentInvoice::STATUS_VOID => 'is-void',
        default => 'is-unpaid',
    };
@endphp
<div class="tenant-portal{{ !empty($print) ? ' tp-print' : '' }}">
    <div class="tp-hero tp-no-print">
        <div>
            <p class="tp-kicker">Rent invoice</p>
            <h1>{{ $invoice->invoice_no ?: 'Invoice #'.$invoice->id }}</h1>
            <p>View only — ask your landlord if anything looks wrong.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('tenant.rent') }}" class="tp-btn tp-btn-ghost">Back to rent</a>
            <a href="{{ route('tenant.rent.show', ['rentInvoice' => $invoice, 'print' => 1]) }}" class="tp-btn tp-btn-ghost" target="_blank" rel="noopener">Print</a>
        </div>
    </div>

    <div class="tp-card tp-invoice-sheet">
        <div class="tp-invoice-head">
            <div>
                <p class="tp-kicker mb-1">Invoice</p>
                <h2 class="mb-0">{{ $invoice->invoice_no ?: '#'.$invoice->id }}</h2>
            </div>
            <span class="tp-pill {{ $statusClass }}">{{ $invoice->statusLabel() }}</span>
        </div>

        <div class="tp-invoice-grid">
            <div>
                <p class="tp-muted mb-1">Property</p>
                <p class="mb-0">{{ $invoice->property?->full_address ?: ($invoice->property?->line_1 ?: '—') }}</p>
            </div>
            <div>
                <p class="tp-muted mb-1">Issued</p>
                <p class="mb-0">{{ $invoice->issue_date?->format('d M Y') ?: '—' }}</p>
            </div>
            <div>
                <p class="tp-muted mb-1">Due</p>
                <p class="mb-0">{{ $invoice->due_date?->format('d M Y') ?: '—' }}</p>
            </div>
            <div>
                <p class="tp-muted mb-1">Period</p>
                <p class="mb-0">
                    {{ $invoice->period_start?->format('d M Y') ?: '—' }}
                    –
                    {{ $invoice->period_end?->format('d M Y') ?: '—' }}
                </p>
            </div>
            <div>
                <p class="tp-muted mb-1">Total</p>
                <p class="mb-0">£{{ number_format((float) $invoice->amount, 2) }}</p>
            </div>
            <div>
                <p class="tp-muted mb-1">Balance</p>
                <p class="mb-0">£{{ number_format((float) $invoice->balance, 2) }}</p>
            </div>
        </div>

        @if($invoice->note)
            <div class="mt-3">
                <p class="tp-muted mb-1">Note</p>
                <p class="mb-0">{{ $invoice->note }}</p>
            </div>
        @endif

        @if($invoice->isOpen())
            <div class="tp-no-print mt-4">
                @if($cardReady)
                    <form action="{{ route('tenant.rent.pay', $invoice) }}" method="POST">
                        @csrf
                        <button type="submit" class="tp-btn">Pay £{{ number_format((float) ($fee['total'] ?? $invoice->balance), 2) }}</button>
                        @if($fee && $fee['fee'] >= 0.01)
                            <p class="tp-muted mt-2 mb-0">£{{ number_format($fee['rent'], 2) }} rent + £{{ number_format($fee['fee'], 2) }} {{ $fee['fee_label'] }}</p>
                        @endif
                    </form>
                @else
                    <p class="tp-muted mb-0">Pay by bank transfer using the details your landlord gave you.</p>
                @endif
            </div>
        @endif
    </div>

    <div class="tp-card mt-3">
        <h3 class="h5 mb-3">Payments</h3>
        @if($invoice->payments->isEmpty())
            <p class="tp-empty mb-0">No payments recorded yet.</p>
        @else
            <table class="tp-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at?->format('d M Y') ?: '—' }}</td>
                            <td>£{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $payment->method)) }}</td>
                            <td>{{ $payment->reference ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@if(!empty($print))
    @push('scripts')
    <script>window.addEventListener('load', function () { window.print(); });</script>
    @endpush
@endif
@endsection
