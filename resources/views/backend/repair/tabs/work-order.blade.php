<h4 class="mb-3">{{ ! $workorder ? 'Create Work Order' : 'Edit Work Order' }}</h4>

<input type="hidden" id="property_id" value="{{ $propertyId }}">

@include('backend.work_orders.work_order_form', [
    'repairIssue' => $repairIssue,
    'workorder' => $workorder,
    'jobTypes' => $jobTypes,
    'taxRates' => $taxRates,
    'contractorCost' => $contractorCost,
    'quoteAttachment' => $quoteAttachment,
    'propertyId' => $propertyId,
    'showInvoiceActions' => $showInvoiceActions,
])
