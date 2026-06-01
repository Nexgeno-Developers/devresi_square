<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.property_repairs.edit', $repairIssue->id) }}" class="btn btn-sm btn-outline-danger">
        <i class="fas fa-edit"></i> Edit Manager Assignments
    </a>
</div>

@include('backend.repair.popup_forms.manager_assign', ['repairIssue' => $repairIssue])
