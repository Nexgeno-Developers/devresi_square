@if(!isset($editMode) || !$editMode)
    <div class="my-3">
        @if ($property->photos)
            <h6>Property Photos</h6>
            <div class="d-flex flex-wrap gap-3">
                @foreach (explode(',', $property->photos) as $photo_id)
                    <img class="preview-img border rounded shadow-sm" role="button" title="Preview" width="150" height="auto"
                        src="{{ uploaded_asset(trim($photo_id)) }}" alt="Property Photo"
                        onclick="openImageModal('{{ uploaded_asset(trim($photo_id)) }}')">
                @endforeach
            </div>
        @else
            <p><strong>Property Photos: </strong>N/A</p>
        @endif
    </div>
    <div class="my-3">
        @if ($property->floor_plan)
            <h6>Floor Plans</h6>
            <div class="d-flex flex-wrap gap-3">
                @foreach (explode(',', $property->floor_plan) as $floor_plan_id)
                    <img class="preview-img border rounded shadow-sm" role="button" title="Preview" width="150" height="auto"
                        src="{{ uploaded_asset(trim($floor_plan_id)) }}" alt="Floor Plan"
                        onclick="openImageModal('{{ uploaded_asset(trim($floor_plan_id)) }}')">
                @endforeach
            </div>
        @else
            <p><strong>Floor Plans: </strong>N/A</p>
        @endif
    </div>
    <div class="my-3">
        @if ($property->view_360)
            <p><strong>360° View: </strong><a target="_blank" href="{{ $property->view_360 }}">View</a></p>

            {{-- <div class="d-flex flex-wrap gap-3">
                @foreach (explode(',', $property->view_360) as $view_360_id)
                <img class="preview-img border rounded shadow-sm" role="button" title="Preview" width="150" height="auto"
                    src="{{ uploaded_asset(trim($view_360_id)) }}" alt="360 View"
                    onclick="openImageModal('{{ uploaded_asset(trim($view_360_id)) }}')">
                @endforeach
            </div> --}}
        @else
            <p><strong>360° Views: </strong>N/A</p>
        @endif
    </div>
    <div class="my-3">
        @if ($property->youtube_url)
            <p><strong>YouTube Link: </strong><a target="_blank" href="{{ $property->youtube_url }}">View</a></p>
        @else
            <p><strong>YouTube Link: </strong>N/A</p>
        @endif
    </div>
    <div class="my-3">
        @if ($property->instagram_url)
            <p><strong>Instagram Link: </strong><a target="_blank" href="{{ $property->instagram_url }}">View</a></p>
        @else
            <p><strong>Instagram Link: </strong>N/A</p>
        @endif
    </div>

@else
    <form id="propertyDetailsForm">
        @csrf
        <input type="hidden" name="property_id" value="{{ $property->id }}">
        <input type="hidden" name="form_type" value="property_media">
        <div class="form-group rs_upload_btn">
            <h5 class="sub_title mt-4">Photos <small>(Living Room, Reception, Bed Room, Bath Room, Garden, Hallway, Exterior
                    - Cover Image)</small></h5>
            <div class="media_wrapper">
                <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true"
                    data-max-files="15">
                    <label class="col-form-label" for="photos">Photos</label>
                    <div class="d-none input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                    </div>
                    <div class="d-none form-control file-amount">Choose File</div>
                    <input id="photos" id="photos" type="hidden" name="photos"
                        value="{{ isset($property) && isset($property->photos) ? $property->photos : '' }}"
                        class="selected-files">
                </div>
                <div class="d-flex gap-3 file-preview box sm">
                </div>
            </div>
        </div>

        <div class="form-group rs_upload_btn">
            <h5 class="sub_title mt-4">Floor Plan</h5>
            <div class="media_wrapper">
                <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true"
                    data-max-files="1">
                    <label for="floor_plan">Upload Floor Plan Photos</label>
                    <div class="d-none input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                    </div>
                    <div class="d-none form-control file-amount">Choose File</div>
                    <input id="floor_plan" type="hidden" name="floor_plan"
                        value="{{ isset($property) && isset($property->floor_plan) ? $property->floor_plan : '' }}"
                        class="selected-files">
                </div>
                <div class="d-flex gap-3 file-preview box sm">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="view_360">360° View Link</label>
            <input type="url" name="view_360" id="view_360" class="form-control"
                placeholder="https://www.yourlink.domain-suffix/..."
                value="{{ isset($property) && isset($property->view_360) ? $property->view_360 : '' }}">
        </div>

        <div class="form-group">
            <label for="youtube_url">YouTube Link</label>
            <input type="url" name="youtube_url" id="youtube_url" class="form-control"
                placeholder="https://www.youtube.com/..." value="{{ old('youtube_url', $property->youtube_url ?? '') }}">
        </div>

        <div class="form-group">
            <label for="instagram_url">Instagram Link</label>
            <input type="url" name="instagram_url" id="instagram_url" class="form-control"
                placeholder="https://www.instagram.com/..."
                value="{{ old('instagram_url', $property->instagram_url ?? '') }}">
        </div>

        {{-- <div class="form-group rs_upload_btn">
            <h5 class="sub_title mt-4">View 360</h5>
            <div class="media_wrapper">
                <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true"
                    data-max-files="15">
                    <label for="view_360">Upload 360 View Photos</label>
                    <div class="d-none input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                    </div>
                    <div class="d-none form-control file-amount">Choose File</div>
                    <input type="hidden" id="view_360" name="view_360"
                        value="{{ isset($property) && isset($property->view_360) ? $property->view_360 : '' }}"
                        class="selected-files">
                </div>
                <div class="d-flex gap-3 file-preview box sm">
                </div>
            </div>
        </div> --}}

        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>

@endif