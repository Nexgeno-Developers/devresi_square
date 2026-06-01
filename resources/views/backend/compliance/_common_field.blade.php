    @if (isset($complianceRecord->id))
        <input type="hidden" name="record_id" value="{{ $complianceRecord->id }}">
    @endif
    <input type="hidden" name="property_id" value="{{ $propertyId ?? '' }}">
    <input type="hidden" name="compliance_type_id" value="{{ $complianceType->id ?? '' }}">

    <!-- Expiry Date Field -->
    <div class="mb-3">
        <label for="expiry_date" class="form-label">Expiry Date</label>
        <input type="date" class="form-control" id="expiry_date" name="expiry_date"
            value="{{ isset($complianceRecord) && $complianceRecord->expiry_date ? $complianceRecord->expiry_date : '' }}" required>
    </div>

    <!-- Image Upload Field -->
    <div class="form-group rs_upload_btn">
        <h6 class="sub_title mt-4">Upload Image</h6>
        <div class="media_wrapper2">
            <div class="input-group" data-toggle="aizuploader" data-type="all" data-multiple="true">
                <label class="col-form-label" for="photos">Photos</label>
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
