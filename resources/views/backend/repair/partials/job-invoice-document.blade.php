@php
    /** @var \App\Models\RepairJobInvoice $invoice */
    $property = $offer->repairIssue?->property;
    $address = collect([$property?->line_1, $property?->line_2, $property?->city, $property?->postcode])->filter()->implode(', ');
    $from = collect([$profile?->address_line, $profile?->city, $profile?->postcode])->filter()->implode(', ');
    $tone = $invoice->status === 'paid' ? 'text-bg-success' : ($invoice->status === 'void' ? 'text-bg-secondary' : ($invoice->isOverdue() ? 'text-bg-danger' : 'text-bg-warning'));
@endphp
<style>
    @media print {
        body * { visibility: hidden; }
        .job-invoice, .job-invoice * { visibility: visible; }
        .job-invoice { position: absolute; inset: 0; border: 0 !important; box-shadow: none !important; }
        .job-invoice .no-print { display: none !important; }
    }
</style>
<div class="card job-invoice"><div class="card-body p-4">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4 flex-wrap">
        <div>
            <div class="text-muted small text-uppercase">{{ $invoice->vat_number ? 'VAT invoice' : 'Invoice' }}</div>
            <h1 class="h4 m-0">{{ $invoice->number }}</h1>
            <span class="badge {{ $tone }} mt-1">{{ $invoice->statusLabel() }}</span>
        </div>
        <div class="text-end small">
            <strong>{{ $profile?->displayName() ?: 'Contractor' }}</strong>
            @if($profile?->trading_name && $profile?->legal_name && $profile->trading_name !== $profile->legal_name)
                <div class="text-muted">{{ $profile->legal_name }}</div>
            @endif
            @if($from)<div>{{ $from }}</div>@endif
            @if($profile?->company_number)<div>Company no. {{ $profile->company_number }}</div>@endif
            @if($invoice->vat_number)<div>VAT no. {{ $invoice->vat_number }}</div>@endif
        </div>
    </div>

    <div class="row small mb-4 g-3">
        <div class="col-sm-6">
            <div class="text-muted">Job</div>
            <div>{{ $offer->repairIssue?->reference_number ?: 'Repair #'.$offer->repair_issue_id }}</div>
            @if($address)<div>{{ $address }}</div>@endif
        </div>
        <div class="col-sm-3">
            <div class="text-muted">Issued</div>
            <div>{{ rs_date($invoice->issued_on) }}</div>
        </div>
        <div class="col-sm-3">
            <div class="text-muted">Due date</div>
            <div class="{{ $invoice->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ rs_date($invoice->due_on) }}</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Description</th><th class="text-end">Net</th>@if($invoice->vat_number)<th class="text-end">VAT</th>@endif<th class="text-end">Total</th></tr></thead>
            <tbody>
                @foreach($invoice->lines ?? [] as $line)
                    <tr>
                        <td>{{ $line['description'] ?? '' }}</td>
                        <td class="text-end">£{{ number_format((float) ($line['net'] ?? 0), 2) }}</td>
                        @if($invoice->vat_number)<td class="text-end">£{{ number_format((float) ($line['vat'] ?? 0), 2) }} <span class="text-muted">({{ rtrim(rtrim(number_format((float) ($line['vat_rate'] ?? 0), 2), '0'), '.') }}%)</span></td>@endif
                        <td class="text-end">£{{ number_format((float) ($line['gross'] ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @if($invoice->vat_number)
                    <tr><th colspan="3" class="text-end fw-normal">Net</th><td class="text-end">£{{ number_format((float) $invoice->net_amount, 2) }}</td></tr>
                    <tr><th colspan="3" class="text-end fw-normal">VAT</th><td class="text-end">£{{ number_format((float) $invoice->vat_amount, 2) }}</td></tr>
                @endif
                <tr><th colspan="{{ $invoice->vat_number ? 3 : 2 }}" class="text-end">Amount due</th><td class="text-end fw-semibold fs-5">£{{ number_format((float) $invoice->gross_amount, 2) }}</td></tr>
            </tfoot>
        </table>
    </div>

    @if(! $invoice->vat_number)
        <p class="small text-muted mb-2">The contractor is not VAT registered, so no VAT is charged.</p>
    @endif
    @if($invoice->cis_status && $invoice->cis_status !== 'not_registered')
        <p class="small text-muted mb-2">Construction Industry Scheme: the contractor is {{ str_replace('_', ' ', $invoice->cis_status) }}. If you are a CIS contractor, deductions may apply.</p>
    @endif
    @if($invoice->notes)
        <div class="small border-top pt-2" style="white-space: pre-line">{{ $invoice->notes }}</div>
    @endif
    @if($invoice->paid_at)
        <p class="small text-success mt-2 mb-0">Paid on {{ rs_date($invoice->paid_at) }}.</p>
    @endif
    <p class="small text-muted mt-3 mb-0">Issued through Resisquare. Payment is made directly between the landlord and the contractor.</p>
</div></div>
