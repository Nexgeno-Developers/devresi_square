@extends('layouts.invoice_layout')

@section('title', 'Invoice - Work Order')
@section('company_name', 'TechCorp Solutions')
@section('company_address', '456 Innovation Drive, Tech City, USA')
@section('company_email', 'billing@techcorp.com')
@section('company_phone', '+1 987 654 3210')

@section('invoice_title', 'Official Invoice')
@section('invoice_number', $invoice->invoice_number)
@section('invoice_date', $invoice->invoice_date->format('d M Y'))

@section('additional_invoice_info')
    <p><strong>Work Order: </strong>{{ $invoice->workOrder->works_order_no }}</p>
@endsection

@section('invoice_content')
    <p><strong>Client Name:</strong> {{ $invoice->workOrder->invoice_to }}</p>
    <p><strong>Job Type:</strong> {{ $invoice->workOrder->jobType->name }}</p>


    {{-- <!-- Invoice Table -->
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->workOrder->job_scope }}</td>
                <td>{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table> --}}
@endsection


@section('total_amount', number_format($invoice->total_amount, 2))
@section('footer_text', 'Please make payment within 7 days.')
