
@if(!isset($editMode) || !$editMode)
    <!-- Display View Mode -->

    @if($repairIssue->property)
        <p><strong>Property:</strong> {{ $repairIssue->property->prop_name ?? 'N/A' }}</p>
        <p><strong>Address:</strong>
            {{ $repairIssue->property->line_1 ?? '' }} {{ $repairIssue->property->line_2 ?? '' }},
            {{ $repairIssue->property->city ?? '' }},
            {{ $repairIssue->property->postcode ?? '' }}
        </p>
        <p><strong>Type:</strong> {{ $repairIssue->property->specific_property_type ?? 'N/A' }}</p>
        <p><strong>Availability:</strong> {{ $repairIssue->property->availability ?? 'N/A' }}</p>
    @else
        <p>No property selected.</p>
    @endif
        
@else
    <form id="propertyDetailsForm">
        @csrf
        <input type="hidden" name="repair_id" value="{{ $repairIssue->id }}">
        <input type="hidden" name="form_type" value="property_details">
        
        <!-- Property Display Card: Read-only view with current selection -->
        <div class="card mb-3 validate-card" id="property-display-card">
            <div class="card-header d-flex justify-content-between align-items-center">Property
                <span>
                    <!-- Change Property Button (Initially hidden) -->
                    <button id="change_property_button" class="btn btn-info d-none">Change Property</button>

                    <!-- Cancel Button for Property Change (Initially hidden) -->
                    <button id="cancel_property_change" class="btn btn-warning d-none">Cancel</button>
                </span>
            </div>
            <div class="card-body">
                <div class="form-group text-center mt-lg-0 mt-4">
                    <div class="set-display-none" id="search_property_section" style="display: none;">
                        <label class="mb-2" for="search_property1">Search And Select Property</label>

                        <!-- Search Input (Initially hidden) -->
                        <div class="form-group">
                            <div class="rs_input input_search position-relative">
                                <div class="right_icon position-absolute top-50 translate-middle-y end-0 pe-2">
                                    <i class="bi bi-search"></i>
                                </div>
                                <input type="text" id="search_property1" placeholder="Search Property" class="form-control search_property" />
                            </div>
                            <div id="error_message" class="mt-1 text-danger" style="display: none;"></div>

                        </div>
                    </div>

                    <!-- Search Results -->
                    <ul id="property_results" class="list-group mt-2"></ul>
                    <!-- Hidden field for selected property IDs -->
                    <input type="hidden" id="selected_properties" name="property_id" value="{{ json_encode(is_array($repairIssue->property->id) ? $repairIssue->property->id : [$repairIssue->property->id]) }}">

                    {{-- <input type="hidden" id="selected_properties" name="property_id" value="{{ json_encode(isset($repairIssue->property->id) ? $repairIssue->property->id : []) }}"> --}}
                </div>
                <!-- Dynamic Property Table -->
                <div id="dynamic_property_table" class="d-none px-3">
                    @php
                        $headers = ['Address', 'Type', 'Availability'];
                        // $headers = ['Address', 'Type', 'Availability', 'Actions'];
                        $rows = []; // Initially empty
                    @endphp
                    <x-backend.dynamic-table :headers="$headers" :rows="$rows" :actionBtn="False" class="user_add_property" />
                </div>
            </div>
        </div>

        <div class="modal-footer px-0">
            <div class="row">
                <div class="col-auto">
                    <button type="button" class="btn btn_outline_secondary" onclick="closeModel();" data-bs-dismiss="modal">Close</button>
                </div>
                <div class="col-auto px-0">
                    <button type="submit" class="btn btn_secondary">Save Changes</button>
                </div>
            </div>
        </div>
    </form>
@endif