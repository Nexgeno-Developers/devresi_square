@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('title', 'Rent · Resisquare')

@section('content')
<div class="tenant-portal">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Rent &amp; payments</p>
            <h1>What you owe</h1>
            <p>Pay an open invoice by card, or by bank transfer to your landlord. A small card fee may apply.</p>
        </div>
        <div class="tp-card" style="min-width: 200px;">
            <p class="tp-metric-label">Outstanding</p>
            <p class="tp-metric-value">£{{ number_format((float) $outstanding, 2) }}</p>
        </div>
    </div>

    @if($checkoutCancelled)
        <p class="tp-banner">Card checkout was cancelled. No payment was taken.</p>
    @endif

    @if($cardPaused ?? false)
        <p class="tp-banner" data-card-paused="1">Card payments are paused because this account is suspended or cancelled. Bank transfer is still available.</p>
    @endif

    <div class="tp-card">
        @if($invoices->isEmpty())
            <p class="tp-empty" data-empty-rent="1">Your landlord has not sent a rent invoice yet.</p>
        @else
            <div class="tp-rent-mobile">
                @foreach($invoices->groupBy(fn ($invoice) => (int) ($invoice->property_id ?: 0)) as $rows)
                    @php $groupAddress = $rows->first()->property?->line_1 ?: 'Rent'; @endphp
                    <h2 class="h5 mt-3" data-address-group="{{ $groupAddress }}">{{ $groupAddress }}</h2>
                @foreach($rows as $invoice)
                    @php
                        $open = $invoice->isOpen();
                        $fee = $fees[$invoice->id] ?? null;
                        $statusClass = match (true) {
                            $invoice->isOverdue() => 'is-overdue',
                            $invoice->status === \App\Models\RentInvoice::STATUS_PAID => 'is-paid',
                            $invoice->status === \App\Models\RentInvoice::STATUS_PARTIAL => 'is-partial',
                            $invoice->status === \App\Models\RentInvoice::STATUS_VOID => 'is-void',
                            default => 'is-unpaid',
                        };
                        $payTotal = (float) ($fee['total'] ?? $invoice->balance);
                    @endphp
                    <article class="tp-pay-card{{ $invoice->isOverdue() ? ' is-overdue' : '' }}" data-rent-home="{{ $invoice->property?->line_1 }}" data-invoice-id="{{ $invoice->id }}">
                        <div class="tp-pay-card__top">
                            <div>
                                <p class="tp-pay-card__title">{{ rs_rent_title($invoice) }}</p>
                                <p class="tp-pay-card__meta">Due {{ rs_date($invoice->due_date) }}</p>
                            </div>
                            <span class="tp-pill {{ $statusClass }}">{{ $invoice->statusLabel() }}</span>
                        </div>
                        <p class="tp-pay-card__amount">£{{ number_format((float) ($invoice->balance_amount ?? $invoice->total_amount ?? 0), 2) }}</p>
                        @if($open && $cardReady)
                            <form action="{{ route('tenant.rent.pay', $invoice) }}" method="POST">
                                @csrf
                                <button type="submit" class="tp-btn">Pay £{{ number_format($payTotal, 2) }}</button>
                            </form>
                            @if($fee && $fee['fee'] >= 0.01)
                                <p class="tp-muted mt-2 mb-0">£{{ number_format($fee['rent'], 2) }} rent + £{{ number_format($fee['fee'], 2) }} {{ $fee['fee_label'] }}</p>
                            @endif
                        @elseif($open && ! $cardReady)
                            <a href="{{ route('tenant.rent.show', $invoice) }}" class="tp-btn">Bank details</a>
                        @else
                            <a href="{{ route('tenant.rent.show', $invoice) }}" class="tp-btn tp-btn-ghost">View</a>
                        @endif
                    </article>
                @endforeach
                @endforeach
            </div>

            <table class="tp-table tp-rent-desktop">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Due</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices->groupBy(fn ($invoice) => (int) ($invoice->property_id ?: 0)) as $rows)
                        @php $groupAddress = $rows->first()->property?->line_1 ?: 'Rent'; @endphp
                        <tr>
                            <th colspan="7" data-address-group="{{ $groupAddress }}">{{ $groupAddress }}</th>
                        </tr>
                    @foreach($rows as $invoice)
                        @php
                            $open = $invoice->isOpen();
                            $fee = $fees[$invoice->id] ?? null;
                            $statusClass = match (true) {
                                $invoice->isOverdue() => 'is-overdue',
                                $invoice->status === \App\Models\RentInvoice::STATUS_PAID => 'is-paid',
                                $invoice->status === \App\Models\RentInvoice::STATUS_PARTIAL => 'is-partial',
                                $invoice->status === \App\Models\RentInvoice::STATUS_VOID => 'is-void',
                                default => 'is-unpaid',
                            };
                        @endphp
                        <tr class="{{ $invoice->isOverdue() ? 'tp-row-overdue' : '' }}" data-rent-home="{{ $invoice->property?->line_1 }}" data-invoice-id="{{ $invoice->id }}">
                            <td>
                                <a href="{{ route('tenant.rent.show', $invoice) }}" class="tp-link">
                                    {{ rs_rent_title($invoice) }}
                                </a>
                                <div class="small text-muted">{{ $invoice->invoice_no }}</div>
                                @if($invoice->tenant)
                                    <div class="small text-muted">Billed to {{ $invoice->tenant->name }}</div>
                                @endif
                            </td>
                            <td>{{ rs_date($invoice->invoice_date) }}</td>
                            <td>{{ rs_date($invoice->due_date) }}</td>
                            <td>£{{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</td>
                            <td>£{{ number_format((float) ($invoice->balance_amount ?? $invoice->total_amount ?? 0), 2) }}</td>
                            <td><span class="tp-pill {{ $statusClass }}">{{ $invoice->statusLabel() }}</span></td>
                            <td class="tp-table-action">
                                <a href="{{ route('tenant.rent.show', $invoice) }}" class="tp-btn tp-btn-ghost">View</a>
                                @if($open && $cardReady)
                                    <form action="{{ route('tenant.rent.pay', $invoice) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="tp-btn">Pay £{{ number_format((float) ($fee['total'] ?? $invoice->balance), 2) }}</button>
                                    </form>
                                    @if($fee && $fee['fee'] >= 0.01)
                                        <p class="tp-muted mt-1 mb-0">£{{ number_format($fee['rent'], 2) }} rent + £{{ number_format($fee['fee'], 2) }} {{ $fee['fee_label'] }}</p>
                                    @endif
                                @elseif($open && ! $cardReady)
                                    <a href="{{ route('tenant.rent.show', $invoice) }}" class="tp-btn tp-btn-ghost">Bank details</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if(! ($cardReady ?? false))
        @php
            $openForReference = $invoices->filter(fn ($row) => $row->isOpen());
        @endphp
        @include('backend.tenant.portal._bank-transfer', [
            'rentBank' => $rentBank ?? null,
            'invoice' => $openForReference->count() === 1 ? $openForReference->first() : null,
        ])
    @endif
</div>
@endsection
