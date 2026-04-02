<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 12px; }
        h2 { margin-bottom: 6px; }
        h4 { margin: 10px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #dee2e6; padding: 6px; }
        th { background: #f8f9fa; text-align: left; }
        .grid { display: table; width: 100%; }
        .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 8px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; background: #e7f1ff; color: #0b5ed7; font-size: 11px; }
        .pill { border: 1px solid #dee2e6; border-radius: 6px; padding: 8px 10px; background: #f8f9fa; }
    </style>
</head>
<body>
    <h2>Payment Receipt</h2>
    <div class="grid">
        <div class="col">
            <table>
                <tr><th width="35%">Payment Date</th><td>{{ $receipt->receipt_date }}</td></tr>
                <tr><th>Payment Mode</th><td>{{ optional($receipt->paymentMethod)->name ?? '-' }}</td></tr>
                <tr><th>Customer</th><td>{{ $receipt->user->name ?? '-' }}</td></tr>
                <tr><th>Status</th><td><span class="badge">{{ $receipt->status }}</span></td></tr>
            </table>
        </div>
        <div class="col">
            <div class="pill">
                <div style="font-size:11px;color:#6c757d;">Total Amount</div>
                <div style="font-weight:bold;font-size:16px;">£{{ number_format($receipt->amount, 2) }}</div>
            </div>
        </div>
    </div>

    <h4>Payment For</h4>
    @php $inv = ($receipt->receiptable_type === 'sale_invoice') ? $receipt->receiptable : null; @endphp
    <table>
        <thead>
            <tr>
                <th>Invoice Number</th>
                <th>Invoice Date</th>
                <th style=\"text-align:right\">Invoice Amount</th>
                <th style=\"text-align:right\">Payment Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $inv->invoice_no ?? '-' }}</td>
                <td>{{ $inv->invoice_date ?? '-' }}</td>
                <td style=\"text-align:right\">{{ isset($inv->total_amount) ? number_format($inv->total_amount, 2) : '-' }}</td>
                <td style=\"text-align:right\">{{ number_format($receipt->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if($receipt->journal && $receipt->journal->lines->count())
        <h4>Journal (ID: {{ $receipt->gl_journal_id }})</h4>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th width="20%" style="text-align:right">Debit</th>
                    <th width="20%" style="text-align:right">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->journal->lines as $line)
                    <tr>
                        <td>{{ $line->account->name ?? $line->gl_account_id }}</td>
                        <td style="text-align:right">{{ number_format($line->debit, 2) }}</td>
                        <td style="text-align:right">{{ number_format($line->credit, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
