<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->invoice_no }}</title>
    <style media="all">
        @page {
            margin: 0;
            padding: 0;
        }
        body {
            font-size: 12px;
            font-weight: normal;
            font-family: Arial, sans-serif;
            direction: ltr;
            text-align: left;
            padding: 0;
            margin: 0;
        }
        .gry-color *, .gry-color { color: #000; }
        table { width: 100%; }
        table th { font-weight: normal; }
        table.padding th { padding: .25rem .7rem; }
        table.padding td { padding: .25rem .7rem; }
        table.sm-padding td { padding: .1rem .7rem; }
        .border-bottom td, .border-bottom th { border-bottom: 1px solid #c38127; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-muted { color: #595c5f !important; }
        .text-center { text-align: center; }
        .strong { font-weight: bold; }
        .small { font-size: 11px; }
        .hr { background: #c38127; border: .5px solid #c38127 !important; }
        .table-dark th { background-color: #000; color: white; }
        .status-paid { color: green; font-weight: bold; }
        .status-partial { color: orange; font-weight: bold; }
        .status-issued, .status-pending { color: orange; font-weight: bold; }
        .status-overdue { color: red; font-weight: bold; }
        .status-cancelled, .status-cancel { color: gray; font-weight: bold; text-decoration: line-through; }
        .status-drafted, .status-draft { color: blue; font-weight: bold; }
        .invoice-container { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #ddd; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .footer { margin-top: 20px; text-align: center; font-size: 12px; color: gray; }
    </style>
</head>
<body>

@php $status = strtolower($invoice->status ?? 'draft'); @endphp

<div class="invoice-container">
    <div style="background: #eceff4; padding: 1.5rem;">
        <table>
            <tr>
                <td>
                    <img loading="lazy" src="https://laravel.resisquare.co.uk/asset/images/resisquare-logo.svg" height="40" style="display:inline-block;">
                </td>
                <td class="text-right">
                    <h1>Invoice</h1>
                    <p># {{ $invoice->invoice_no }}</p>
                    <p>
                        <span class="status-{{ $status }}">
                            {{ strtoupper($status) }}
                        </span>
                    </p>
                </td>
            </tr>
        </table>
        <div style="margin-top: 3.2rem;"></div>
        <table>
            <tr>
                <td style="font-size: 1.2rem;" class="strong">
                    <address class="text-muted">
                        <strong>{{ get_setting('company_name') ?: 'Resisquare' }}</strong><br>
                        {{ get_setting('contact_address') ?: 'UK, London' }}<br>
                        {{ get_setting('contact_email') ?: '' }}<br>
                        {{ get_setting('contact_phone') ?: '' }}
                    </address>
                </td>
                <td style="font-size: 1.2rem;" class="text-right strong">
                    <strong>Bill To:</strong>
                    <address class="text-muted">
                        {{ optional($customer)->name ?? 'N/A' }}<br>
                        {{ optional($customer)->email ?? '' }}
                    </address>
                </td>
            </tr>
            <tr>
                <td class="gry-color small"></td>
                <td class="text-right small" style="padding-top: 2rem;">
                    <span class="gry-color small">Invoice Date:</span>
                    <span class="strong">{{ $invoice->invoice_date }}</span>
                </td>
            </tr>
            <tr>
                <td class="gry-color small"></td>
                <td class="text-right small">
                    <span class="gry-color small">Invoice Due Date:</span>
                    <span class="strong">{{ $invoice->due_date ?? '-' }}</span>
                </td>
            </tr>
            <tr>
                <td class="gry-color small"></td>
                <td class="text-right small">
                    <span class="gry-color small">Invoice Header:</span>
                    <span class="strong">{{ $invoice->invoiceHeader?->header_name ?? '-' }}</span>
                </td>
            </tr>
        </table>
    </div>

    @if($invoice->invoiceHeader)
        <div style="padding: 0 1.5rem 12px 1.5rem;">
            <p style="margin: 0 0 4px;"><strong>Header Reference:</strong> {{ $invoice->invoiceHeader->unique_reference_number }}</p>
            @if($invoice->invoiceHeader->header_description)
                <p style="margin: 0;"><strong>Header Description:</strong> {{ $invoice->invoiceHeader->header_description }}</p>
            @endif
        </div>
    @endif

    <div style="padding: 0 1.5rem;">
        <table class="invoice-table">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Discount</th>
                    <th class="text-right">Tax</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $idx => $row)
                    @php
                        $lineBase = max(0, ($row->quantity * $row->rate) - ($row->discount ?? 0));
                        $lineTotal = $lineBase + ($row->tax_amount ?? 0);
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $row->item_name }}</td>
                        <td>{{ $row->description }}</td>
                        <td class="text-right">{{ number_format($row->quantity, 2) }}</td>
                        <td class="text-right">£{{ number_format($row->rate, 2) }}</td>
                        <td class="text-right">£{{ number_format($row->discount ?? 0, 2) }}</td>
                        <td class="text-right">£{{ number_format($row->tax_amount ?? 0, 2) }}</td>
                        <td class="text-right">£{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="padding: 0 1.5rem;">
        <table class="text-right sm-padding small strong">
            <thead>
                <tr>
                    <th width="60%"></th>
                    <th width="40%"></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
                    <td>
                        <table class="text-right sm-padding small strong">
                            <tbody>
                                <tr>
                                    <th class="gry-color text-left">Sub Total</th>
                                    <td>£{{ number_format($subtotal, 2) }}</td>
                                </tr>
                                <tr class="border-bottom">
                                    <th class="gry-color text-left">Total Tax</th>
                                    <td>£{{ number_format($taxTotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <th class="text-left strong">Grand Total</th>
                                    <td>£{{ number_format($total, 2) }}</td>
                                </tr>
                                <tr>
                                    <th class="gry-color text-left">Paid</th>
                                    <td>£{{ number_format($paid, 2) }}</td>
                                </tr>
                                <tr class="border-bottom">
                                    <th class="text-left strong">Balance</th>
                                    <td>£{{ number_format($balance, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($invoice->notes)
        <div style="padding: 0 1.5rem; margin-top: 10px;">
            <p><strong>Notes:</strong> {{ $invoice->notes }}</p>
        </div>
    @endif

    @if($invoice->payments->count())
        <div style="padding: 0 1.5rem; margin-top: 16px;">
            <h4 style="margin: 0 0 6px;">Payments</h4>
            <table class="invoice-table">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Bank</th>
                        <th class="text-right">Amount</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $pidx => $pay)
                        <tr>
                            <td>{{ $pidx + 1 }}</td>
                            <td>{{ $pay->payment_date }}</td>
                            <td>{{ optional($pay->paymentMethod)->name ?? '-' }}</td>
                            <td>{{ optional($pay->bankAccount)->account_name ?? '-' }}</td>
                            <td class="text-right">£{{ number_format($pay->amount, 2) }}</td>
                            <td>{{ $pay->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">
        All invoices must be paid within 30 days. Thank you for your business!
    </div>

    <div style="margin-top: 100px;"></div>

    <div class="hr"></div>

    <div>
        <table class="text-right small strong">
            <tbody>
                <tr>
                    <td></td>
                    <td>
                        <table class="text-center small" style="line-height: 23px; font-size: 12px;">
                            <tbody>
                                <tr>
                                    <td class="text-center" width="100%"><b>Terms -</b> The bill is system generated physical sign is not required.</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="hr"></div>
</div>

</body>
</html>
