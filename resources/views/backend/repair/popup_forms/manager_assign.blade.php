@if (!isset($editMode) || !$editMode)
    <!-- Display View -->
    <h5 class="fw-bold fs-4 my-3 pt-2">Property Manager Assignments</h5>

    @if($repairIssue->repairIssuePropertyManagers->count())
        @foreach($repairIssue->repairIssuePropertyManagers as $index => $assignment)
            <div class="mb-3 p-3 bg-light rounded border">
                <p class="mb-1 fs-6">
                    <span class="fw-semibold text-primary">#{{ $index + 1 }}</span>
                </p>
                <p class="mb-1 fs-6">
                    <span class="fw-semibold">Manager:</span>
                    {{ $assignment->propertyManager->name ?? 'N/A' }}
                </p>
                <p class="mb-1 fs-6 text-muted">
                    {{ $assignment->propertyManager->email ?? 'N/A' }}
                </p>
                <p class="mb-0 fs-6">
                    <span class="fw-semibold">Assigned At:</span>
                    {{ \Carbon\Carbon::parse($assignment->assigned_at)->format('d M Y, H:i') }}
                </p>
            </div>
        @endforeach
    @else
        <p class="text-muted fs-6">No property manager assignments available.</p>
    @endif

@else
    <form id="managerAssignForm">
        @csrf
        <input type="hidden" name="repair_id" value="{{ $repairIssue->id }}">
        <input type="hidden" name="form_type" value="manager_assign">

        <div class="form-group mb-3 repair-manager-select-wrap">
            <label for="property_managers" class="form-label d-block">Assign Property Managers</label>
            <select name="property_managers[]" id="property_managers"
                class="form-control select2 repair-manager-select"
                multiple
                data-placeholder="Select property manager(s)"
                style="width: 100%;">
                @foreach(($propertyManagers ?? collect()) as $manager)
                    <option value="{{ $manager->id }}" {{ in_array($manager->id, $assignedManagers ?? []) ? 'selected' : '' }}>
                        {{ $manager->name }}{{ $manager->email ? ' - ' . $manager->email : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="modal-footer px-0">
            <button type="button" class="btn btn_outline_secondary" onclick="closeModel();" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn_secondary">Save Changes</button>
        </div>
    </form>

@endif
