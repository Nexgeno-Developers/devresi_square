<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Work Order Details</h4>
    <button type="button" class="btn btn-sm btn-outline-primary" id="toggleWorkOrderEdit">
        {{ $workorder ? 'Edit Work Order' : 'Create Work Order' }}
    </button>
</div>

<input type="hidden" id="property_id" value="{{ $propertyId }}">

<div id="workOrderDetailView" class="{{ $workorder ? '' : 'd-none' }}">
    @include('backend.repair.popup_forms.work_order_detail', ['repairIssue' => $repairIssue])
</div>

<div id="workOrderEditView" class="{{ $workorder ? 'd-none' : '' }}">
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
</div>
