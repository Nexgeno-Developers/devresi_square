@extends('backend.layout.app')

@php
    $property = $tenancy->property;
    $mainMember = $tenancy->tenantMembers->firstWhere('is_main_person', true) ?: $tenancy->tenantMembers->first();
    $tenantNames = $tenancy->tenantMembers
        ->pluck('user.name')
        ->filter()
        ->unique()
        ->implode(', ');
    $money = fn ($amount) => number_format((float) $amount, 2);
@endphp

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1">Rent Ledger - Tenancy #{{ $tenancy->id }}</h2>
            <div class="text-muted">
                {{ $property->prop_ref_no ?? '' }}
                {{ $property->full_address ?? 'Property not available' }}
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($property)
                <a href="{{ route('admin.properties.index', ['property_id' => $property->id, 'tabname' => 'Tenancy']) }}" class="btn btn-outline-secondary">
                    Back to Tenancy
                </a>
            @endif
            <a href="{{ route('backend.accounting.sale.invoices.create') }}" class="btn btn-primary">
                Create Rent Invoice
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Agreed Rent</div>
                    <div class="h5 mb-0">{{ $money($tenancy->rent) }}</div>
                    <div class="text-muted small">{{ $tenancy->frequency ?: 'No frequency set' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Invoiced</div>
                    <div class="h5 mb-0">{{ $money($summary['total_invoiced']) }}</div>
                    <div class="text-muted small">{{ $summary['invoice_count'] }} invoice{{ $summary['invoice_count'] == 1 ? '' : 's' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Paid</div>
                    <div class="h5 mb-0">{{ $money($summary['total_paid']) }}</div>
                    <div class="text-muted small">Latest: {{ $summary['latest_payment_date'] ?: '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Balance</div>
                    <div class="h5 mb-0">{{ $money($summary['balance']) }}</div>
                    <span class="badge bg-{{ $summary['status_class'] }}">{{ $summary['status'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Tenants</div>
                    <div>{{ $tenantNames ?: 'No tenant members' }}</div>
                    @if($mainMember?->user)
                        <div class="text-muted small">Main: {{ $mainMember->user->name }}</div>
                    @endif
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Status</div>
                    <div>{{ $tenancy->status }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Sub Status</div>
                    <div>{{ $tenancy->tenancySubStatus->name ?? '-' }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Move In</div>
                    <div>{{ $tenancy->move_in ?: '-' }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Move Out</div>
                    <div>{{ $tenancy->move_out ?: '-' }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Deposit</div>
                    <div>{{ $money($tenancy->deposit) }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Type</div>
                    <div>{{ $tenancy->tenancyType->name ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Term</div>
                    <div>{{ $tenancy->term_months ?? 0 }} months, {{ $tenancy->term_days ?? 0 }} days</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Rent Invoices For This Tenancy Record</h5>
                <span class="text-muted small">Includes direct tenancy invoices and property-linked invoices charged to this tenancy's tenants.</span>
            </div>

            @if($invoiceRows->isEmpty())
                <div class="alert alert-info mb-0">No rent invoices are linked to this tenancy record yet.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Due</th>
                                <th>Charged To</th>
                                <th>Source</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoiceRows as $row)
                                @php
                                    $invoice = $row['invoice'];
                                    $invoiceStatusClass = match ($invoice->status) {
                                        'paid' => 'success',
                                        'partial' => 'warning',
                                        'cancelled' => 'secondary',
                                        'draft' => 'info',
                                        default => 'danger',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $invoice->invoice_no }}</td>
                                    <td>{{ $invoice->invoice_date ?: '-' }}</td>
                                    <td>{{ $invoice->due_date ?: '-' }}</td>
                                    <td>{{ $invoice->user->name ?? $invoice->chargeTo->name ?? '-' }}</td>
                                    <td>{{ $row['source'] }}</td>
                                    <td class="text-end">{{ $money($invoice->total_amount) }}</td>
                                    <td class="text-end">{{ $money($row['paid']) }}</td>
                                    <td class="text-end">{{ $money($row['balance']) }}</td>
                                    <td><span class="badge bg-{{ $invoiceStatusClass }}">{{ ucfirst($invoice->status ?? 'draft') }}</span></td>
                                    <td>
                                        <a href="{{ route('backend.accounting.sale.invoices.show', $invoice->id) }}" class="btn btn-sm btn-outline-info">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-2">Payment Records</h5>

            @if($payments->isEmpty())
                <div class="alert alert-info mb-0">No payments have been recorded against this tenancy's invoices.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Method</th>
                                <th>Bank</th>
                                <th class="text-end">Amount</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $row)
                                @php
                                    $invoice = $row['invoice'];
                                    $payment = $row['payment'];
                                @endphp
                                <tr>
                                    <td>{{ $payment->payment_date ?: '-' }}</td>
                                    <td>
                                        <a href="{{ route('backend.accounting.sale.invoices.show', $invoice->id) }}">
                                            {{ $invoice->invoice_no }}
                                        </a>
                                    </td>
                                    <td>{{ $payment->paymentMethod->name ?? '-' }}</td>
                                    <td>{{ $payment->bankAccount->account_name ?? '-' }}</td>
                                    <td class="text-end">{{ $money($payment->amount) }}</td>
                                    <td>{{ $payment->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
