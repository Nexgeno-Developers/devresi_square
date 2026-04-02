@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Payment for Invoice {{ $receipt->receiptable_type === 'sale_invoice' && $receipt->receiptable ? ($receipt->receiptable->invoice_no ?? $receipt->receiptable_id) : $receipt->receiptable_id }}</h4>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary">Print</button>
            <a href="{{ route($routeName . '.pdf', $receipt->id) }}" class="btn btn-outline-primary">Download PDF</a>
            <a href="{{ route($routeName . '.edit', $receipt->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route($routeName . '.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title mb-3">Payment Receipt</h5>
            <div class="row g-3 align-items-start">
                <div class="col-md-8">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Payment Date</dt>
                        <dd class="col-sm-9">{{ $receipt->receipt_date }}</dd>

                        <dt class="col-sm-3">Payment Mode</dt>
                        <dd class="col-sm-9">{{ optional($receipt->paymentMethod)->name ?? '-' }}</dd>

                        <dt class="col-sm-3">Customer</dt>
                        <dd class="col-sm-9">{{ $receipt->user->name ?? '-' }}</dd>

                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9"><span class="badge bg-info text-dark text-uppercase">{{ $receipt->status }}</span></dd>
                    </dl>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-light">
                        <div class="small text-muted">Total Amount</div>
                        <div class="fw-bold fs-5">£{{ number_format($receipt->amount, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3">Payment For</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice Number</th>
                            <th>Invoice Date</th>
                            <th class="text-end">Invoice Amount</th>
                            <th class="text-end">Payment Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $inv = ($receipt->receiptable_type === 'sale_invoice') ? $receipt->receiptable : null; @endphp
                        <tr>
                            <td>{{ $inv->invoice_no ?? '-' }}</td>
                            <td>{{ $inv->invoice_date ?? '-' }}</td>
                            <td class="text-end">{{ isset($inv->total_amount) ? number_format($inv->total_amount, 2) : '-' }}</td>
                            <td class="text-end">{{ number_format($receipt->amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($receipt->journal)
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Journal (ID: {{ $receipt->gl_journal_id }})</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Account</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receipt->journal->lines as $line)
                                <tr>
                                    <td>{{ $line->account->name ?? $line->gl_account_id }}</td>
                                    <td class="text-end">{{ number_format($line->debit, 2) }}</td>
                                    <td class="text-end">{{ number_format($line->credit, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
