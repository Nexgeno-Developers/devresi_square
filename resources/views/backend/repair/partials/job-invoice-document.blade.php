@php
    /** @var \App\Models\RepairJobInvoice $invoice */
    $sheet = $invoice->documentContext($profile ?? null, $offer ?? null);
    $supplier = $sheet['supplier'];
    $billTo = $sheet['bill_to'];
    $site = $sheet['site'];
    $payment = $sheet['payment'];
    $supplierName = $supplier['name'] ?? 'Contractor';
    $legalName = $supplier['legal_name'] ?? null;
    $showLegal = filled($legalName) && $legalName !== $supplierName;
    $billName = $billTo['name'] ?? null;
    $billContact = $billTo['contact'] ?? null;
    $showContact = filled($billContact) && $billContact !== $billName;
    $statusLine = $invoice->status === 'paid' && $invoice->paid_at
        ? 'Paid on '.rs_date($invoice->paid_at)
        : $invoice->statusLabel();
@endphp
<style>
    .job-invoice {
        background: #fff;
        color: #1c1c1c;
        max-width: 800px;
        margin: 0 auto;
        padding: 36px 40px 28px;
        border: 1px solid #d9d9d9;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 14px;
        line-height: 1.45;
    }
    .job-invoice h1 { font-size: 28px; letter-spacing: 0.02em; margin: 0; font-weight: 600; }
    .job-invoice .kicker { margin: 0 0 4px; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: #555; }
    .job-invoice .who { display: flex; justify-content: space-between; gap: 32px; align-items: flex-start; }
    .job-invoice .supplier { text-align: right; }
    .job-invoice .parties { display: flex; gap: 32px; margin: 28px 0 8px; }
    .job-invoice .parties > div { flex: 1; }
    .job-invoice .label { font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: #666; margin-bottom: 4px; }
    .job-invoice .dates { margin-left: auto; min-width: 180px; }
    .job-invoice .dates div { display: flex; justify-content: space-between; gap: 16px; }
    .job-invoice table { width: 100%; border-collapse: collapse; margin-top: 22px; }
    .job-invoice th { text-align: left; font-weight: 600; font-size: 12px; letter-spacing: 0.04em; text-transform: uppercase; border-bottom: 1px solid #1c1c1c; padding: 6px 8px; }
    .job-invoice td { border-bottom: 1px solid #ececec; padding: 8px; vertical-align: top; }
    .job-invoice .num { text-align: right; white-space: nowrap; }
    .job-invoice tfoot td { border-bottom: 0; padding-top: 4px; }
    .job-invoice .due td { font-size: 16px; font-weight: 600; padding-top: 8px; }
    .job-invoice .pay, .job-invoice .note { margin-top: 22px; }
    .job-invoice .pay p, .job-invoice .note p { margin: 0; }
    .job-invoice .status { margin-top: 18px; }
    .job-invoice .foot { margin: 28px 0 0; font-size: 12px; color: #666; }
    @media print {
        body * { visibility: hidden; }
        .job-invoice, .job-invoice * { visibility: visible; }
        .job-invoice { position: absolute; inset: 0; border: 0; max-width: none; }
        .no-print { display: none !important; }
    }
</style>
<article class="job-invoice">
    <div class="who">
        <div>
            <p class="kicker">{{ filled($invoice->vat_number) ? 'VAT invoice' : 'Invoice' }}</p>
            <h1>{{ $invoice->number }}</h1>
        </div>
        <div class="supplier">
            <strong>{{ $supplierName }}</strong>
            @if($showLegal)<div>{{ $legalName }}</div>@endif
            @if(filled($supplier['address'] ?? null))<div>{{ $supplier['address'] }}</div>@endif
            @if(filled($supplier['phone'] ?? null))<div>{{ $supplier['phone'] }}</div>@endif
            @if(filled($supplier['email'] ?? null))<div>{{ $supplier['email'] }}</div>@endif
            @if(filled($supplier['company_number'] ?? null))<div>Company no. {{ $supplier['company_number'] }}</div>@endif
            @if(filled($invoice->vat_number))<div>VAT no. {{ $invoice->vat_number }}</div>@endif
        </div>
    </div>

    <div class="parties">
        <div>
            @if(filled($billName))
                <div class="label">Bill to</div>
                <div>{{ $billName }}</div>
                @if($showContact)<div>{{ $billContact }}</div>@endif
                @if(filled($billTo['address'] ?? null))<div>{{ $billTo['address'] }}</div>@endif
                @if(filled($billTo['email'] ?? null))<div>{{ $billTo['email'] }}</div>@endif
            @endif
            @if(filled($site['address'] ?? null))
                <div class="label" @if(filled($billName)) style="margin-top:14px" @endif>Site</div>
                <div>{{ $site['address'] }}</div>
            @endif
        </div>
        <div class="dates">
            <div><span>Issued</span><span>{{ rs_date($invoice->issued_on) }}</span></div>
            @if($invoice->supplied_on)
                <div><span>Date of supply</span><span>{{ rs_date($invoice->supplied_on) }}</span></div>
            @endif
            <div><span>Due</span><span>{{ rs_date($invoice->due_on) }}</span></div>
            <div><span>Job</span><span>{{ $offer->repairIssue?->reference_number ?: 'Repair #'.$offer->repair_issue_id }}</span></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Net</th>
                @if($invoice->vat_number)<th class="num">VAT</th>@endif
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines ?? [] as $line)
                <tr>
                    <td>{{ $line['description'] ?? '' }}</td>
                    <td class="num">£{{ number_format((float) ($line['net'] ?? 0), 2) }}</td>
                    @if($invoice->vat_number)
                        <td class="num">£{{ number_format((float) ($line['vat'] ?? 0), 2) }} ({{ rtrim(rtrim(number_format((float) ($line['vat_rate'] ?? 0), 2), '0'), '.') }}%)</td>
                    @endif
                    <td class="num">£{{ number_format((float) ($line['gross'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if($invoice->vat_number)
                <tr>
                    <td class="num" colspan="3">Net</td>
                    <td class="num">£{{ number_format((float) $invoice->net_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="num" colspan="3">VAT</td>
                    <td class="num">£{{ number_format((float) $invoice->vat_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="due">
                <td class="num" colspan="{{ $invoice->vat_number ? 3 : 2 }}">Amount due</td>
                <td class="num">£{{ number_format((float) $invoice->gross_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($payment !== [])
        <div class="pay">
            <div class="label">How to pay</div>
            <p>Account name {{ $payment['account_name'] ?? '' }}</p>
            @if(filled($payment['bank_name'] ?? null))<p>{{ $payment['bank_name'] }}</p>@endif
            <p>Sort code {{ $payment['sort_code'] ?? '' }}</p>
            <p>Account number {{ $payment['account_number'] ?? '' }}</p>
            <p>Reference {{ $payment['reference'] ?? $invoice->number }}</p>
        </div>
    @endif

    @if($invoice->notes)
        <div class="note">
            <div class="label">Message</div>
            <p style="white-space: pre-line">{{ $invoice->notes }}</p>
        </div>
    @endif

    <p class="status">{{ $statusLine }}</p>
    <p class="foot">Issued for the contractor through ResiSquare.</p>
</article>
