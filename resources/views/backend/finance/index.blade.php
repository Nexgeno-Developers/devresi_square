@extends('backend.layout.app')

@section('title', 'Finance · Resisquare')

@section('content')
<div class="container-fluid lw-page">
    <div class="lw-hero d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
            <h4>Finance</h4>
            <p>Rent invoices for this account. Tenants see these on their Rent page.</p>
        </div>
        @if($canCreate)
            <a href="{{ route('admin.finance.create') }}" class="btn lw-btn-secondary">New invoice</a>
        @endif
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
                'open' => 'Open',
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
                        @php
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
                            <td>
                                <a href="{{ route('admin.finance.show', $invoice) }}">{{ rs_rent_title($invoice) }}</a>
                                <div class="small text-muted">{{ $invoice->invoice_no }}</div>
                            </td>
                            <td>{{ rs_person($invoice->tenant) }}</td>
                            <td>{{ $invoice->property?->full_address ?: ($invoice->property?->line_1 ?: 'Property #'.$invoice->property_id) }}</td>
                            <td>{{ $invoice->issue_date?->format('d M Y') ?: '—' }}</td>
                            <td>{{ $invoice->due_date?->format('d M Y') ?: '—' }}</td>
                            <td>£{{ number_format((float) $invoice->amount, 2) }}</td>
                            <td>£{{ number_format((float) $invoice->balance, 2) }}</td>
                            <td><span class="badge bg-{{ $badge }}">{{ $label }}</td></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="lw-empty">
                                    @if($canCreate)
                                        <div class="lw-empty-title">No rent invoices yet</div>
                                        <p class="mb-3">Issue one for a tenancy that has a property and a tenant.</p>
                                        <a href="{{ route('admin.finance.create') }}" class="btn lw-btn-primary" data-next-action="new-invoice">New invoice</a>
                                    @else
                                        <div class="lw-empty-title">Nothing to invoice yet</div>
                                        <p class="mb-3">Add a tenancy on a property, then you can issue rent here.</p>
                                        <a href="{{ route('admin.tenancies.create') }}" class="btn lw-btn-primary" data-next-action="add-tenancy">Add tenancy</a>
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
