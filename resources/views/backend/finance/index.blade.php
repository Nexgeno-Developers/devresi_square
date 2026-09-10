@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page">
    <div class="lw-hero d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
            <h4>Finance</h4>
            <p>Rent invoices for this account. Tenants see these on their Rent page.</p>
        </div>
        @if($canCreate)
            <a href="{{ route('admin.finance.create') }}" class="btn btn-light">New invoice</a>
        @endif
    </div>

    @if(! $canCreate && ($orphanTenancies ?? collect())->isNotEmpty())
        <div class="alert alert-lw mb-3">
            A tenancy is missing its property, so rent cannot be issued yet.
            @foreach($orphanTenancies as $orphan)
                <a class="alert-link" href="{{ route('admin.tenancies.edit', $orphan->id) }}">Link tenancy #{{ $orphan->id }} to a property</a>@if(! $loop->last), @endif
            @endforeach
        </div>
    @endif

    <div class="card lw-card">
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
                            <td colspan="8">
                                <div class="lw-empty">
                                    @if($canCreate)
                                        <div class="lw-empty-title">No rent invoices yet</div>
                                        <p class="mb-3">Issue one for a tenancy that has a property and a tenant.</p>
                                        <a href="{{ route('admin.finance.create') }}" class="btn lw-btn-primary">New invoice</a>
                                    @else
                                        <div class="lw-empty-title">Nothing to invoice yet</div>
                                        <p class="mb-3">Add a tenancy on a property, then you can issue rent here.</p>
                                        <a href="{{ route('admin.tenancies.create') }}" class="btn lw-btn-primary">Add tenancy</a>
                                    @endif
                                </div>
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
