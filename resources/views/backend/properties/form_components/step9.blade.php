@php $currentStep = 9 ; @endphp
<!-- resources/views/backend/properties/form_components/step8.blade.php -->
<form id="property-form-step-{{ $currentStep }}" class="rs_steps" method="POST" action="{{ route('admin.properties.store') }}"
    enctype="multipart/form-data">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" id="property_id" class="property_id" name="property_id"
        value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">

    <div class="main_title">Media</div>

    <div class="steps_wrapper" data-step-name="Media" data-step-number="{{ $currentStep }}" data-step-title="Media">
{{--
<!-- old-code -->
        <div class="form-group rs_upload_btn">
            <h5 class="sub_title mt-4">Photos</h5>
            <div class="media_wrapper">
                <div class="media_content">
                    <div class="image_wrapper">
                        <!-- Preview images will be dynamically added here -->
                    </div>
                    <div class="media_upload">
                        <div for="photos">Upload Photos</div>
                        <input type="file" name="photos[]" id="photos" class="form-control" multiple accept="image/*"
                            onchange="previewMultipleImage(this)">
                        @error('photos.*')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($property && $property->photos)
                        @foreach(json_decode($property->photos) as $photoPath)
                            <img src="{{ asset('storage/' . $photoPath) }}" alt="Uploaded Photo" width="100">
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- aiz-uploader  -->
        <div class="form-group row">
            <label class="col-md-3 col-form-label" for="signinSrEmail">Gallery Images</label>
            <div class="col-md-8">
                <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                    <div class="input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                    </div>
                    <div class="form-control file-amount">Choose File</div>
                    <input id="sort-the-photo" type="hidden" name="photos" value="" class="selected-files">
                </div>
                <div id="sort-photo" class="file-preview box sm">
                </div>
            </div>
        </div>

--}}
        <div class="form-group rs_upload_btn">
            <h5 class="sub_title mt-4">Photos <small>(Living Room, Reception, Bed Room, Bath Room, Garden, Hallway, Exterior - Cover Image)</small></h5>
            <div class="media_wrapper">
                <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                    <label class="col-form-label" for="photos">Photos</label>
                    <div class="d-none input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                    </div>
                    <div class="d-none form-control file-amount">Choose File</div>
                    <input id="photos" id="photos" type="hidden" name="photos" value="{{ isset($property) && isset($property->photos) ? $property->photos : '' }}" class="selected-files">
                </div>
                <div class="d-flex gap-3 file-preview box sm">
                </div>
            </div>
        </div>

        <div class="form-group rs_upload_btn">
            <h5 class="sub_title mt-4">Floor Plan</h5>
            <div class="media_wrapper">
                <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                    <label for="floor_plan">Upload Floor Plan Photos</label>
                    <div class="d-none input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                    </div>
                    <div class="d-none form-control file-amount">Choose File</div>
                    <input id="floor_plan" type="hidden" name="floor_plan" value="{{ isset($property) && isset($property->floor_plan) ? $property->floor_plan : '' }}" class="selected-files">
                </div>
                <div class="d-flex gap-3 file-preview box sm">
                </div>
            </div>
        </div>

        <div class="form-group rs_upload_btn">
            <h5 class="sub_title mt-4">View 360</h5>
            <div class="media_wrapper">
                <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                    <label for="view_360">Upload 360 View Photos</label>
                    <div class="d-none input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                    </div>
                    <div class="d-none form-control file-amount">Choose File</div>
                    <input type="hidden" id="view_360" name="view_360" value="{{ isset($property) && isset($property->view_360) ? $property->view_360 : '' }}" class="selected-files">
                </div>
                <div class="d-flex gap-3 file-preview box sm">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-12">
                <div class="form-group rs_upload_btn">
                    <h5 class="sub_title mt-4">Video URL</h5>
                    <label for="video_url">Video URL</label>
                    <input required type="url" name="video_url" id="video_url" class="form-control" value="{{ isset($property) && $property->video_url ? $property->video_url : '' }}">
                    @error('video_url')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="footer_btn">
                    <div class="row mt-lg-4">
                        <div class="col-6">
                            <button type="button" class="btn btn_outline_secondary w-100 previous-step" data-previous-step="{{ $currentStep - 1 }}"
                                data-current-step="{{ $currentStep }}">Previous</button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn_secondary w-100 next-step" data-next-step="{{ $currentStep + 1 }}"
                                data-current-step="{{ $currentStep }}">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</form>
