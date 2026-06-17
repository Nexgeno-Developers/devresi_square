<p>Hello {{ $contractor->name }},</p>

<p>You have been assigned repair work for issue <strong>{{ $repairIssue->reference_number }}</strong>.</p>

<p>
    <strong>Property:</strong>
    {{ getPropertyDetails($repairIssue->property_id, ['prop_name', 'line_1', 'city', 'postcode']) }}
</p>

<p><strong>Issue Category:</strong> {{ getRepairCategoryDetails($repairIssue->repair_category_id) }}</p>

@if($repairIssue->description)
    <p><strong>Description:</strong><br>{{ $repairIssue->description }}</p>
@endif

<p>
    <strong>Repair Status:</strong> {{ $repairIssue->status ?? '-' }}<br>
    <strong>Contractor Job Status:</strong> {{ $repairIssue->sub_status ?? '-' }}<br>
    <strong>Priority:</strong> {{ ucfirst($repairIssue->priority ?? '-') }}
</p>

@if($repairIssue->tenant)
    <p>
        <strong>Tenant:</strong> {{ $repairIssue->tenant->name }}<br>
        <strong>Tenant Email:</strong> {{ $repairIssue->tenant->email ?? '-' }}<br>
        <strong>Tenant Phone:</strong> {{ $repairIssue->tenant->phone ?? '-' }}
    </p>
@endif

@if($repairIssue->tenant_availability || $repairIssue->access_details)
    <p>
        <strong>Preferred Availability:</strong>
        {{ optional($repairIssue->tenant_availability)->format('d M Y, H:i') ?: '-' }}<br>
        <strong>Access Details:</strong> {{ $repairIssue->access_details ?: '-' }}
    </p>
@endif

@if($assignment)
    <p>
        <strong>Estimated Price:</strong>
        {{ $assignment->cost_price !== null ? getPoundSymbol() . number_format($assignment->cost_price, 2) : '-' }}<br>
        <strong>Your Preferred Availability:</strong>
        {{ optional($assignment->contractor_preferred_availability)->format('d M Y, H:i') ?: '-' }}
    </p>
@endif

@if($repairIssue->workOrder)
    <p>
        <strong>Work Order:</strong> {{ $repairIssue->workOrder->works_order_no ?? '-' }}<br>
        <strong>Job Type:</strong> {{ $repairIssue->workOrder->jobType->name ?? '-' }}<br>
        <strong>Job Sub Type:</strong> {{ $repairIssue->workOrder->jobSubType->name ?? '-' }}<br>
        <strong>Start Date:</strong> {{ $repairIssue->workOrder->tentative_start_date ?? '-' }}<br>
        <strong>End Date:</strong> {{ $repairIssue->workOrder->tentative_end_date ?? '-' }}
    </p>
@endif

<p>The work order PDF is attached for your reference.</p>

<p>Thank you.</p>
