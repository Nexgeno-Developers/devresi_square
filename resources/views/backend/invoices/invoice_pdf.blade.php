@extends('backend.invoices.invoice_layout')

@section('title', 'Invoice - ' . $invoice->invoice_number)

@section('invoice_from')
    <address class="text-muted">
        <strong>{{get_setting('company_name') }}</strong><br>
        {{ get_setting('company_address') }}<br>
        {{ get_setting('company_email') }}<br>
        {{ get_setting('company_phone') }}
    </address>
@endsection
@section('invoice_to')
    <strong class="fw-bold">Bill To:</strong>
    <address class="text-muted">
        {!! get_user_address_name_by_id($invoice->user_id) !!}
    </address>
@endsection
@section('invoice_status')
    <span class="status-{{ strtolower($invoice->status->name) }}">
        {{ strtoupper($invoice->status->name) }}
    </span>
@endsection

@section('invoice_number', $invoice->invoice_number)
@section('invoice_date', formatDate($invoice->invoice_date))
@section('invoice_due_date', formatDate($invoice->due_date))
@section('total_amount', '£' . number_format($invoice->total_amount, 2))

@section('invoice_content')
    <table class="invoice-table">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">£{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">£{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('invoice_total')
<div style="padding:0 1.5rem;">
    <table class="text-right sm-padding small strong">
        <thead>
            <tr>
                <th width="60%"></th>
                <th width="40%"></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">
                    
                </td>
                <td>
                    <table class="text-right sm-padding small strong">
                        <tbody>
                            <tr>
                                <th class="gry-color text-left">Sub Total</th>
                                <td class="currency">£{{ number_format($invoice->subtotal, 2) }}</td>
                            </tr>
                            <tr class="border-bottom">
                                <th class="gry-color text-left">Total Tax</th>
                                <td class="currency">£{{ number_format($invoice->tax_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th class="text-left strong">Grand Total</th>
                                <td class="currency">£{{ number_format($invoice->total_amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection

@section('additional_invoice_info')
@if($invoice->notes)
    <p><strong>Notes:</strong> {{ $invoice->notes ?? '' }}</p>
@endif
@endsection

@section('footer_text', 'All invoices must be paid within 30 days. Thank you for your business!')
