@extends('backend.layout.app')

@section('content')
<div class="container lw-page">
    <div class="lw-hero d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
        <div>
            <h4>{{ $invoice->invoice_no }}</h4>
            <p>{{ ucfirst($invoice->status) }} · balance £{{ number_format((float) $invoice->balance, 2) }}</p>
        </div>
        <a href="{{ route('admin.finance.index') }}" class="btn btn-light">Back</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-2"><strong>Tenant</strong><br>{{ $invoice->tenant?->name ?: $invoice->tenant?->email }}</div>
                <div class="col-md-6 mb-2"><strong>Property</strong><br>{{ $invoice->property?->full_address ?: ($invoice->property?->line_1 ?: '—') }}</div>
                <div class="col-md-3 mb-2"><strong>Issued</strong><br>{{ $invoice->issue_date?->format('d M Y') }}</div>
                <div class="col-md-3 mb-2"><strong>Due</strong><br>{{ $invoice->due_date?->format('d M Y') }}</div>
                <div class="col-md-3 mb-2"><strong>Amount</strong><br>£{{ number_format((float) $invoice->amount, 2) }}</div>
                <div class="col-md-3 mb-2"><strong>Balance</strong><br>£{{ number_format((float) $invoice->balance, 2) }}</div>
                @if($invoice->period_start || $invoice->period_end)
                    <div class="col-12 mb-2"><strong>Period</strong><br>{{ $invoice->period_start?->format('d M Y') ?: '—' }} – {{ $invoice->period_end?->format('d M Y') ?: '—' }}</div>
                @endif
                @if($invoice->note)
                    <div class="col-12"><strong>Note</strong><br>{{ $invoice->note }}</div>
                @endif
            </div>
        </div>
    </div>

    @if($invoice->isOpen())
        <div class="card mb-3">
            <div class="card-body">
                <h5>Record payment</h5>
                <form action="{{ route('admin.finance.payments.store', $invoice) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="amount">Amount (£)</label>
                            <input type="number" step="0.01" min="0.01" max="{{ $invoice->balance }}" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $invoice->balance) }}" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="paid_at">Paid on</label>
                            <input type="date" name="paid_at" id="paid_at" class="form-control @error('paid_at') is-invalid @enderror" value="{{ old('paid_at', now()->toDateString()) }}" required>
                            @error('paid_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="method">Method</label>
                            <select name="method" id="method" class="form-control @error('method') is-invalid @enderror" required>
                                @foreach($manualMethods as $value => $label)
                                    <option value="{{ $value }}" @selected(old('method', 'bank_transfer') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="reference">Reference</label>
                            <input type="text" name="reference" id="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}" maxlength="120">
                            @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <button type="submit" class="btn lw-btn-primary">Save payment</button>
                </form>
            </div>
        </div>
    @endif

    @if($invoice->canVoid())
        <form action="{{ route('admin.finance.void', $invoice) }}" method="POST" class="mb-3" onsubmit="return confirm('Void this unpaid invoice?');">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">Void invoice</button>
        </form>
    @endif

    <div class="card">
        <div class="card-body">
            <h5>Payments</h5>
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Fee</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at?->format('d M Y') }}</td>
                            <td>£{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ (float) $payment->fee_amount > 0 ? '£'.number_format((float) $payment->fee_amount, 2) : '—' }}</td>
                            <td>{{ $methods[$payment->method] ?? ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                            <td>{{ $payment->reference ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted">No payments recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
