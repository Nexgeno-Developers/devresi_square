{{-- <h1 class="mb-4">Media</h1> --}}

@php
    $photos_ids = explode(',', $property->photos) ?? '';
    $floor_plan_ids = explode(',', $property->floor_plan) ?? '';
    $view_360_ids = explode(',', $property->view_360) ?? '';
    $video = $property->video_url ?? '';
@endphp

<div class="w-100">
    <button class="btn btn-outline-danger btn-sm float-end editForm" data-form="{{ 'property_media' }}" data-id="{{ $property->id }}">
        Edit
    </button>
</div>

<div id="section-property_media-{{ $property->id }}">
    @include("backend.properties.popup_forms.property_media", ['property' => $property])
</div>
{{-- 
<!-- Photos Section -->
@if ($photos_ids)
    <div class="mb-4">
        <h5>Property Photos</h5>
        <div class="row">
            @foreach ($photos_ids as $photo_id)
                <div class="col-6 col-md-4 col-lg-3 mb-3">
                    <div class="card">
                        <img src="{{ uploaded_asset($photo_id) }}" class="card-img-top" alt="photo" onclick="openImageModal('{{ uploaded_asset(trim($photo_id)) }}')">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <hr>
@endif

<!-- Floor Plans Section -->
@if ($floor_plan_ids)
    <div class="mb-4">
        <h5>Floor Plans</h5>
        <div class="row">
            @foreach ($floor_plan_ids as $photo_id)
                <div class="col-6 col-md-4 col-lg-3 mb-3">
                    <div class="card">
                        <img src="{{ uploaded_asset($photo_id) }}" class="card-img-top" alt="floor plan" onclick="openImageModal('{{ uploaded_asset(trim($photo_id)) }}')">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <hr>
@endif

<!-- 360 Views Section -->
@if ($view_360_ids)
    <div class="mb-4">
        <h5>360 Views</h5>
        <div class="row">
            @foreach ($view_360_ids as $photo_id)
                <div class="col-6 col-md-4 col-lg-3 mb-3">
                    <div class="card">
                        <img src="{{ uploaded_asset($photo_id) }}" class="card-img-top" alt="360 view" onclick="openImageModal('{{ uploaded_asset(trim($photo_id)) }}')">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <hr>
@endif

<!-- Video Section -->
@if ($video)
    <div class="mb-4">
        <h5>Video</h5>
        <div class="row">
            <div class="col-12 mb-3">
                <div class="card">
                    <!-- Embed the YouTube video using iframe -->
                    <div class="embed-responsive embed-responsive-16by9">
                        <iframe class="embed-responsive-item"
                                src="https://www.youtube.com/embed/{{ $video }}"
                                allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif --}}

{{-- <!-- Video Section -->
@if ($video)
    <div class="mb-4">
        <h5>Video</h5>
        <div class="row">
            <div class="col-6 col-md-4 col-lg-3 mb-3">
                <div class="card">
                    <video src="{{ $video }}" class="card-img-top" alt="360 view">
                </div>
            </div>
        </div>
    </div>
@endif --}}
