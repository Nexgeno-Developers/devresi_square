@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Rent &amp; payments</p>
            <h1>What you owe</h1>
            <p>Invoices billed to you for your tenancy. Internal landlord accounts are not shown.</p>
        </div>
        <div class="tp-card" style="min-width: 200px;">
            <p class="tp-metric-label">Outstanding</p>
            <p class="tp-metric-value">£{{ number_format((float) $outstanding, 2) }}</p>
        </div>
    </div>

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
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_no ?: '#'.$invoice->id }}</td>
                            <td>{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') : '—' }}</td>
                            <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : '—' }}</td>
                            <td>£{{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</td>
                            <td>£{{ number_format((float) ($invoice->balance_amount ?? $invoice->total_amount ?? 0), 2) }}</td>
                            <td><span class="tp-pill">{{ $invoice->status ?: 'Issued' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
