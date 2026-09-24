@if (isset($complianceRecord->id))
    <input type="hidden" name="record_id" value="{{ $complianceRecord->id }}">
@endif
<input type="hidden" name="property_id" value="{{ $propertyId ?? '' }}">
<input type="hidden" name="compliance_type_id" value="{{ $complianceType->id ?? '' }}">

<div class="mb-3">
    <label for="issued_date" class="form-label">Issued date</label>
    <input type="date" class="form-control" id="issued_date" name="issued_date"
        value="{{ old('issued_date', isset($complianceRecord) && $complianceRecord->issued_date ? $complianceRecord->issued_date->format('Y-m-d') : '') }}">
</div>

<div class="mb-3">
    <label for="expiry_date" class="form-label">{{ $expiryDateLabel ?? 'Expiry date' }}</label>
    <input type="date" class="form-control" id="expiry_date" name="expiry_date"
        value="{{ old('expiry_date', isset($complianceRecord) && $complianceRecord->expiry_date ? $complianceRecord->expiry_date->format('Y-m-d') : '') }}" required>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-6">
        <label for="served_to_tenant_at" class="form-label">Served to tenant on</label>
        <input type="date" id="served_to_tenant_at" name="served_to_tenant_at" class="form-control"
            value="{{ old('served_to_tenant_at', isset($complianceRecord) && $complianceRecord->served_to_tenant_at ? $complianceRecord->served_to_tenant_at->format('Y-m-d') : '') }}">
        <div class="form-text">Leave blank until you give them a copy.</div>
    </div>
    <div class="col-md-6">
        <label for="served_notes" class="form-label">How it was served</label>
        <input type="text" id="served_notes" name="served_notes" class="form-control" maxlength="1000"
            placeholder="e.g. Handed over at move-in / emailed PDF"
            value="{{ old('served_notes', $complianceRecord->served_notes ?? '') }}">
    </div>
</div>

@unless(is_landlord_plan_user())
<div class="row g-2 mb-3">
    <div class="col-md-4">
        <label for="responsible_user_id" class="form-label">Responsible person</label>
        <select id="responsible_user_id" name="responsible_user_id" class="form-select">
            <option value="">Account administrators</option>
            @foreach(($responsibleUsers ?? collect()) as $responsibleUser)
                <option value="{{ $responsibleUser->id }}" @selected((int) old('responsible_user_id', $complianceRecord->responsible_user_id ?? 0) === (int) $responsibleUser->id)>{{ $responsibleUser->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="remediation_due_at" class="form-label">Remediation deadline</label>
        <input type="datetime-local" id="remediation_due_at" name="remediation_due_at" class="form-control" value="{{ old('remediation_due_at', isset($complianceRecord) && $complianceRecord->remediation_due_at ? $complianceRecord->remediation_due_at->format('Y-m-d\\TH:i') : '') }}">
    </div>
    <div class="col-md-4">
        <label for="completed_at" class="form-label">Closed at</label>
        <input type="datetime-local" id="completed_at" name="completed_at" class="form-control" value="{{ old('completed_at', isset($complianceRecord) && $complianceRecord->completed_at ? $complianceRecord->completed_at->format('Y-m-d\\TH:i') : '') }}">
        <div class="form-text">Only set when this certificate item is fully closed.</div>
    </div>
</div>
@endunless

<div class="form-group rs_upload_btn">
    <h6 class="sub_title mt-4">{{ is_landlord_plan_user() ? 'Certificate file' : ($uploadHeading ?? 'Upload Image') }}</h6>
    <div class="media_wrapper2">
        <div class="input-group" data-toggle="aizuploader" data-type="all" data-multiple="true">
            <label class="col-form-label">{{ is_landlord_plan_user() ? 'File' : ($uploadLabel ?? 'Photos') }}</label>
            <div class="d-none input-group-prepend">
                <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
            </div>
            <div class="d-none form-control file-amount">Choose File</div>
            <input id="photos" type="hidden" name="photos" value="{{ isset($complianceRecord) && $complianceRecord->photos ? $complianceRecord->photos : '' }}" class="selected-files">
        </div>
        <div class="d-flex gap-3 file-preview box sm">
        </div>
    </div>
</div>
