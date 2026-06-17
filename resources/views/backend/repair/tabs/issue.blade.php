<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-sm btn-outline-danger editRepairForm"
        data-id="{{ $repairIssue->id }}"
        data-form="property_issue_details"
        data-title="Edit Issue">
        <i class="fas fa-edit"></i> Edit
    </button>
</div>

<div class="accordion" id="repairIssueTabAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header" id="heading-property-details">
            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse-property-details" aria-expanded="true"
                aria-controls="collapse-property-details">
                Property Details
            </button>
        </h2>
        <div id="collapse-property-details" class="accordion-collapse collapse show"
            aria-labelledby="heading-property-details">
            <div class="accordion-body">
                @include('backend.repair.popup_forms.property_details', ['repairIssue' => $repairIssue])
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="heading-issue-details">
            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse-issue-details" aria-expanded="true"
                aria-controls="collapse-issue-details">
                Property Issue Details
            </button>
        </h2>
        <div id="collapse-issue-details" class="accordion-collapse collapse show"
            aria-labelledby="heading-issue-details">
            <div class="accordion-body">
                @include('backend.repair.popup_forms.property_issue_details', ['repairIssue' => $repairIssue])
            </div>
        </div>
    </div>
</div>
