<!-- resources/views/backend/repair/common_form.blade.php -->


    <!-- Textarea for description -->
    <div class="mb-3">
        <label for="description" class="form-label">What is wrong</label>
        <textarea class="form-control" id="description" rows="4" name="description" required placeholder="Kitchen tap is dripping"></textarea>
    </div>

    {{-- <!-- Multiple Image Select -->
    <div class="mb-3">
        <label for="images" class="form-label">Select Images</label>
        <input class="form-control" type="file" id="images" name="images[]" accept="image/*" multiple>
    </div> --}}

    @if(is_landlord_plan_user())
    <div class="mb-3">
        <label for="repair_photo" class="form-label">Photo</label>
        <input class="form-control" type="file" id="repair_photo" name="repair_photo" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">Optional. The photo is saved with this form.</div>
        <img id="repair_photo_preview" alt="Selected repair photo" class="d-none mt-2 rounded" style="max-height:160px">
    </div>
    @else
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
    @endif


