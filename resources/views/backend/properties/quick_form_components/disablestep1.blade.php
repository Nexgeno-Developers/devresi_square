<!-- resources/views/backend/properties/quick_form_components/step1.blade.php -->

<div class="container-fluid mt-4 quick_add_property">
    <div class="row">
        <div class="col-md-6 col-12 left_col">
            <div class="left_content_wrapper">
                <div class="left_content_img">
                    <img src="{{ asset('asset/images/svg/building.svg') }}" alt="Property Name">
                </div>
                <div class="left_title">
                    What is your<br /> <span class="secondary-color">property</span> name?
                </div>
            </div>
        </div>
        <div class="col-md-6 col-12 right_col">
            <div class="right_content_wrapper">
                <form id="property-form-step-1" method="POST" action="{{ route('admin.properties.quick_store') }}">
                    @csrf
                    <!-- Hidden field for property ID with isset check -->
                    <input type="hidden" id="property_id" class="property_id" name="property_id" value="{{ (isset($property) ? $property->id : '') }}">                   
                    <div class="right_content_wrapper" data-step-name="Property Name" data-step-number="1"></div>
                    <div class="form-group">
                        <label for="prop_name">Property Name</label>
                        <input required type="text" name="prop_name" id="prop_name" class="form-control"
                            value="{{ (isset($property) && $property->prop_name) ? $property->prop_name : '' }}">
                        @error('prop_name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="button" class="btn btn-primary btn-sm next-step mt-2 w-50" data-next-step="2"
                        data-current-step="1">Next</button>
                </form>
            </div>
        </div>
    </div>
</div>