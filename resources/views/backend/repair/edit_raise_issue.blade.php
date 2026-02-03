@extends('backend.layout.app')

@section('content')
<style>
    .border-danger {
    border: 2px solid #dc3545 !important;
}
</style>
<div class="container">

    <div class="d-flex justify-content-between align-items-center mt-2 mt-md-4">
        <h1>Edit Repair Issue</h1>
        <a class="btn btn-primary float-end" href="{{ route('admin.repair.workorder.invoice', $repairIssue->id) }}">{{ $repairIssue->workOrder ? 'Edit Work Order & Invoice' : 'Create Work Order & Invoice' }}</a>
        {{-- <button class=" float-end btn btn-primary" data-bs-toggle="modal" data-bs-target="#workOrderModal">{{ $repairIssue->workOrder ? 'Edit Work Order' : 'Create Work Order' }}</button> --}}
    </div>

    <form id="repair-form-page" action="{{ route('admin.property_repairs.update', $repairIssue->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row justify-content-center align-items-center">
            <div class="col-12">
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
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <!-- Category Display Card: Read-only view with current selection -->
                <div class="card mb-3 validate-card" id="category-display-card">
                    <div class="card-header d-flex justify-content-between align-items-center">Category <button type="button" class="btn btn-info" id="change-category-btn">Change Category</button></div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="repair_category_display">Category</label>
                            <input readonly type="text" class="form-control" id="repair_category_display"
                                value="{{ old('repair_category_id', getRepairCategoryDetails($repairIssue->repair_category_id)) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <p><b>Navigation:</b> {{ getFormattedRepairNavigation($repairIssue->repair_navigation) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Category Edit Card: Multi-step category selector (initially hidden) -->
                <div class="card mb-3 d-none validate-card" id="category-edit-card">
                    <div class="card-header d-flex justify-content-between align-items-center">Select New Category <button type="button" class="btn btn-warning mt-2 d-none" id="cancel-category-btn">Cancel Category Change</button>
                    </div>
                    <div class="card-body">
                        <!-- Breadcrumb Navigation (optional) -->
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb"></ol>
                        </nav>
                        <!-- Category Main View -->
                        <div id="category-main-view" class="main-view">
                            <!-- Level 1 Categories: Rendered on page load -->
                            <div class="category-level" data-level="1">
                                <div class="row">
                                    @foreach ($categories as $category)
                                        @if(is_null($category->parent_id))
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check d-flex align-items-center">
                                                    <input class="form-check-input" type="radio" name="category_1"
                                                        id="repair-{{ $category->id }}" value="{{ $category->id }}">
                                                    <label class="form-check-label d-flex align-items-center"
                                                        for="repair-{{ $category->id }}">
                                                        <i class="fas fa-cogs me-2"></i>
                                                        {{ $category->name }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <!-- Dynamically generated levels -->
                            @for ($level = 2; $level <= $maxLevel; $level++)
                                <div class="category-level" data-level="{{ $level }}" style="display: none;">
                                    <div class="row"></div>
                                </div>
                            @endfor
                        </div>
                        <!-- Hidden inputs to store new category navigation -->
                        <input type="hidden" name="repair_navigation_old" id="selected_categories_old" value="{{ $repairIssue->repair_navigation }}">
                        <input type="hidden"  name="repair_category_id_old" id="last_selected_category_old" value="{{ $repairIssue->repair_category_id }}">
                        <input type="hidden" name="repair_navigation" id="selected_categories" value="{{ $repairIssue->repair_navigation }}">
                        <input type="hidden" name="repair_category_id" id="last_selected_category" value="{{ $repairIssue->repair_category_id }}">
                        <!-- Navigation buttons for category selection -->
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-secondary" id="category-prev-btn">Previous</button>
                            <button type="button" class="btn btn-primary d-none " id="category-next-btn" disabled>Next</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card mb-3 validate-card">
                    <div class="card-header">Description</div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description"
                                required>{{ old('description', $repairIssue->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card mb-3 validate-card">
                    <div class="card-header">Photos</div>
                    <div class="card-body">
                        <div class="form-group rs_upload_btn">
                            <h5 class="sub_title mt-4">Select images</h5>
                            <div class="media_wrapper">
                                <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                                    <label for="repair_photos">Upload Photos</label>
                                    <div class="d-none input-group-prepend">
                                        <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                                    </div>
                                    <div class="d-none form-control file-amount">Choose File</div>
                                    <input type="hidden" id="repair_photos" name="repair_photos" value="{{ $repairIssue->repairPhotos->isNotEmpty() ? $repairIssue->repairPhotos->first()->photos : '' }}" class="selected-files">
                                </div>
                                <div class="d-flex gap-3 file-preview box sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card mb-3">
                    <div class="card-header">Priority</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="priority">Select Priority</label>
                            <select name="priority" id="priority" class="form-control">
                                <option value="low" {{ $repairIssue->priority == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ $repairIssue->priority == 'medium' ? 'selected' : '' }}>Medium
                                </option>
                                <option value="high" {{ $repairIssue->priority == 'high' ? 'selected' : '' }}>High
                                </option>
                                <option value="critical" {{ $repairIssue->priority == 'critical' ? 'selected' : '' }}>
                                    Urgent</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card mb-3 mb-3">
                    <div class="card-header">Repair Statuses</div>
                    <div class="card-body">
                        <div class="form-group mb-2">
                            <label for="sub_status">Job Status(contractor)</label>
                            <select name="sub_status" id="sub_status" class="form-control">
                                @foreach(['Pending', 'Quoted', 'Awarded','Work Completed'] as $sub_status)
                                    <option value="{{ $sub_status }}" {{ $repairIssue->sub_status == $sub_status ? 'selected' : '' }}>
                                        {{ $sub_status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label for="status">Repair Ticket Status</label>
                            <select name="status" id="status" class="form-control">
                                @foreach(['Pending', 'Reported', 'Under Process', 'Work Completed', 'Closed', 'Cancelled'] as $status)
                                    <option value="{{ $status }}" {{ $repairIssue->status == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <!-- New Fields: Tenant/Owner Availability and Access Details -->
                <div class="card mb-3">
                    <div class="card-header">Tenant/Owner Details</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="tenant_availability">Preferred Availability for Repair (Tenant/Owner)</label>
                            <input type="datetime-local" name="tenant_availability" id="tenant_availability"
                                class="form-control"
                                value="{{ old('tenant_availability', optional($repairIssue->tenant_availability)->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="form-group">
                            <label for="access_details">Access Details Note</label>
                            <!-- You might replace this with a rich text editor -->
                            <textarea name="access_details" id="access_details" class="form-control"
                                rows="3">{{ old('access_details', $repairIssue->access_details) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <!-- Estimated Price (Only for admin/property manager) -->
                {{-- @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('property_manager')) --}}
                <div class="card mb-3">
                    <div class="card-header">Estimated Price</div>
                    <div class="card-body">
                        <!-- Estimated Price Input -->
                        <div class="form-group">
                            <label for="estimated_price">Estimated Price</label>
                            <input type="number" step="0.01" name="estimated_price" id="estimated_price"
                                class="form-control"
                                value="{{ old('estimated_price', $repairIssue->estimated_price) }}">
                        </div>
                        <!-- VAT Type Radio Buttons -->
                        <div class="form-group mt-3">
                            <label>VAT Type:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vat_type" id="vat_type_inclusive"
                                    value="inclusive" {{ old('vat_type', $repairIssue->vat_type) == 'inclusive' ? 'checked' : '' }}>
                                <label class="form-check-label" for="vat_type_inclusive">
                                    Inclusive VAT
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vat_type" id="vat_type_exclusive"
                                    value="exclusive" {{ old('vat_type', $repairIssue->vat_type) == 'exclusive' ? 'checked' : '' }}>
                                <label class="form-check-label" for="vat_type_exclusive">
                                    Exclusive VAT
                                </label>
                            </div>
                        </div>
                        <!-- Exclusive VAT Fields (hidden by default) -->
                        <div class="form-group mt-3 d-none" id="exclusive_vat_fields">
                            <label for="vat_percentage">VAT Percentage</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text" id="basic-addon1">%</span>
                                <input type="text" name="vat_percentage" id="vat_percentage" value="{{ old('vat_percentage', $repairIssue->vat_percentage) }}" class="form-control" placeholder="Enter VAT Percentage" aria-label="VAT Percentage" aria-describedby="basic-addon1">
                              </div>
                        </div>

                        <!-- VAT Calculation Preview -->
                        <div class="form-group d-none" id="vat_calculation_preview">
                            <label for="vat_calculation">VAT Calculation Preview</label>
                            <div class="form-control" id="vat_calculation">
                                <!-- Calculation preview will be displayed here -->
                            </div>
                        </div>

                    </div>
                </div>
                {{-- @endif --}}
            </div>
            <div class="col-6">
                <!-- Multiple Property Manager Assignment -->
                <div class="card mb-3">
                    <div class="card-header">Property Managers Assignment</div>
                    <div class="card-body">
                        <!-- Assume $propertyManagers is passed from controller as list of users with manager role -->
                        <div class="form-group">
                            <label for="property_managers">Assign Property Managers</label>
                            <select name="property_managers[]" id="property_managers" class="form-control select2"
                                multiple>
                                @foreach($propertyManagers as $manager)
                                    <option value="{{ $manager->id }}" {{ in_array($manager->id, $assignedManagers) ? 'selected' : '' }}>{{ $manager->name }} ({{ $manager->email }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <!-- Contractor Assignment Section wrapped in a card -->
                <div class="card mb-3">
                    <div class="card-header">Final Contractor Assign</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="final_contractor">Select Final Contractor:</label>
                            <select name="final_contractor_id" id="final_contractor" class="form-control">
                                <option value="">-- Select Contractor --</option>
                                @foreach ($contractorAssignments as $assignment)
                                    <option value="{{ $assignment->contractor_id }}" {{ $repairIssue->final_contractor_id == $assignment->contractor_id ? 'selected' : '' }}>{{  $assignment->contractor->name }} ({{ $assignment->contractor->email }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <!-- Contractor Assignment Section wrapped in a card -->
                <div class="card mb-3">
                    <div class="card-header">Contractor Assignment</div>
                    <div class="card-body">
                        <div id="contractor-assignments">
                            <!-- New contractor assignment blocks will be appended here via JavaScript -->
                        </div>
                        <button type="button" id="add-contractor-assignment" class="btn btn-primary">Add Contractor</button>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header">Tenant Details</div>
                    <div class="card-body">
                        <div class="form-group">
                            <input type="hidden" id="selected_tenant" value="{{ $repairIssue->tenant_id ?? '' }}">
                            <label for="tenant-select">Select Tenant</label>
                            <select name="tenant_id" id="tenant-select" class="form-control">
                                <option value="">-- Select Tenant --</option>
                                <!-- Options will be populated dynamically via AJAX -->
                            </select>
                        </div>
                        <div id="tenant-preview" class="mt-3">
                            <!-- Tenant details preview will appear here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if($repairIssue->workOrder) 
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Work Order #{{ $repairIssue->workOrder->works_order_no }} Details</h5>
                    </div>
                    <div class="card-body">
                                                   
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>Job Status:</strong> {{ $repairIssue->workOrder->job_status }}</p>
                                    <p><strong>Job Type:</strong> {{ $repairIssue->workOrder->jobType->name ?? '' }}</p>
                                    <p><strong>Job Sub Type:</strong> {{ $repairIssue->workOrder->jobSubType->name ?? '' }}</p>
                                    <p><strong>Job Scope:</strong>
                                    

                                        @if(!empty($repairIssue->workOrder->items))
                                            @php
                                                $items = json_decode($repairIssue->workOrder->items, true);
                                            @endphp
                                        
                                            @if(is_array($items) && count($items) > 0)
                                                <div class="row">
                                                    @foreach($items as $item)
                                                        <div class="col-md-3">
                                                            <div class="card mb-3">
                                                                <div class="card-body">
                                                                    <h5 class="card-title">{{ $item['title'] ?? 'N/A' }}</h5>
                                                                    <p class="card-text"><strong>Description:</strong> {{ $item['description'] ?? 'N/A' }}</p>
                                                                    <p><strong>Unit Price:</strong> ${{ isset($item['unit_price']) ? number_format($item['unit_price'], 2) : '0.00' }}</p>
                                                                    <p><strong>Quantity:</strong> {{ $item['quantity'] ?? '0' }}</p>
                                                                    <p><strong>Tax Rate:</strong> {{ isset($item['tax_rate']) ? $item['tax_rate'] . '%' : 'N/A' }}</p>
                                                                    <p><strong>Total Price:</strong> ${{ isset($item['total_price']) ? number_format($item['total_price'], 2) : '0.00' }}</p>
                                                                    {{-- <p><strong>Created At:</strong> 
                                                                        @if(!empty($item['created_at']))
                                                                            {{ \Carbon\Carbon::parse($item['created_at'])->format('d-m-Y H:i') }}
                                                                        @else
                                                                            N/A
                                                                        @endif
                                                                    </p> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p>No job scope items available.</p>
                                            @endif
                                        @else
                                            <p>Job scope data is not available or invalid.</p>
                                        @endif
                                    </p>
                            
                                    <p><strong>Tentative Start Date:</strong> {{ formatDate($repairIssue->workOrder->tentative_start_date ?? '') }}</p>
                                    <p><strong>Tentative End Date:</strong> {{ formatDate($repairIssue->workOrder->tentative_end_date ?? '') }}</p>
                                    <p><strong>Booked Date:</strong> {{ formatDate($repairIssue->workOrder->booked_date ?? '') }}</p>
                                    <p><strong>Invoice To:</strong> {{ $repairIssue->workOrder->invoice_to }}</p>
                                    <p><strong>Payment Terms:</strong> {{ $repairIssue->workOrder->payment_by }}</p>
                                {{-- </div>
                            
                                <div class="col-3"> --}}
                                
                                    {{-- <p><strong>Estimated Cost:</strong> {{getPoundSymbol()}}{{ number_format($repairIssue->workOrder->estimated_cost, 2) }}</p>
                                    <p><strong>Actual Cost:</strong> {{getPoundSymbol()}}{{ number_format($repairIssue->workOrder->actual_cost, 2) }}</p>
                                    <p><strong>Charge to Landlord:</strong> {{getPoundSymbol()}}{{ number_format($repairIssue->workOrder->charge_to_landlord, 2) }}</p> --}}
                            
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-success">{{ $repairIssue->workOrder->status }}</span>
                                    </p>
                            
                                    {{-- <p><strong>Date And Time:</strong> {{ formatDateTime($repairIssue->workOrder->date_time ?? '') }}</p> --}}
                                    <p><strong>Extra Notes:</strong> {{ $repairIssue->workOrder->extra_notes }}</p>
                            
                                    <!-- Quote Attachment (if available) -->
                                    @if($repairIssue->workOrder->quote_attachment)
                                        <p><strong>Quote Attachment:</strong> 
                                            <a href="{{ asset('storage/' . optional($repairIssue->workOrder->quoteAttachment)->file_name) }}" 
                                                target="_blank" 
                                                class="btn btn-sm btn-secondary">
                                                View File
                                            </a>
                                        </p>
                                    @endif
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </div>        
        @else
            {{-- <p class="text-danger">No work order found for this issue.</p> --}}
        @endif

        
        <button type="submit" class="float-end btn btn-primary">Update</button>
    </form>

    <a href="{{ route('admin.property_repairs.index') }}" class="float-start btn btn-secondary">Back to List</a>
</div>
    
</div>
@endsection
{{-- Include the partial to push Select2 assets into the stacks --}}
@include('backend.partials.assets.select2')
@section('page.scripts')
<script>
    // Embedding saved contractor assignments from the controller.
    // Each contractor assignment should have at least: contractor_id, cost_price, contractor_preferred_availability.
    var savedContractorAssignments = {!! json_encode($contractorAssignments->toArray()) !!};
    // console.log("Saved Contractor Assignments:", savedContractorAssignments);


    $(document).ready(function () {       
        function loadJobSubTypes(jobTypeId, selectedSubTypeId = null) {
            if (!jobTypeId) {
                $("#jobSubTypeSelect").html('<option disabled value="">Select Job Sub Type</option>');
                return;
            }

            var url = "{{ route('admin.job_types.getSubCategories', ':id') }}".replace(':id', jobTypeId);

            $("#jobSubTypeSelect").html('<option value="">Loading...</option>');

            $.ajax({
                url: url,
                type: "GET",
                success: function (response) {
                    $("#jobSubTypeSelect").html('<option disabled value="">Select Job Sub Type</option>');
                    $.each(response, function (key, value) {
                        let selected = selectedSubTypeId == value.id ? 'selected' : '';
                        $("#jobSubTypeSelect").append(`<option value="${value.id}" ${selected}>${value.name}</option>`);
                    });
                },
                error: function () {
                    $("#jobSubTypeSelect").html('<option disabled value="">No Sub Types Found</option>');
                }
            });
        }

        // When Job Type changes
        $(document).on("change", "#jobTypeSelect", function () {
            var jobTypeId = $(this).val();
            loadJobSubTypes(jobTypeId);
        });

        // Auto-select job sub-type when editing
        var existingJobTypeId = $("#jobTypeSelect").val();
        var existingJobSubTypeId = "{{ $repairIssue->workOrder->job_sub_type_id ?? '' }}";
        if (existingJobTypeId) {
            loadJobSubTypes(existingJobTypeId, existingJobSubTypeId);
        }
        
        // $(document).on('change', '#jobTypeSelect', function () {
        //     var jobTypeId = $(this).val();
        //     var url = "{{ route('admin.job_types.getSubCategories', ':id') }}"; 
        //     url = url.replace(':id', jobTypeId);

        //     $('#jobSubTypeSelect').html('<option value="">Loading...</option>');

        //     $.ajax({
        //         url: url,
        //         type: 'GET',
        //         success: function (response) {
        //             $('#jobSubTypeSelect').html('<option disabled value="">Select Job Sub Type</option>');
        //             $.each(response, function (key, value) {
        //                 $('#jobSubTypeSelect').append('<option value="' + value.id + '">' + value.name + '</option>');
        //             });
        //         },
        //         error: function () {
        //             $('#jobSubTypeSelect').html('<option disabled value="">No Sub Types Found</option>');
        //         }
        //     });
        // });
        
       

        $(document).on("click", "#generateInvoiceBtn", function () {
            let workOrderId = $("#work_order_id").val();

            // Build the URL using the named route and replace the placeholder with the work order ID
            var url = "{{ route('admin.invoices.generate', ['workOrderId' => 'id']) }}".replace('id', workOrderId);

            
            if (!workOrderId) {
                alert("No Work Order found!");
                return;
            }

            $.ajax({
                url: url,
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                },
                success: function (response) {
                    alert(response.message);

                    // Freeze the form
                    $("#workOrderForm :input").prop("disabled", true);
                    $("#generateInvoiceBtn").text("Invoice Generated").prop("disabled", true);
                },
                error: function (xhr) {
                    alert(xhr.responseJSON.message);
                }
            });
        });

        function loadInvoiceToDetails(invoiceTo, propertyId) {
            let roleId = null;
            $("#userDetails").hide();
            let existingInvoiceToId = $("#existingInvoiceToId").val(); // Get preselected invoice_to_id
            // console.log('Existing ID: ' + existingInvoiceToId);

            if (invoiceTo === "Landlord") {
                roleId = 5; // Landlord
                var endpoint = "{{ route('admin.getUsersByProperty', ['propertyId' => 'PROPERTYID', 'roleId' => 'ROLEID']) }}";
                endpoint = endpoint.replace('PROPERTYID', propertyId).replace('ROLEID', roleId);
            } else if (invoiceTo === "Tenant") {
                var endpoint = "{{ route('admin.getTenantsByProperty', ['propertyId' => 'PROPERTYID']) }}";
                endpoint = endpoint.replace('PROPERTYID', propertyId);
            } else {
                $("#invoiceToContainer").html('');
                $("#userDetails").hide();
                return;
            }

            $("#invoiceToContainer").html('<select class="form-control"><option>Loading...</option></select>');

            $.ajax({
                url: endpoint,
                type: 'GET',
                success: function (response) {
                    var dropdown = '<div class="form-group">';
                    dropdown += '<label class="form-label">Select ' + invoiceTo + '</label>';
                    dropdown += '<select name="invoice_to_id" id="invoiceToSelect" class="form-control">';
                    dropdown += '<option value="">Select ' + invoiceTo + '</option>';

                    $.each(response, function (index, item) {
                        let isSelected = parseInt(item.id) === parseInt(existingInvoiceToId) ? 'selected' : '';
                        // console.log('Checking ID: ' + item.id + ', Selected: ' + isSelected);

                        dropdown += `<option value="${item.id}" ${isSelected} 
                                    data-email="${item.email}" 
                                    data-phone="${item.phone}" 
                                    data-name="${item.name}" 
                                    data-address="${item.full_address || ''}">
                                    ${item.name}
                                    </option>`;
                    });

                    dropdown += '</select></div>';
                    $("#invoiceToContainer").html(dropdown);

                    if (existingInvoiceToId) {
                        $("#invoiceToSelect").val(existingInvoiceToId).trigger("change");
                        // console.log("Final selection applied: " + $("#invoiceToSelect").val());
                    }
                },
                error: function () {
                    $("#invoiceToContainer").html('<p class="text-danger">Unable to load details</p>');
                }
            });
        }

        var selectedInvoiceTo = $("input[name='invoice_to']:checked").val();
        var propertyId = $("#property_id").val();

        // Listen for changes on the Invoice To radio buttons
        $(document).on("change", "input[name='invoice_to']", function () {
            var invoiceTo = $(this).val();
            var propertyId = $("#property_id").val();
            loadInvoiceToDetails(invoiceTo, propertyId);
            updateStatusOptions(invoiceTo);
        });

        // Preload if Landlord or Tenant is already selected
        if (selectedInvoiceTo) {
            loadInvoiceToDetails(selectedInvoiceTo, propertyId);
            updateStatusOptions(selectedInvoiceTo);
        }

        // Show User Details When a User is Selected
        $(document).on("change", "#invoiceToSelect", function () {
            var selectedOption = $(this).find(':selected');
            // var name = selectedOption.data('name');
            var address = selectedOption.data('address');
            var phone = selectedOption.data('phone');
            var email = selectedOption.data('email');

            if (address) {
                $('#userAddress').text(address);
                $('#userPhone').text(phone);
                $('#userEmail').text(email);
                $('#userDetails').show();
            } else {
                $('#userDetails').hide();
            }
        });

        function updateStatusOptions(invoiceTo) {
            let statusOptions = [];
            if (invoiceTo === "Company") {
                statusOptions = [
                    { value: "Raised", text: "Raised" },
                    { value: "Sent to Contractor", text: "Sent to Contractor" },
                    { value: "Completed", text: "Completed" },
                    { value: "Cancelled", text: "Cancelled" }
                ];
            } else if (invoiceTo === "Landlord" || invoiceTo === "Tenant") {
                statusOptions = [
                    { value: "Raised", text: "Raised" },
                    { value: "Sent to Contractor", text: "Sent to Contractor" },
                    { value: "Work Completed - Invoice Received From Contractor", text: "Work Completed - Invoice Received From Contractor" },
                    { value: "Work Completed - Invoice Generated to Landlord", text: "Work Completed - Invoice Generated to Landlord (If landlord paying)" },
                    { value: "Work Completed - Invoice Generated to Tenant", text: "Work Completed - Invoice Generated to Tenant (If tenant paying)" },
                    { value: "Completed - Invoice Generated", text: "Completed - Invoice Generated (to Landlord-Tenant)" },
                    { value: "Work Completed - Invoice Paid To Contractor", text: "Work Completed - Invoice Paid To Contractor" },
                    { value: "Cancelled", text: "Cancelled" }
                ];
            }

            // Populate status dropdown
            let statusDropdown = $("#statusSelect");
            statusDropdown.html(""); // Clear existing options

            $.each(statusOptions, function (index, option) {
                statusDropdown.append(new Option(option.text, option.value));
            });

            // ✅ Ensure existing status is preselected
            let existingStatus = $("#existingStatus").val();
            if (existingStatus) {
                statusDropdown.val(existingStatus);
            }

            // console.log("Updated Status Options for:", invoiceTo);
        }

        // Listen for changes on the Invoice To radio buttons
        $(document).on("change", "input[name='invoice_to']", function () {
            loadInvoiceToDetails($(this).val(), $("#property_id").val());
            updateStatusOptions($(this).val());
        });
        
    });


</script>
<script>
    $(document).ready(function () {
        // Global variables for category selection (for edit mode)
        let currentLevel = 1;
        let selectedCategories = {}; // Will hold new selection: e.g., { "level_1": "5", "level_2": "34" }
        let breadcrumbItems = [];
        let allCategories = {}; // This should be loaded from your categories API or embedded in the page
        let initialselectedCategories = JSON.parse($('#selected_categories').val()) ?? '' ; // Might be empty initially
        let initiallastSelectedCategory = $('#last_selected_category').val() ?? ''; // Might be empty initially
        let initialTenantId = $('#selected_tenant').val() ?? '';

        // Preload all categories via AJAX (similar to create form)
        $.ajax({
            url: "{{ route('admin.get.repair.categories') }}",
            method: 'GET',
            async: false,
            success: function(data) {
                allCategories = data;
                // console.log("All Categories Loaded:", allCategories);
            },
            error: function() {
                // console.log("Error fetching categories.");
            }
        });

        // ----------------- PROPERTY SELECTION ----------------- //

        // Store the initial selected properties value to restore it in case of cancellation
        const initialSelectedProperties = JSON.parse($('#selected_properties').val());
        initialPropertyId = initialSelectedProperties;
        // Check if there are selected properties
        if (initialSelectedProperties && initialSelectedProperties.length > 0) {
            // Show the "Change Property" button and dynamic table with selected properties
            $('#change_property_button').removeClass('d-none');
            $('#dynamic_property_table').removeClass('d-none');
            // searchPropertiesByIds(initialSelectedProperties);
        }

        // Show the search bar and hide the dynamic property table when "Change Property" button is clicked
        $(document).on('click','#change_property_button', function(e) {
            e.preventDefault();
            // Save the current property and tenant as the "previous" selection.
            window.previousTenantId = $('#tenant-select').val();
            // console.log('Previous Tenant ID:', window.previousTenantId);
            $('#dynamic_property_table').addClass('d-none'); // Hide the selected property table
            $('#search_property_section').show(); // Show the search input
            $('#cancel_property_change').removeClass('d-none'); // Show the search input

            // Optionally reset the search field
            $('#search_property1').val('');

            // Hide the "Change Property" button after switching to search mode
            // $(this).hide();
            $(this).addClass('d-none');

            // Clear the selected properties hidden field to allow new selection
            $('#selected_properties').val('[]');
        });

        // When the user cancels the property change, restore the previous ID and hide the search bar
        $(document).on('click', '#cancel_property_change', function(e) {
            e.preventDefault();
            // Restore the initial selected properties value
            $('#selected_properties').val(JSON.stringify(initialSelectedProperties));

            // Hide the search section and show the dynamic property table again
            $('#search_property_section').hide();
            $('#dynamic_property_table').removeClass('d-none');

            // Optionally, you can re-populate the dynamic property table
            searchPropertiesByIds(initialSelectedProperties);

            // Re-fetch the tenants for the previous property.
            fetchTenants(initialPropertyId, function() {
                // Restore the previously selected tenant, if any.
                $('#tenant-select').val(window.previousTenantId).trigger('change');
            });

            // Show the "Change Property" button again
            // $('#change_property_button').show();
            $(this).addClass('d-none');
            $('#change_property_button').removeClass('d-none');
        });

        // Function to initialize the selected properties when at step 3
        function initSelectedProperties() {
            const selectedProperties_f = JSON.parse($('#selected_properties').val()); // Assuming selected properties are stored in a hidden field
            if (selectedProperties_f) {
                // console.log('Selected Properties:', selectedProperties_f);
                searchPropertiesByIds(selectedProperties_f); // Call the function to fetch and display selected properties
            }
        }

        // Function to fetch property details by IDs .. <td><button class="btn btn-danger remove-btn">Remove</button></td>
        function searchPropertiesByIds(propertyIds) {
            $.ajax({
                url: '{{ route('admin.users.properties.search') }}',
                method: 'GET',
                data: { ids: propertyIds },
                success: function(response) {
                    $('#dynamic_property_table tbody').empty();
                    response.forEach(function(property) {
                        if ($('#dynamic_property_table tbody tr[data-id="' + property.id + '"]').length === 0) {  // Prevent duplicates
                            var address = property.address || 'N/A';
                            var propRefNo = property.prop_ref_no || 'N/A';
                            var propName = property.prop_name || 'N/A';
                            var newRow = `<tr data-id="${property.id}">
                                            <td>${address} - ${propRefNo} - ${propName}</td>
                                            <td>${property.type}</td>
                                            <td>${property.availability}</td>
                                        </tr>`;
                            $('#dynamic_property_table tbody').append(newRow);
                        }
                    });
                    updateSelectedProperties();
                    $('#dynamic_property_table').removeClass('d-none');
                },
                error: function() {
                    toastr.error('Error fetching properties by IDs.', 'Error');
                }
            });
        }

        initSelectedProperties();
        
        // // Function to get the selected property address from the table
        // function getSelectedPropertyAddress() {
        //     var selectedProperty = $('#dynamic_property_table tbody tr:first td:first').text(); // Get first row's address column
        //     return selectedProperty ? selectedProperty.trim() : 'N/A';
        // }

        // // When the Work Order modal is opened, set the property address
        // $(document).on('click', '#workOrderModal', function () {
        //     var propertyAddress = getSelectedPropertyAddress();
        //     $('.set-property-address').text(propertyAddress); // Set address in modal
        // });

        $(document).on('keyup keydown', '#search_property1', function () {
            var query = $(this).val().trim();
            if (query.length >= 3) {
                searchProperties(query);
                $('#error_message').hide();
            } else {
                $('#property_results').empty();
                $('#error_message').text('Please enter at least 3 characters to search.').show();
            }
        });

        function searchProperties(query) {
            $.ajax({
                url: '{{ route('properties.search') }}',
                method: 'GET',
                data: { query: query },
                success: function (response) {
                    $('#property_results').empty();
                    if (response.length > 0) {
                        response.forEach(function (property) {
                            if (!$('#property_results li[data-id="' + property.id + '"]').length) {
                                appendPropertyToResults(property);
                            }
                        });
                    } else {
                        $('#property_results').append('<li class="list-group-item">No properties found.</li>');
                    }
                },
                error: function () {
                    $('#property_results').append('<li class="list-group-item">Error fetching results.</li>');
                }
            });
        }

        function appendPropertyToResults(property) {
            var address = property.address || 'N/A';
            var propRefNo = property.prop_ref_no || 'N/A';
            var propName = property.prop_name || 'N/A';
            var listItem = `<li class="list-group-item property-result" data-id="${property.id}" data-type="${property.type}" data-availability="${property.availability}">
                                ${address} - ${propRefNo} - ${propName}
                            </li>`;
            $('#property_results').append(listItem);
        }

        $(document).on('click', '.property-result', function () {
            selectedProperty = $(this).data("id");
            $('#dynamic_property_table tbody').empty();
            $('#dynamic_property_table').removeClass('d-none');
            var propertyId = $(this).data('id');
            if ($('#dynamic_property_table tbody tr[data-id="' + propertyId + '"]').length === 0) {
                var newRow = `<tr data-id="${propertyId}">
                                <td>${$(this).text()}</td>
                                <td>${$(this).data('type')}</td>
                                <td>${$(this).data('availability')}</td>
                                <td><button class="btn btn-danger remove-btn">Remove</button></td>
                            </tr>`;
                $('#dynamic_property_table tbody').append(newRow);
                updateSelectedProperties();
            }
            $('#property_results').empty();
            $('#search_property1').val('');

            // Fetch the new tenants for this property.
            fetchTenants(selectedProperty, function() {
                // Clear the tenant preview, because tenant selection may change.
                $('#tenant-preview').html('');
            });
        });

        function updateSelectedProperties() {
            var selectedPropertyIds = [];
            $('#dynamic_property_table tbody tr').each(function () {
                selectedPropertyIds.push($(this).data('id'));
            });
            $('#selected_properties').val(JSON.stringify(selectedPropertyIds));
        }

        $(document).on('click', '.remove-btn', function () {
            $(this).closest('tr').remove();
            updateSelectedProperties();
            if ($('#dynamic_property_table tbody tr').length < 1) {
                $('#dynamic_property_table').addClass('d-none');
                disableNextButton();
            }
        });

        // // Fetch tenants for the given property.
        // if (initialSelectedProperties) {
        //     $.ajax({
        //         url: "{{ route('admin.get.property_repairs.tenants') }}",
        //         method: "GET",
        //         data: { property_id: initialSelectedProperties },
        //         success: function(data) {
        //             let options = '<option value="">-- Select Tenant --</option>';
        //             $.each(data, function(i, tenant) {
        //                 // Add tenant details as data attributes.
        //                 options += `<option value="${tenant.id}"
        //                             data-email="${tenant.email}"
        //                             data-phone="${tenant.phone}"
        //                             data-address="${tenant.address}">${tenant.full_name}</option>`;
        //             });
        //             $('#tenant-select').html(options);
        //         },
        //         error: function() {
        //             console.log("Error fetching tenants.");
        //         }
        //     });
        // }


        // Function to fetch tenants for a given property and update the dropdown
        function fetchTenants(propertyId, callback) {
            $.ajax({
                url: "{{ route('admin.get.property_repairs.tenants') }}",
                method: "GET",
                data: { property_id: propertyId },
                success: function(data) {
                    let options = '<option value="">-- Select Tenant --</option>';
                    $.each(data, function(i, tenant) {
                        options += `<option value="${tenant.id}"
                            data-email="${tenant.email}"
                            data-phone="${tenant.phone}"
                            data-address="${tenant.address}">${tenant.name}</option>`;
                    });
                    $('#tenant-select').html(options);
                    if (typeof callback === 'function') {
                        callback();
                    }
                },
                error: function() {
                    // console.log("Error fetching tenants.");
                }
            });
        }

        // On page load, fetch tenants for the initial property.
        fetchTenants(initialPropertyId, function() {
            // If an initial tenant was selected, preselect it in the dropdown.
            if (initialTenantId) {
                $('#tenant-select').val(initialTenantId).trigger('change');
            }
        });

        // When a tenant is selected, update the preview area.
        $('#tenant-select').on('change', function() {
            let selected = $(this).find('option:selected');
            if (selected.val() === "") {
                $('#tenant-preview').html('');
                return;
            }
            let details = `
                <p><strong>Name:</strong> ${selected.text()}</p>
                <p><strong>Email:</strong> ${selected.data('email') || 'N/A'}</p>
                <p><strong>Phone:</strong> ${selected.data('phone') || 'N/A'}</p>
            `;
            $('#tenant-preview').html(details);
        });




        // Updated hidden inputs updater
        function updateHiddenInputs() {
            $('#selected_categories').val(JSON.stringify(selectedCategories));
            let deepest = Object.keys(selectedCategories).length;
            if (deepest === currentLevel) {
                $('#last_selected_category').val(selectedCategories[`level_${currentLevel}`] || '');
            } else {
                $('#last_selected_category').val('');
            }
        }
        // Call updateHiddenInputs() after every change:
        updateHiddenInputs();

        // Initialize breadcrumb (you can prepopulate breadcrumb if needed)
        function updateBreadcrumbUI(){
            let html = '';
            breadcrumbItems.forEach((item, index) => {
                if (index < breadcrumbItems.length - 1) {
                    html += `<li class="breadcrumb-item clickable" data-index="${index}" style="cursor:pointer;">${item}</li>`;
                } else {
                    html += `<li class="breadcrumb-item active" aria-current="page" data-index="${index}">${item}</li>`;
                }
            });
            $('ol.breadcrumb').html(html);
        }

        function resetBreadcrumb() {
            breadcrumbItems = ['Select Category'];
            updateBreadcrumbUI();
        }
        resetBreadcrumb();

        function restoreOriginalCategory() {
            // Retrieve the original category selections stored in the hidden input.
            let originalCategories = $('#selected_categories_old').val();
            let originalCategoryId = $('#last_selected_category_old').val();

            try {
                originalCategories = JSON.parse(originalCategories);
            } catch (err) {
                originalCategories = {};
            }

            // Restore the selectedCategories object.
            selectedCategories = originalCategories;

            // Reset currentLevel based on the original selection.
            let levels = Object.keys(selectedCategories).map(k => parseInt(k.replace('level_', '')));
            currentLevel = levels.length ? Math.max(...levels) : 1;

            // Pre-check the radio buttons for each level using the restored values.
            $.each(selectedCategories, function(key, value) {
                let lvl = key.replace('level_', '');
                $(`.category-level[data-level="${lvl}"] input[type="radio"][value="${value}"]`).prop('checked', true);
            });

            // Update hidden inputs with the restored values.
            updateHiddenInputs();

            // Hide the category edit card and show the display card.
            $('#category-edit-card').addClass('d-none');
            $('#category-display-card').removeClass('d-none');

            // Optionally, update the breadcrumb in the display card if needed.
        }


        // Category selection navigation buttons (Next and Previous)
        $(document).on('click', '#category-next-btn', function(){
            // Process current selection in the visible level
            let visibleLevel = $(`.category-level[data-level="${currentLevel}"]`);
            let selectedRadio = visibleLevel.find('input[type="radio"]:checked');
            if (!selectedRadio.length) return;
            let selectedValue = selectedRadio.val();
            let selectedName = selectedRadio.siblings('label').text().trim();
            selectedCategories[`level_${currentLevel}`] = selectedValue;
            // Update breadcrumb for this level:
            breadcrumbItems = breadcrumbItems.slice(0, currentLevel);
            breadcrumbItems.push(selectedName);
            updateBreadcrumbUI();

            // Check for children categories using our preloaded allCategories:
            let children = allCategories[selectedValue] || [];
            if (children.length > 0) {
                let nextLevel = currentLevel + 1;
                let nextLevelContainer = $(`[data-level="${nextLevel}"]`);
                let html = '';
                children.forEach(function(category) {
                    html += `
                        <div class="col-md-4 mb-2">
                            <div class="form-check d-flex align-items-center">
                                <input class="form-check-input" type="radio" name="category_${nextLevel}" id="category-${category.id}" value="${category.id}">
                                <label class="form-check-label d-flex align-items-center" for="category-${category.id}">
                                    <i class="fas fa-cogs me-2"></i> ${category.name}
                                </label>
                            </div>
                        </div>
                    `;
                });
                nextLevelContainer.find('.row').html(html);
                visibleLevel.hide();
                nextLevelContainer.show();
                currentLevel++;
                disableCategoryNextBtn();
            } else {
                // No further children: final selection is made
                // Hide the category edit card and update hidden fields
                updateHiddenInputs();
                // Optionally, you can automatically hide the edit interface
                // and update the display card.
            }
            updateHiddenInputs();
        });
        $(document).on('click', '#category-prev-btn', function(){
            if (currentLevel > 1) {
                $(`[data-level="${currentLevel}"]`).hide().find('.row').empty();
                delete selectedCategories[`level_${currentLevel}`];
                currentLevel--;
                // console.log(currentLevel);

                    delete selectedCategories[`level_${currentLevel}`];
                    // console.log(selectedCategories);

                // Uncheck all radio buttons in the current level before removing the selection.
                $(`[data-level="${currentLevel}"] input[type="radio"]`).prop('checked', false);
                $(`[data-level="${currentLevel}"]`).show();
                breadcrumbItems.pop();
                updateBreadcrumbUI();
                enableCategoryNextBtn();
            } else {


                // At level 1: cancel category change.
                // Optionally, hide the category edit card and show the display card.
                $('#category-edit-card').addClass('d-none');
                $('#category-display-card').removeClass('d-none');
            }
            updateHiddenInputs();
        });

        $(document).on('change', '.category-level input[type="radio"]', function(){
            let level = $(this).closest('.category-level').data('level');
            let selectedValue = $(this).val();
            // Update the selectedCategories for the current level
            selectedCategories[`level_${level}`] = selectedValue;
            updateHiddenInputs();
            // Check if this selection has children in allCategories
            let children = allCategories[selectedValue] || [];
            if(children.length > 0){
                // Automatically trigger the next button click if not already in the next level.
                if (currentLevel === level) {
                    // console.log('Triggering next button click...');
                    enableCategoryNextBtn();
                    $('#category-next-btn').click();
                    disableCategoryNextBtn();
                    // console.log('Next button clicked.');
                }
            } else {
                // No children; just update hidden inputs and breadcrumb.
                // Optionally, you might want to disable the next button if no further children exist.
                disableCategoryNextBtn();
                updateHiddenInputs();
            }
        });

        function disableCategoryNextBtn() {
            $('#category-next-btn').prop('disabled', true);
        }
        function enableCategoryNextBtn() {
            $('#category-next-btn').prop('disabled', false);
        }
        // Enable the next button if a radio button is selected in the current level.
        $(document).on('change', '.category-level input[type="radio"]', function(){
            // enableCategoryNextBtn();
            updateHiddenInputs();
        });

        // ---- CANCEL CATEGORY CHANGE HANDLING ---- //
        // When the user clicks a cancel button (assume it has ID #cancel-category-btn),
        // restore the original category selection.
        $('#cancel-category-btn').on('click', function(e) {
            e.preventDefault();
            restoreOriginalCategory();
            $('#change-category-btn').removeClass('d-none');
        });

        // Toggle between read-only display and editable category selection.
        $('#change-category-btn').on('click', function () {
            // Hide the display card and show the edit card.
            $('#category-display-card').addClass('d-none');
            $('#category-edit-card').removeClass('d-none');
            $(this).addClass('d-none');
            $('#cancel-category-btn').removeClass('d-none');
        });

        // $('ol.breadcrumb').on('click', 'li.clickable', function () {
        //     let index = $(this).data('index');
        //     if (index === 0) {
        //         // Go back to step 1 (or reset to the first category level)
        //         currentLevel = 1;
        //         breadcrumbItems = breadcrumbItems.slice(0, 1);
        //         updateBreadcrumbUI();
        //         $(".category-level").hide();
        //         $(`[data-level="1"]`).show();
        //     } else {
        //         // Go back to the clicked level
        //         // Remove selections beyond the clicked level
        //         for (let lvl = index + 1; lvl <= currentLevel; lvl++) {
        //             delete selectedCategories[`level_${lvl}`];
        //         }
        //         currentLevel = index;
        //         breadcrumbItems = breadcrumbItems.slice(0, index + 1);
        //         updateBreadcrumbUI();
        //         $(".category-level").hide();
        //         $(`[data-level="${currentLevel}"]`).show();
        //     }
        //     updateHiddenInputs();
        // });
    });

    initSelect2('.select2');

    $(document).ready(function () {

        // ----- Initialize jQuery Validation on the Form First -----
        let validator = $("#repair-form-page").validate({
            rules: {
                description: { required: true },
                priority: { required: true },
                status: { required: true },
                estimated_price: { required: true, number: true },
                vat_type: { required: true },
                tenant_availability: { required: true },
                access_details: { required: true },
                tenant_id: { required: true },
                "property_managers[]": { required: true }
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element.closest('.form-group'));
            },
            highlight: function(element, errorClass, validClass) {
                if ($(element).is('select') && !$(element).hasClass('select2-hidden-accessible')) {
                    $(element).addClass('border-danger');
                } else if ($(element).is('select') && $(element).hasClass('select2-hidden-accessible')) {
                    $(element).next('.select2-container').find('.select2-selection').addClass('border-danger');
                } else {
                    $(element).closest('.form-group').find('select, input, textarea').addClass('border-danger');
                }
                $(element).addClass(errorClass).removeClass(validClass);
            },
            unhighlight: function(element, errorClass, validClass) {
                if ($(element).is('select') && !$(element).hasClass('select2-hidden-accessible')) {
                    $(element).removeClass('border-danger');
                } else if ($(element).is('select') && $(element).hasClass('select2-hidden-accessible')) {
                    $(element).next('.select2-container').find('.select2-selection').removeClass('border-danger');
                } else {
                    $(element).closest('.form-group').find('select, input, textarea').removeClass('border-danger');
                }
                $(element).removeClass(errorClass).addClass(validClass);
            },
            invalidHandler: function(event, validator) {
                let errors = validator.numberOfInvalids();
                if (errors) {
                    AIZ.plugins.notify('error', 'Please fill out all required fields.');
                }
            },
            submitHandler: function(form) {
                form.submit();
            }
        });

        // ----- Contractor Assignment Template as a JavaScript String -----
        // const contractorAssignmentTemplate = `
        //     <div class="contractor-assignment mb-3 border p-3">
        //         <!-- Index badge will be prepended dynamically -->
        //         <button type="button" class="btn btn-danger btn-sm remove-contractor-assignment" style="float: right;">Remove</button>
        //         <input type="hidden" name="contractor_assignments[__index__][id]" value="">
        //         <div class="form-group">
        //             <label>Contractor</label>
        //             <select name="contractor_assignments[__index__][contractor_id]" class="form-control contractor-id">
        //                 @foreach($contractors as $contractor)
        //                     <option value="{{ $contractor->id }}">{{ $contractor->full_name }} ({{ $contractor->email }})</option>
        //                 @endforeach
        //             </select>
        //         </div>
        //         <div class="form-group">
        //             <label for="cost_price___index__">Cost Price</label>
        //             <input type="number" step="0.01" name="contractor_assignments[__index__][cost_price]" id="cost_price___index__" class="form-control cost-price">
        //         </div>
        //         <div class="form-group">
        //             <label for="quote_attachment___index__">Quote Price Document</label>
        //             <input type="file" name="contractor_assignments[__index__][quote_attachment]" id="quote_attachment___index__" class="form-control quote-attachment">
        //         </div>
        //         <div class="form-group">
        //             <label for="contractor_preferred_availability___index__">Preferred Availability (Contractor)</label>
        //             <input type="datetime-local" name="contractor_assignments[__index__][contractor_preferred_availability]" id="contractor_preferred_availability___index__" class="form-control contractor-availability">
        //         </div>
        //     </div>
        // `;
        const contractorAssignmentTemplate = `
            <div class="contractor-assignment mb-3 border p-3">
                <!-- Index badge will be prepended dynamically -->
                <button type="button" class="btn btn-danger btn-sm remove-contractor-assignment" style="float: right;">Remove</button>
                <input type="hidden" name="contractor_assignments[__index__][id]" value="">
                <div class="form-group">
                    <label>Contractor</label>
                    <select name="contractor_assignments[__index__][contractor_id]" class="form-control contractor-id">
                        @foreach($contractors as $contractor)
                            <option value="{{ $contractor->id }}">{{ $contractor->name }} ({{ $contractor->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="cost_price___index__">Cost Price</label>
                    <input type="number" step="0.01" name="contractor_assignments[__index__][cost_price]" id="cost_price___index__" class="form-control cost-price">
                </div>
                <div class="form-group rs_upload_btn">
                    <h5 class="sub_title mt-4">Upload Quote Price Document</h5>
                    <div class="media_wrapper">
                        <div class="input-group" data-toggle="aizuploader" data-type="document" data-multiple="false">
                            <label for="quote_attachment___index__">Select Document</label>
                            <div class="d-none input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                            </div>
                            <div class="d-none form-control file-amount">Choose File</div>
                            <input type="hidden" name="contractor_assignments[__index__][quote_attachment]" id="quote_attachment___index__" value="" class="selected-files quote_attachment">
                        </div>
                        <div class="d-flex gap-3 file-preview box sm"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="contractor_preferred_availability___index__">Preferred Availability (Contractor)</label>
                    <input type="datetime-local" name="contractor_assignments[__index__][contractor_preferred_availability]" id="contractor_preferred_availability___index__" class="form-control contractor-availability">
                </div>
            </div>
        `;

        // ----- Function to update index badges for each contractor assignment block -----
        function updateContractorIndexes() {
            $('#contractor-assignments .contractor-assignment').each(function (index) {
                let $assignment = $(this);
                $assignment.find('.assignment-index').remove();
                $assignment.prepend(`<div class="assignment-index badge bg-secondary mb-2">#${index + 1}</div>`);
            });
            if ($('#contractor-assignments .contractor-assignment').length === 1) {
                $('#contractor-assignments .contractor-assignment .remove-contractor-assignment').hide();
            } else {
                $('#contractor-assignments .contractor-assignment .remove-contractor-assignment').show();
            }
        }

        // ----- Function to add a new contractor assignment block (for new entries) -----
        function addContractorAssignment(index) {
            let newHtml = contractorAssignmentTemplate.replace(/__index__/g, index);
            let $newAssignment = $(newHtml);
            $('#contractor-assignments').append($newAssignment);
            updateContractorIndexes();
            attachValidationRules($newAssignment);
        }

        // ----- Function to attach validation rules to dynamic contractor assignment fields -----
        function attachValidationRules($assignment) {
            let validatorInstance = $("#repair-form-page").data("validator");
            if (!validatorInstance) {
                console.error("Validator instance not found!");
                return;
            }
            $assignment.find('.contractor-id').rules('add', {
                required: true,
                messages: { required: "Please select a contractor" }
            });
            $assignment.find('.cost-price').rules('add', {
                required: true,
                number: true,
                messages: { required: "Please enter a cost price", number: "Enter a valid number" }
            });
            $assignment.find('.contractor-availability').rules('add', {
                required: true,
                messages: { required: "Please select preferred availability" }
            });
            // Note: File input cannot be pre-filled or validated as required if already saved.
            // $assignment.find('select, input').each(function() {
            //     $(this).valid();
            // });
        }

        // ----- Function to add a saved contractor assignment block (for editing existing data) -----
        function addSavedContractorAssignment(index, data) {
            // Clone the template and replace placeholder.
            let newHtml = contractorAssignmentTemplate.replace(/__index__/g, index);
            let $assignment = $(newHtml);

            // Pre-fill the fields with saved data.

            // Pre-fill the hidden id field with the saved assignment ID.
            $assignment.find('input[name="contractor_assignments['+ index +'][id]"]').val(data.id);

            // For contractor_id, set the selected option.
            $assignment.find('.contractor-id').val(data.contractor_id);
            // For cost price.
            $assignment.find('.cost-price').val(data.cost_price);
            // For preferred availability.
            $assignment.find('.contractor-availability').val(data.contractor_preferred_availability);
            if(data.quote_attachment) {
                $assignment.find('.quote_attachment').val(data.quote_attachment);
            }
            // Note: For file input (quote_attachment), we cannot set a value. Instead, you might display the saved file name in a separate element if needed.
            // Optionally, set a hidden field with the existing file path if you want to keep it.
            // if(data.quote_attachment) {
            //     // Optionally, show a link or text with the file name.
            //     $assignment.append(`<p>Existing File: ${data.quote_attachment}</p>`);
            //     // Also, you might want to add a hidden field:
            //     $assignment.append(`<input type="hidden" name="contractor_assignments[${index}][existing_quote_attachment]" value="${data.quote_attachment}">`);
            // }

            // Append the saved assignment block.
            $('#contractor-assignments').append($assignment);
            updateContractorIndexes();
            // Attach dynamic validation rules.
            attachValidationRules($assignment);
        }

        // ----- On page load: Load saved contractor assignments from the server -----
        if (savedContractorAssignments && savedContractorAssignments.length > 0) {
            $.each(savedContractorAssignments, function(index, assignment) {
                addSavedContractorAssignment(index, assignment);
            });
            // Ensure preview generation happens after all assignments are added
            AIZ.uploader.previewGenerate();
        } else {
            // If no saved assignments, add a default new block.
            addContractorAssignment(0);
        }

        // ----- Add new assignment on button click -----
        $(document).on('click', '#add-contractor-assignment', function () {
            let index = $('#contractor-assignments .contractor-assignment').length;
            addContractorAssignment(index);
        });

        // ----- Delegate event handler for Remove button -----
        $(document).on('click', '.remove-contractor-assignment', function () {
            $(this).closest('.contractor-assignment').remove();
            updateContractorIndexes();
        });

        // console.log("Contractor Assignment Script Initialized");

        // Initialize the VAT type and VAT percentage from the database
        var vatTypeFromDb = '{{ old('vat_type', $repairIssue->vat_type) }}';
        var vatPercentageFromDb = '{{ old('vat_percentage', $repairIssue->vat_percentage) }}';

        // Set the VAT type radio button based on the value from the database
        if (vatTypeFromDb === 'exclusive') {
            $("#vat_type_exclusive").prop('checked', true);
            $("#exclusive_vat_fields").removeClass('d-none'); // Show VAT percentage field
            if (vatPercentageFromDb > 0) {
                $("#vat_calculation_preview").removeClass('d-none'); // Show VAT calculation preview
                $("#vat_percentage").val(vatPercentageFromDb); // Set VAT percentage
                // Call the VAT calculation function to update the preview
                calculateVAT();
            }
            $("#vat_percentage").prop('required', true); // Make VAT percentage required
        } else {
            $("#vat_type_inclusive").prop('checked', true);
            $("#exclusive_vat_fields").addClass('d-none'); // Hide VAT percentage field
            $("#vat_calculation_preview").addClass('d-none'); // Hide VAT calculation preview
            $("#vat_percentage").prop('required', false); // Remove the required attribute
        }

        // Show/hide exclusive VAT fields based on VAT type selection
        $("input[name='vat_type']").on('change', function () {
            if ($("#vat_type_exclusive").is(':checked')) {
                $("#exclusive_vat_fields").removeClass('d-none');
                // Make VAT percentage required if Exclusive VAT is selected
                $("#vat_percentage").prop('required', true);
                // Show the VAT calculation preview only if VAT percentage is not empty
                if ($("#vat_percentage").val().length > 0 && $("#vat_percentage").val() > 0) {
                    $("#vat_calculation_preview").removeClass('d-none');
                }
                calculateVAT();
            } else {
                $("#exclusive_vat_fields").addClass('d-none');
                $("#vat_calculation_preview").addClass('d-none');
                $("#vat_percentage").val(''); // Clear VAT percentage input if switching to inclusive VAT
                $("#vat_percentage").prop('required', false); // Remove the required attribute
            }
        });

        // Calculate VAT preview when percentage is entered
        $("#vat_percentage").on('input', function () {
            // Show the VAT calculation preview when the VAT percentage has a value and is greater than 0
            if ($(this).val().length > 0 && $(this).val() > 0) {
                $("#vat_calculation_preview").removeClass('d-none');
            } else {
                $("#vat_calculation_preview").addClass('d-none');
            }
            calculateVAT();
        });

        // Function to calculate and show VAT preview
        function calculateVAT() {
            var estimatedPrice = parseFloat($("#estimated_price").val()) || 0;
            var vatPercentage = parseFloat($("#vat_percentage").val()) || 0;
            if (estimatedPrice > 0 && vatPercentage > 0) {
                var vatAmount = estimatedPrice * (vatPercentage / 100);
                var totalPrice = estimatedPrice + vatAmount;
                $("#vat_calculation").html(`VAT Amount: $${vatAmount.toFixed(2)}<br>Total Price (including VAT): $${totalPrice.toFixed(2)}`);
            } else {
                $("#vat_calculation").html(""); // Clear calculation if invalid or empty values
            }
        }
    });



</script>
@endsection
