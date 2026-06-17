@extends('backend.work_orders.workorder_pdf_layout')

@section('title', 'Work Order')
@section('workorder_title', 'Work Order')

@php
    $repairIssue = $workorder->repairIssue;
    $finalContractor = $repairIssue?->finalContractor;
    $property = $repairIssue?->property;
    $propertyAddress = $property ? get_property_address_by_id($property->id) : null;
@endphp

@section('workorder_from')
    <address class="text-muted">
        <span>Authorised by</span><br>
        <strong>{{ get_setting('company_name') }}</strong><br>
        {{ get_setting('company_address') }}<br>
        {{ get_setting('company_email') }}<br>
        {{ get_setting('company_phone') }}
    </address>
@endsection
@section('workorder_to')
    <strong class="fw-bold">Work Order To:</strong>
    <address class="text-muted">
        @if($finalContractor)
            {!! get_user_address_name_by_id($finalContractor->id) !!}
        @elseif($propertyAddress)
            <strong>Property</strong><br>{{ $propertyAddress }}
        @else
            Contractor not assigned
        @endif
    </address>
@endsection

@section('workorder_status')
    <span class="status-{{ strtolower($workorder->status) }}">
        {{ strtoupper($workorder->status) }}
    </span>
@endsection

@section('workorder_number', $workorder->works_order_no)
@section('workorder_date', formatDate($workorder->tentative_start_date))
@section('workorder_due_date', formatDate($workorder->tentative_end_date))


@section('workorder_content')
    <table class="workorder-detail-table">
        <tbody>
            <tr>
                <th>Property</th>
                <td>
                    <strong>{{ $property->prop_ref_no ?? '-' }}</strong>
                    @if($property?->prop_name)
                        - {{ $property->prop_name }}
                    @endif
                    @if($propertyAddress)
                        <br>{{ $propertyAddress }}
                    @endif
                </td>
            </tr>
            <tr>
                <th>Repair Issue</th>
                <td>
                    <strong>Reference:</strong> {{ $repairIssue->reference_number ?? '-' }}<br>
                    <strong>Category:</strong> {{ $repairIssue->repairCategory->name ?? '-' }}<br>
                    <strong>Priority:</strong> {{ ucfirst($repairIssue->priority ?? '-') }}<br>
                    <strong>Status:</strong> {{ $repairIssue->status ?? '-' }}
                </td>
            </tr>
            <tr>
                <th>Issue Details</th>
                <td>
                    {{ $repairIssue->description ?: '-' }}
                </td>
            </tr>
            <tr>
                <th>Job</th>
                <td>
                    <strong>Type:</strong> {{ $workorder->jobType->name ?? '-' }}
                    @if($workorder->jobSubType)
                        / {{ $workorder->jobSubType->name }}
                    @endif
                    <br>
                    <strong>Start Date:</strong> {{ formatDate($workorder->tentative_start_date) }}<br>
                    <strong>Completion Date:</strong> {{ formatDate($workorder->tentative_end_date) }}<br>
                    <strong>Booked Date:</strong> {{ formatDate($workorder->booked_date) }}
                </td>
            </tr>
            <tr>
                <th>Access Details</th>
                <td>
                    <strong>Access:</strong> {{ $repairIssue->access_details ?: '-' }}<br>
                    <strong>Availability:</strong> {{ optional($repairIssue->tenant_availability)->format('d M Y, H:i') ?: '-' }}
                </td>
            </tr>
        </tbody>
    </table>

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
@if($workorder->extra_notes)
    <p><strong>Notes:</strong> {{ $workorder->extra_notes }}</p>
@endif

<div class="instructions-conditions">
    <h3>Instructions &amp; Conditions</h3>
    <ul>
        <li>Call the contact person available on the site before attending to confirm appointment.</li>
        {{-- <li>Contact tenant at least 24 hours before attending to confirm appointment.</li> --}}
        <li>The property must be left clean, tidy, and secure upon departure. All waste to be removed from site.</li>
        <li>Quote required for any works exceeding the authorised limit before proceeding.</li>
        <li>Provide a written job report with dated photographic evidence of works before and after.</li>
        <li>All works must comply with current UK Building Regulations and relevant British Standards.</li>
        <li>Contractor is responsible for holding valid public liability insurance (min. £2m).</li>
    </ul>
</div>  
@endsection

@section('footer_text', 'All workorders must be paid within 30 days. Thank you for your business!')

@section('total_amount', '£' . number_format($grandTotal, 2))
