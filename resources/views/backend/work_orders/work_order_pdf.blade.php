@extends('backend.work_orders.workorder_pdf_layout')

@section('title', 'Work Order')
@section('workorder_title', 'Work Order')

@section('workorder_from')
    <address class="text-muted">
        <strong>{{get_setting('company_name') }}</strong><br>
        {{ get_setting('company_address') }}<br>
        {{ get_setting('company_email') }}<br>
        {{ get_setting('company_phone') }}
    </address>
@endsection
@section('workorder_to')
    <strong class="fw-bold">Bill To:</strong>
    <address class="text-muted">
        {!! get_user_address_name_by_id($workorder->repairIssue->finalContractor->id) !!}
    </address>
@endsection
{{-- {{ dd($workorder->repairIssue->finalContractor->id) }} --}}

@section('workorder_status')
    <span class="status-{{ strtolower($workorder->status) }}">
        {{ strtoupper($workorder->status) }}
    </span>
@endsection

@section('workorder_number', $workorder->works_order_no)
@section('workorder_date', formatDate($workorder->tentative_start_date))
@section('workorder_due_date', formatDate($workorder->tentative_end_date))


@section('workorder_content')
    <table class="workorder-table">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Job Title</th>
                <th>Description</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Tax (%)</th>
                <th>Tax Amount</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $subtotal = 0;
                $taxTotal = 0;
                $grandTotal = 0;
            @endphp
            @foreach($workorder->items as $index => $item)
                @php
                    $rowSubtotal = $item->unit_price * $item->quantity;
                    $taxAmount = ($rowSubtotal * $item->tax_rate) / 100;
                    $rowTotal = $rowSubtotal + $taxAmount;
                    $subtotal += $rowSubtotal;
                    $taxTotal += $taxAmount;
                    $grandTotal += $rowTotal;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ getPoundSymbol() }}{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->tax_rate }}%</td>
                    <td>{{ getPoundSymbol() }}{{ number_format($taxAmount, 2) }}</td>
                    <td>{{ getPoundSymbol() }}{{ number_format($rowTotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('workorder_total')
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
                                <td class="currency">{{ getPoundSymbol() }}{{ number_format($subtotal, 2) }}</td>
                            </tr>
                            <tr class="border-bottom">
                                <th class="gry-color text-left">Total Tax</th>
                                <td class="currency">{{ getPoundSymbol() }}{{ number_format($taxTotal, 2) }}</td>
                            </tr>
                            <tr>
                                <th class="text-left strong">Grand Total</th>
                                <td class="currency">{{ getPoundSymbol() }}{{ number_format($grandTotal, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection

@section('additional_workorder_info')
@if($workorder->notes)
    <p><strong>Notes:</strong> {{ $workorder->notes ?? '' }}</p>
@endif
@endsection

@section('footer_text', 'All workorders must be paid within 30 days. Thank you for your business!')

@section('total_amount', '£' . number_format($grandTotal, 2))