@extends('backend.layout.app')

@php
    $property = $tenancy->property;
    $mainMember = $tenancy->tenantMembers->firstWhere('is_main_person', true) ?: $tenancy->tenantMembers->first();
    $tenantNames = $tenancy->tenantMembers
        ->pluck('user.name')
        ->filter()
        ->unique()
        ->implode(', ');
    $money = fn ($amount) => '£'.number_format((float) $amount, 2);
@endphp

@section('content')
<div class="container-fluid pt-4 pb-5 lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1">Rent ledger</h4>
            <p class="mb-0">
                {{ $property?->short_title ?? ($property?->line_1 ?? 'Property') }}
                @if($property?->short_address)
                    <span class="text-muted">· {{ $property->short_address }}</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.tenancies.show', $tenancy->id) }}" class="btn lw-btn-secondary">Back to tenancy</a>
            <a href="{{ route('admin.finance.create') }}" class="btn lw-btn-primary">New invoice</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card lw-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Agreed rent</div>
                    <div class="h5 mb-0">{{ $money($tenancy->rent) }}</div>
                    <div class="text-muted small">{{ $tenancy->frequency ?: 'No frequency set' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card lw-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Invoiced</div>
                    <div class="h5 mb-0">{{ $money($summary['total_invoiced']) }}</div>
                    <div class="text-muted small">{{ $summary['invoice_count'] }} invoice{{ $summary['invoice_count'] == 1 ? '' : 's' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card lw-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Paid</div>
                    <div class="h5 mb-0">{{ $money($summary['total_paid']) }}</div>
                    <div class="text-muted small">Latest: {{ $summary['latest_payment_date'] ? \Illuminate\Support\Carbon::parse($summary['latest_payment_date'])->format('d M Y') : '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card lw-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Balance</div>
                    <div class="h5 mb-0">{{ $money($summary['balance']) }}</div>
                    <span class="badge bg-{{ $summary['status_class'] }}">{{ $summary['status'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card lw-card mb-3">
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
                    <div class="text-muted small">Move in</div>
                    <div>{{ $tenancy->move_in?->format('d M Y') ?: '—' }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Move out</div>
                    <div>{{ $tenancy->move_out?->format('d M Y') ?: '—' }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Auto invoice</div>
                    <div>{{ $tenancy->rent_auto_invoice ? 'On' : 'Off' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card lw-card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Rent invoices</h5>

            @if($invoiceRows->isEmpty())
                <div class="lw-empty pt-2">
                    <div class="lw-empty-title">No rent invoices yet</div>
                    <p class="mb-3">Issue rent from Finance for this tenancy.</p>
                    <a href="{{ route('admin.finance.create') }}" class="btn lw-btn-primary">New invoice</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table lw-table align-middle">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Issued</th>
                                <th>Due</th>
                                <th>Tenant</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoiceRows as $row)
                                @php
                                    $invoice = $row['invoice'];
                                    $label = $invoice->statusLabel();
                                    $badge = match ($label) {
                                        'Overdue' => 'danger',
                                        'Paid' => 'success',
                                        'Part paid' => 'warning',
                                        'Void' => 'secondary',
                                        default => 'primary',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $invoice->invoice_no }}</td>
                                    <td>{{ $invoice->issue_date?->format('d M Y') ?: '—' }}</td>
                                    <td>{{ rs_date($invoice->due_date) }}</td>
                                    <td>{{ $invoice->tenant?->name ?: $invoice->tenant?->email ?: '—' }}</td>
                                    <td class="text-end">{{ $money($invoice->amount) }}</td>
                                    <td class="text-end">{{ $money($row['paid']) }}</td>
                                    <td class="text-end">{{ $money($row['balance']) }}</td>
                                    <td><span class="badge bg-{{ $badge }}">{{ $label }}</span></td>
                                    <td>
                                        <a href="{{ route('admin.finance.show', $invoice) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card lw-card">
        <div class="card-body">
            <h5 class="mb-3">Payments</h5>

            @if($payments->isEmpty())
                <p class="text-muted mb-0">No payments recorded against this tenancy’s invoices yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table lw-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Method</th>
                                <th class="text-end">Amount</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $row)
                                @php
                                    $invoice = $row['invoice'];
                                    $payment = $row['payment'];
                                @endphp
                                <tr>
                                    <td>{{ $payment->paid_at?->format('d M Y') ?: '—' }}</td>
                                    <td>
                                        <a href="{{ route('admin.finance.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                                    </td>
                                    <td>{{ \App\Models\RentPayment::METHODS[$payment->method] ?? ucfirst((string) $payment->method) }}</td>
                                    <td class="text-end">{{ $money($payment->amount) }}</td>
                                    <td>{{ $payment->reference ?: '—' }}</td>
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
