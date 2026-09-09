@extends('backend.layout.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1">Finance</h4>
            <div class="text-muted">Rent invoices for this account. Tenants see these on their Rent page.</div>
        </div>
        @if($canCreate)
            <a href="{{ route('admin.finance.create') }}" class="btn btn-outline-danger btn-sm">New invoice</a>
        @endif
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Tenant</th>
                        <th>Property</th>
                        <th>Issued</th>
                        <th>Due</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>
                                <a href="{{ route('admin.finance.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                            </td>
                            <td>{{ $invoice->tenant?->name ?: $invoice->tenant?->email ?: 'Tenant #'.$invoice->tenant_user_id }}</td>
                            <td>{{ $invoice->property?->full_address ?: ($invoice->property?->line_1 ?: 'Property #'.$invoice->property_id) }}</td>
                            <td>{{ $invoice->issue_date?->format('d M Y') ?: '—' }}</td>
                            <td>{{ $invoice->due_date?->format('d M Y') ?: '—' }}</td>
                            <td>£{{ number_format((float) $invoice->amount, 2) }}</td>
                            <td>£{{ number_format((float) $invoice->balance, 2) }}</td>
                            <td>{{ ucfirst($invoice->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                @if($canCreate)
                                    No rent invoices yet. Issue one for a tenancy.
                                @else
                                    Add a tenancy on a property and invite a tenant before issuing rent.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection
