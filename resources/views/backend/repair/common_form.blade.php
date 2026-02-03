<!-- resources/views/backend/repair/common_form.blade.php -->


    <!-- Textarea for description -->
    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control" id="description" rows="4" name="description" required></textarea>
    </div>

    {{-- <!-- Multiple Image Select -->
    <div class="mb-3">
        <label for="images" class="form-label">Select Images</label>
        <input class="form-control" type="file" id="images" name="images[]" accept="image/*" multiple>
    </div> --}}

    <div class="form-group rs_upload_btn">
        <h5 class="sub_title mt-4">Select images</h5>
        <div class="media_wrapper">
            <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                <label for="repair_photos">Upload Photos</label>
                <div class="d-none input-group-prepend">
                    <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                </div>
                <div class="d-none form-control file-amount">Choose File</div>
                <input type="hidden" id="repair_photos" name="repair_photos" class="selected-files">
            </div>
            <div class="d-flex gap-3 file-preview box sm">
            </div>
        </div>
    </div>


