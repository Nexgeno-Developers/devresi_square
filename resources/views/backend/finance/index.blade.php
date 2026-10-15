@extends('backend.layout.app')

@section('title', 'Rent · Resisquare')

@section('content')
<div class="container-fluid lw-page">
    <div class="lw-hero d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
            <h4>Rent</h4>
            <p>Track rent due, received and outstanding across your portfolio.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(! empty($openInvoice))
                <a href="{{ route('admin.finance.show', $openInvoice) }}" class="btn lw-btn-secondary">Record Payment</a>
            @endif
            @if($canCreate)
                <a href="{{ route('admin.finance.create') }}" class="btn lw-btn-primary">Create Charge</a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach(['due' => 'Due', 'received' => 'Received', 'outstanding' => 'Outstanding', 'unallocated' => 'Unallocated'] as $key => $label)
            <div class="col-6 col-lg-3">
                <div class="card lw-card h-100"><div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fw-semibold">{{ rs_money($summary[$key] ?? 0) }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    @if(($overdueCount ?? 0) > 0)
        <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
            <div>
                <strong data-overdue-count="{{ $overdueCount }}">{{ $overdueCount }} overdue rent {{ \Illuminate\Support\Str::plural('invoice', $overdueCount) }}</strong>
                <div class="small mb-0">Past due date with a balance still outstanding.</div>
            </div>
            <a href="{{ route('admin.finance.index', ['status' => 'overdue']) }}" class="btn btn-sm btn-dark">Show overdue</a>
        </div>
    @endif

    <div class="card lw-card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <strong>How tenants pay</strong>
                <div class="small text-muted mb-0">
                    Card: {{ ($cardRentReady ?? false) ? 'ready' : 'off' }}
                    · Bank details: {{ ($rentBank ?? null) ? 'set' : 'missing' }}
                </div>
            </div>
            <a href="{{ route('admin.finance.rent-pay') }}" class="btn btn-sm btn-outline-dark">Rent pay settings</a>
        </div>
    </div>

    @if(! ($cardRentReady ?? false) && ! ($rentBank ?? null))
        <div class="alert alert-danger mb-3">
            Card rent is off and no bank details are saved. Tenants will hit a dead end on Pay.
            <a href="{{ route('admin.finance.rent-pay') }}" class="alert-link">Add bank transfer details</a>
        </div>
    @endif

    @if(! $canCreate && ($orphanTenancies ?? collect())->isNotEmpty())
        <div class="alert alert-lw mb-3">
            A tenancy is missing its property, so rent cannot be issued yet.
            @foreach($orphanTenancies as $orphan)
                <a class="alert-link" href="{{ route('admin.tenancies.edit', $orphan->id) }}">Link tenancy #{{ $orphan->id }} to a property</a>@if(! $loop->last), @endif
            @endforeach
        </div>
    @endif

    <div class="d-flex gap-2 flex-wrap mb-3">
        @php
            $filters = [
                'all' => 'All',
                'open' => 'Due',
                'part' => 'Part Paid',
                'overdue' => 'Overdue',
                'paid' => 'Paid',
            ];
        @endphp
        @foreach($filters as $key => $label)
            <a href="{{ route('admin.finance.index', ['status' => $key]) }}"
               class="btn btn-sm {{ ($filter ?? 'all') === $key ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card lw-card">
        <div class="card-body table-responsive">
            <table class="table lw-table align-middle">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Tenant</th>
                        <th>Period</th>
                        <th>Due</th>
                        <th>Received</th>
                        <th>Balance</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        @php
                            $label = $invoice->statusLabel();
                            $badge = match ($label) {
                                'Overdue' => 'danger',
                                'Paid' => 'success',
                                'Part Paid' => 'warning',
                                'Void' => 'secondary',
                                default => 'primary',
                            };
                            $received = max(0, (float) $invoice->amount - (float) $invoice->balance);
                            $method = optional($invoice->payments->sortByDesc('id')->first())->method;
                            $methodLabel = \App\Models\RentPayment::METHODS[$method] ?? '—';
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.finance.show', $invoice) }}">{{ $invoice->property?->line_1 ?: ($invoice->property?->full_address ?: 'Property') }}</a>
                            </td>
                            <td>{{ rs_person($invoice->tenant) }}</td>
                            <td>{{ $invoice->issue_date?->format('F Y') ?: '—' }}</td>
                            <td>{{ rs_money($invoice->amount) }}</td>
                            <td>{{ rs_money($received) }}</td>
                            <td>{{ rs_money($invoice->balance) }}</td>
                            <td>{{ $methodLabel }}</td>
                            <td><span class="badge bg-{{ $badge }}">{{ $label }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="lw-empty">
                                    <div class="lw-empty-title">Rent Is Up to Date</div>
                                    <p class="mb-3">There are no overdue rent balances across your portfolio.</p>
                                    @if($canCreate)
                                        <a href="{{ route('admin.finance.create') }}" class="btn lw-btn-primary" data-next-action="new-invoice">Create Charge</a>
                                    @else
                                        <a href="{{ route('admin.tenancies.create') }}" class="btn lw-btn-primary" data-next-action="add-tenancy">Add Tenancy</a>
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
