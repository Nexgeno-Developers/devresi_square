@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Rent &amp; payments</p>
            <h1>What you owe</h1>
            <p>Pay an open invoice by card, or by bank transfer to your landlord. A small card fee may apply.</p>
        </div>
        <div class="tp-card" style="min-width: 200px;">
            <p class="tp-metric-label">Outstanding</p>
            <p class="tp-metric-value">£{{ number_format((float) $outstanding, 2) }}</p>
        </div>
    </div>

    @if($checkoutCancelled)
        <p class="tp-banner">Card checkout was cancelled. No payment was taken.</p>
    @endif

    <div class="tp-card">
        @if($invoices->isEmpty())
            <p class="tp-empty">There are no rent invoices on your account yet.</p>
        @else
            <table class="tp-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Due</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        @php
                            $open = $invoice->isOpen();
                            $fee = $fees[$invoice->id] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $invoice->invoice_no ?: '#'.$invoice->id }}</td>
                            <td>{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') : '—' }}</td>
                            <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : '—' }}</td>
                            <td>£{{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</td>
                            <td>£{{ number_format((float) ($invoice->balance_amount ?? $invoice->total_amount ?? 0), 2) }}</td>
                            <td><span class="tp-pill">{{ ucfirst((string) ($invoice->status ?: 'issued')) }}</span></td>
                            <td class="tp-table-action">
                                @if($open && $cardReady)
                                    <form action="{{ route('tenant.rent.pay', $invoice) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="tp-btn">Pay £{{ number_format((float) ($fee['total'] ?? $invoice->balance), 2) }}</button>
                                        @if($fee && $fee['fee'] >= 0.01)
                                            <p class="tp-muted mt-1 mb-0">£{{ number_format($fee['rent'], 2) }} rent + £{{ number_format($fee['fee'], 2) }} {{ $fee['fee_label'] }}</p>
                                        @endif
                                    </form>
                                @elseif($open && ! $cardReady)
                                    <span class="tp-muted">Pay by bank transfer</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
