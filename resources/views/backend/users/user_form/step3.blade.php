<!-- resources/views/backend/properties/quick_form_components/step4.blade.php -->
@php $currentStep = 3 ;
@endphp
<div class="container-fluid mt-4 quick_add_user">
    <div class="row">
        <div class="col-md-6 col-12 left_col">
            <div class="left_content_wrapper">
                <div class="left_title"> Add <span class="secondary-color">Properties</span><br /> related to this user </div>
            </div>
        </div>
        <div class="col-md-6 col-12 right_col">
            <form id="user-form-step-{{$currentStep}}" method="POST" action="{{ route('admin.properties.quick_store') }}">
                @csrf
                <!-- Hidden field for user ID with isset check -->
                <input type="hidden" name="step" value="{{ $currentStep }}">
                <input type="hidden" id="user_id" class="user_id" name="user_id" value="{{ isset($user) ? $user->id : '' }}">

                <div class="steps_wrapper">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="from-group mt-lg-0 mt-4">
                                <label class="mb-2" for="search_property1">Search Property</label>
                                <div class="row">
                                    <div class="col-12">

                                        <!-- Search input field for properties -->
                                        <div class="form-group">
                                            <div class="rs_input input_search">
                                                <input type="text" id="search_property1" name="search_property" placeholder="Search Property" />
                                                <div class="right_icon"><i class="bi bi-search"></i></div>
                                            </div>
                                            <div id="error_message" style="color: red; display: none;"></div>
                                        </div>

                                        <!-- Search results listing -->
                                        <ul id="property_results" class="list-group mt-2"></ul>

                                        <!-- Selected Properties -->
                                        <input type="hidden" id="selected_properties" name="selected_properties" value="{{ json_encode(isset($selectedProperties) ? $selectedProperties : []) }}">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Property Table -->
                    <div id="dynamic_property_table" class="mt-4">
                        @php
                            $headers = ['id' => 'id', 'Address', 'Type', 'Availability'];
                            $rows = []; // Start with an empty array of rows
                        @endphp
                        <x-backend.dynamic-table :headers="$headers" :rows="$rows" class='user_add_property' />
                    </div>

                </div>
                <div class="footer_btn">
                    <div class="row mt-lg-4">
                        <div class="col-6">
                            <button type="button" class="btn btn_outline_secondary btn-sm previous-step mt-lg-2 w-100" data-previous-step="{{$currentStep-1}}"
                                data-current-step="{{$currentStep}}">Previous</button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn_secondary btn-sm next-step mt-lg-2 w-100" data-next-step="{{$currentStep+1}}"
                        data-current-step="{{$currentStep}}">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
