<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-sm btn-outline-danger editRepairForm"
        data-id="{{ $repairIssue->id }}"
        data-form="manager_assign"
        data-title="Edit Property Managers Assignment">
        <i class="fas fa-edit"></i> Edit Manager Assignments
    </button>
</div>

@include('backend.repair.popup_forms.manager_assign', ['repairIssue' => $repairIssue])
