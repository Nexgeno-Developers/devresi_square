@extends('backend.layout.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-end gap-3 mt-3">
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">
            <i class="me-2 fa-solid fa-arrow-left"></i> Back
        </a>
        {{-- <button class="btn btn-primary" onclick="window.print();">
            <i class="me-2 fa-solid fa-print"></i> Print
        </button> --}}
        
        <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="btn btn-warning">
            <i class="me-2 fa-solid fa-pen-to-square"></i> Edit
        </a>

        <a href="{{ route('admin.invoices.download', $invoice->id) }}" class="btn btn_secondary">
            <i class="me-2 fa-regular fa-file-pdf"></i> Download PDF
        </a>        
    </div>

        
    {{-- <img class="img-fluid" width="250" src="{{ uploaded_asset(get_setting('header_logo')) }}" alt="Resisquare logo"> --}}
    {{-- <div class="top shadow-sm bg-white py-3 mb-4">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <div class="mb-4">
                    <a href="{{ url('/') }}" class="logo">
                        <img class="img-fluid" width="250" src="{{ asset('asset/images/resisquare-logo.svg') }}" alt="Resisquare logo">
                    </a>
                </div>
                <span class="badge {{ getInvoiceStatusBadge($invoice->status_id) }}">
                    {{ getInvoiceStatusText($invoice->status_id) }}
                </span>
            </div>
        </div>
    </div> --}}

    <!-- Invoice Card -->
    <div class="card shadow-lg invoice-card invoice-card-print p-2 my-4">
        <div class="card-body">
            <div class="row">
                <!-- Company Info -->
                <div class="col-md-6">
                    <h4 class="fw-bold">Invoice #</span> {{ $invoice->invoice_number }}</h4>
                    {{-- <span class="mb-2 badge {{ getInvoiceStatusBadge($invoice->status_id) }}">
                        {{ getInvoiceStatusText($invoice->status_id) }}
                    </span> --}}
                    <span class="mb-2 badge {{ getInvoiceStatusDetails($invoice->status_id)['badge'] }}">
                        {{ getInvoiceStatusDetails($invoice->status_id)['text'] }}
                    </span>
                    <address class="text-muted">
                        <strong>{{get_setting('company_name') }}</strong><br>
                        {{ get_setting('company_address') }}<br>
                        {{ get_setting('company_email') }}<br>
                        {{ get_setting('company_phone') }}
                    </address>
                    
                </div>

                <!-- Client Info -->
                <div class="col-md-6 text-md-end">
                    <strong class="fw-bold">To</strong>
                    <address class="text-muted">
                        {!! get_user_address_name_by_id($invoice->user_id) !!}
                    </address>

                    <!-- Invoice Dates -->
                    <p class="mb-1"><strong>Invoice Date:</strong> {{ formatDate($invoice->invoice_date) }}</p>
                    <p class="mb-1"><strong>Due Date:</strong> {{ formatDate($invoice->due_date) }}</p>
                </div>


            <!-- Invoice Items Table -->
            <div class="table-responsive mt-4">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Description</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Tax</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->description }}</td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">£{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end">0%</td>
                            <td class="text-end">£{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Invoice Summary -->
            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold">Notes</h5>
                    <p class="text-muted">{{ $invoice->notes ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-end"><strong>Subtotal:</strong></td>
                            <td class="text-end">£{{ number_format($invoice->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-end"><strong>Tax:</strong></td>
                            <td class="text-end">£{{ number_format($invoice->tax_amount, 2) }}</td>
                        </tr>
                        <tr class="table-light">
                            <td class="text-end"><strong>Total:</strong></td>
                            <td class="text-end fs-5 fw-bold">£{{ number_format($invoice->total_amount, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Payments / Transactions linked to this Invoice -->
            <div class="mt-4">
                <h5 class="fw-bold">Payments</h5>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Paid:</strong> £{{ number_format($paid ?? $invoice->paidAmount(), 2) }}</p>
                        <p><strong>Outstanding:</strong> £{{ number_format($outstanding ?? $invoice->outstandingAmount(), 2) }}</p>
                    </div>
                    @if(($outstanding ?? $invoice->outstandingAmount()) > 0)
                    <div class="col-md-6 text-md-end">
                        <a href="{{ route('backend.transactions.create') }}?invoice_id={{ $invoice->id }}" class="btn btn-primary">Add Payment</a>
                    </div>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Txn Number</th>
                                <th>Date</th>
                                <th>Method / Bank</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Status</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->payments as $i => $payment)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $payment->transaction_number ?? '-' }}</td>
                                    <td>{{ formatDate($payment->transaction_date ?? $payment->date) }}</td>
                                    <td>
                                        {{ optional($payment->paymentMethod)->name ?? '' }}
                                        @if($payment->bankAccount) <br> <small>{{ $payment->bankAccount->account_name }}</small> @endif
                                    </td>
                                    <td class="text-end">{{ getPoundSymbol() }}{{ number_format($payment->amount, 2) }}</td>
                                    <td class="text-center">{{ ucfirst($payment->status) }}</td>
                                    <td>{{ Str::limit($payment->notes ?? '-', 80) }}</td>
                                    <td>
                                        <a href="{{ route('backend.transactions.show', $payment) }}" class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center">No payments recorded for this invoice.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


            <!-- Terms & Conditions -->
            <div class="mt-4">
                <hr>
                <h5 class="fw-bold">Terms & Conditions</h5>
                <p class="text-muted">All invoices must be paid within 30 days from the invoice date.</p>
            </div>
        </div>
    </div>
</div>

@endsection
