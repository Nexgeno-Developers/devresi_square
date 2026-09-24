@php
    $name = auth()->user()->first_name ?: auth()->user()->name;
    $propertyLabel = $activeTenancy?->property?->full_address
        ?: ($activeTenancy?->property?->prop_name ?: 'No property linked yet');
@endphp
@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Tenant portal</p>
            <h1>Welcome, {{ $name }}</h1>
            <p>Your tenancy, rent and repairs in one place.</p>
        </div>
        @if($activeTenancy)
            <a class="tp-btn" href="{{ route('tenant.tenancy') }}" data-home-tenancy="1">View my tenancy</a>
        @endif
    </div>

    <div class="tp-metrics">
        <div class="tp-card">
            <p class="tp-metric-label">Home</p>
            <p class="tp-metric-value">{{ $propertyLabel }}</p>
            @if($activeTenancy)
                <p class="tp-muted mt-2 mb-0">{{ $activeTenancy->status }} tenancy</p>
            @endif
        </div>
        <div class="tp-card">
            <p class="tp-metric-label">Outstanding rent</p>
            <p class="tp-metric-value">£{{ number_format((float) $outstanding, 2) }}</p>
            <p class="tp-muted mt-2 mb-0">From invoices on your tenancy</p>
            @if($outstanding > 0)
                <a class="tp-btn mt-3" href="{{ route('tenant.rent') }}">Pay rent</a>
            @endif
        </div>
        <div class="tp-card">
            <p class="tp-metric-label">Open repairs</p>
            <p class="tp-metric-value" data-open-repairs="{{ $openRepairCount }}">{{ $openRepairCount }}</p>
            <p class="tp-muted mt-2 mb-0">Reported against your property</p>
        </div>
    </div>

    @foreach($tenancies as $tenancy)
        @php
            $groupAddress = $tenancy->property?->line_1 ?: ($tenancy->property?->full_address ?: 'Home');
            $groupInvoices = $recentInvoices->where('tenancy_id', $tenancy->id);
            $groupRepairs = $recentRepairs->where('property_id', $tenancy->property_id);
        @endphp
        <section class="tp-card mb-3" data-address-group="{{ $groupAddress }}">
            <h2>{{ $groupAddress }}</h2>
            @foreach($groupInvoices as $invoice)
                <p class="mb-1" data-rent-home="{{ $groupAddress }}">{{ $invoice->invoice_no ?: 'Invoice #'.$invoice->id }} · £{{ number_format((float) ($invoice->balance_amount ?? $invoice->total_amount ?? 0), 2) }}</p>
            @endforeach
            @foreach($groupRepairs as $repair)
                <p class="mb-1" data-repair-home="{{ $groupAddress }}">{{ $repair->description ? \Illuminate\Support\Str::limit($repair->description, 80) : 'Repair #'.$repair->id }}</p>
            @endforeach
            @if($groupInvoices->isEmpty() && $groupRepairs->isEmpty())
                <p class="tp-muted mb-0">Nothing outstanding for this home.</p>
            @endif
        </section>
    @endforeach

    <div class="tp-grid">
        <div class="tp-card">
            <h2>Recent rent invoices</h2>
            @forelse($recentInvoices as $invoice)
                <div class="tp-row">
                    <div>
                        <p>{{ $invoice->invoice_no ?: 'Invoice #'.$invoice->id }}</p>
                        <p class="tp-muted">{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') : 'No date' }}@if($invoice->tenant) · Billed to {{ $invoice->tenant->name }}@endif</p>
                    </div>
                    <div class="text-end">
                        <p>£{{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</p>
                        <span class="tp-pill">{{ ucfirst((string) ($invoice->status ?: 'issued')) }}</span>
                    </div>
                </div>
            @empty
                <p class="tp-empty">Your landlord has not sent a rent invoice yet.</p>
            @endforelse
            <div class="mt-3">
                <a class="tp-btn tp-btn-ghost" href="{{ route('tenant.rent') }}">Rent &amp; payments</a>
            </div>
        </div>
        <div class="tp-card">
            <h2>Maintenance</h2>
            @forelse($recentRepairs as $repair)
                <div class="tp-row">
                    <div>
                        <p>{{ $repair->description ? \Illuminate\Support\Str::limit($repair->description, 80) : 'Repair #'.$repair->id }}</p>
                        <p class="tp-muted">{{ $repair->property?->prop_name ?: ($repair->property?->full_address ?: 'Property') }}</p>
                    </div>
                    <span class="tp-pill">{{ $repair->status ?: 'Open' }}</span>
                </div>
            @empty
                <p class="tp-empty">No repair requests yet.</p>
            @endforelse
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <a class="tp-btn tp-btn-ghost" href="{{ route('tenant.maintenance') }}">View repairs</a>
                <a class="tp-btn" href="{{ route('tenant.maintenance') }}">Report an issue</a>
            </div>
        </div>
        <div class="tp-card">
            <h2>Upcoming appointments</h2>
            @forelse($upcomingEvents as $event)
                <a class="tp-row" href="{{ route('tenant.calendar', ['month' => $event->start_datetime?->format('Y-m')]) }}" data-upcoming-event="{{ $event->id }}" data-home-visit="1">
                    <div>
                        <p>{{ $event->title }}</p>
                        <p class="tp-muted">{{ $event->start_datetime?->format('d M Y, H:i') }}</p>
                    </div>
                    <span class="tp-pill">{{ $event->status ?: 'Scheduled' }}</span>
                </a>
            @empty
                <p class="tp-empty">No appointments yet. Inspections and move-in dates will appear here.</p>
            @endforelse
            <div class="mt-3">
                <a class="tp-btn tp-btn-ghost" href="{{ route('tenant.calendar') }}">View calendar</a>
            </div>
        </div>
    </div>
</div>
@endsection
