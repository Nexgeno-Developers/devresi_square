<!-- resources/views/backend/properties/form_components/form.blade.php -->
@extends('backend.layout.app')

@section('content')
@php
    $stepNames = [
        1 => 'Property Address',
        2 => 'Property Type',
        3 => 'Property Information',
        4 => 'Current Status',
        5 => 'Features',
        6 => 'Accessiblity',
        7 => 'Price',
        8 => 'Valid EPC',
        9 => 'Media',
        10 => 'Responsibility',
    ];

    // Determine the last enabled step based on $property->step or default to 1
    $currentStep = isset($property->step) ? $property->step+1 : 1;
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-3 left_inner_menu">
            <div class="stepformcomponents">
                <!-- @for ($i = 1; $i <= count($stepNames); $i++)
                    <div class="form-check {{ session('current_step') == $i ? 'active' : '' }}">
                    <input class="form-check-input" type="radio" name="step" data-property-id="" id="step{{ $i }}" value="{{ $i }}"
                    {{ session('current_step') == $i ||  $i == 1 ? 'checked' : '' }}>
                        <label class="form-check-label {{ session('current_step') == $i ? 'active' : '' }}"
                            for="step{{ $i }}">
                            {{ $stepNames[$i] ?? 'Unknown Step'}}
                        </label>
                    </div>
                @endfor -->
                @for ($i = 1; $i <= count($stepNames); $i++)
                    <div class="form-check {{ $currentStep == $i ? 'active' : '' }}">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="step"
                            id="step{{ $i }}"
                            value="{{ $i }}"
                            {{ $currentStep == $i || ($i == 1 && !$property) ? 'checked' : '' }}
                            {{ $i <= $currentStep ? '' : 'disabled' }}>

                        <label class="form-check-label {{ $currentStep == $i ? 'active' : '' }}" for="step{{ $i }}">{{ $stepNames[$i] ?? 'Unknown Step'}}</label>
                    </div>
                @endfor
            </div>
            <!-- <ul class="nav flex-column stepformcomponents">
                <li class="nav-item">
                    <a class="nav-link {{ session('current_step') == 1 ? 'active' : '' }}" href="#" data-step="1">Property Address</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ session('current_step') == 2 ? 'active' : '' }}" href="#" data-step="2">Property Type</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ session('current_step') == 3 ? 'active' : '' }}" href="#" data-step="3">Property Information</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ session('current_step') == 4 ? 'active' : '' }}" href="#" data-step="4">Current Status</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ session('current_step') == 5 ? 'active' : '' }}" href="#" data-step="5">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ session('current_step') == 6 ? 'active' : '' }}" href="#" data-step="6">Price</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ session('current_step') == 7 ? 'active' : '' }}" href="#" data-step="7">Valid EPC</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ session('current_step') == 8 ? 'active' : '' }}" href="#" data-step="8">Media</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ session('current_step') == 9 ? 'active' : '' }}" href="#" data-step="9">Responsibility</a>
                </li>
            </ul> -->
        </div>

        <div class="col-lg-9 col-12 render_blade">
            @if (isset($property) && isset($currentStep) && view()->exists('backend.properties.form_components.step' . $currentStep))
                @include('backend.properties.form_components.step' . $currentStep, ['property' => $property])
            @else
                {{-- Handle the case where $property is not set or step is invalid --}}
                @if (isset($currentStep) && view()->exists('backend.properties.form_components.step' . $currentStep))
                    @include('backend.properties.form_components.step' . $currentStep)
                @else
                    {{-- Fallback or error message if the step is invalid --}}
                    <p>Invalid step or property not found. Please try again.</p>
                @endif
            @endif
        </div>


    </div>
</div>
<!-- Modal for confirmation -->
<div class="modal fade" id="smallModal2" tabindex="-1" aria-labelledby="smallModal2-label" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="smallModal2-label">Gas Safe Confirmation</h5>
                <a type="button" class="btn-close" onclick="closeModal();" data-bs-dismiss="modal" aria-label="Close"></a>
            </div>
            <div class="modal-body">
                <p>I acknowledge that it's my legal duty to provide tenants with a GAS SAFE certificate before the tenancy begins.</p>
                <p>I understand it's a criminal offence to rent out a property with gas appliances or a gas supply that hasn't been inspected by a GAS SAFE Registered Engineer within the last 12 months.</p>
                <button id="confirm_gas" class="btn btn-primary">I Acknowledge</button>
                <button id="cancel_gas" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page.scripts')
<script type="text/javascript">
		$(document).ready(function() {
			AIZ.plugins.aizUppy();
		});
	</script>
<script>

    $(document).ready(function () {
        function handleGasSafeModal() {
            // Check if hidden input has value 1 and is_gas is 1, if so, no need to show the popup
            if ($('#gas_safe_acknowledged').val() != '1' && $('#is_gas_yes').is(':checked')) {
                // If both conditions are true, skip showing the popup
                $('#smallModal2').modal('show');
            }

            // Show warning and modal when "Yes" is selected
            $('#is_gas_yes').change(function() {
                // Check if the hidden input is already acknowledged, skip popup if true
                if ($('#gas_safe_acknowledged').val() !== '1') {
                    $('#smallModal2').modal('show'); // Show the modal
                }
            });

            // Show warning if "No" is selected and reset hidden input
            $('#is_gas_no').change(function() {
                $('#gas_safe_acknowledged').val('0'); // Reset the hidden field value to 0
            });

            // Confirm gas acknowledgment and set the hidden field
            $('#confirm_gas').click(function(event) {
                event.preventDefault(); // Prevent any form submission or button default action
                $('#gas_safe_acknowledged').val('1'); // Set acknowledgment to 1
                $('#smallModal2').modal('hide'); // Hide the modal
            });

            // Close modal without setting acknowledgment
            $('#cancel_gas').click(function(event) {
                event.preventDefault(); // Prevent any form submission or button default action
                $('#smallModal2').modal('hide'); // Hide the modal
            });

            // Close modal without setting acknowledgment (button in modal header)
            function closeModal() {
                $('#smallModal2').modal('hide'); // Hide the modal
            }
        }
 // Utility function to initialize Tagify dynamically based on data attributes
 function initDynamicTagify() {
        const tagifyInputs = document.querySelectorAll('.tagify-input');
        tagifyInputs.forEach(inputElement => {
            // const source = inputElement.dataset.source; // 'stations' or 'schools'
            const values = inputElement.dataset.values; // Pre-selected values (can be IDs, comma-separated, or JSON)
            const options = JSON.parse(inputElement.dataset.options || '{}'); // Max tags, dropdown options
            const idValue = JSON.parse(inputElement.dataset.idValue || '[]'); // ID-Value pair (array or JSON)

            // Determine the data (station or school)
            const data = idValue;
            // const data = (source === 'stations') ? idValue : idValue;

            // Parse the pre-selected values (can be comma-separated or JSON string)
            let selectedIds = [];
            if (values.includes(',')) {
                selectedIds = values.split(',').map(id => id.trim()); // Comma-separated IDs
            } else if (values.startsWith('{') || values.startsWith('[')) {
                // Handle case where it's a JSON string
                selectedIds = JSON.parse(values).map(item => item.trim());
            } else {
                // Handle simple case with a single ID
                selectedIds = [values.trim()];
            }

            // Initialize Tagify with dynamic options
            const tagify = new Tagify(inputElement, {
                whitelist: data.map(item => item.name), // Use the name for the whitelist
                maxTags: options.maxTags || 5, // Set the max number of tags
                dropdown: {
                    enabled: options.dropdownEnabled === 1, // Enable dropdown only when typing
                    maxItems: options.maxItems || 10, // Limit the number of items in the dropdown
                    searchKeys: options.searchKeys || ['name'], // Search by 'name' in the whitelist
                    closeOnSelect: options.closeOnSelect || false, // Keep dropdown open after selecting an item
                },
                pattern: /[\w\s]/, // Regular expression to match word characters and spaces (case-insensitive)
                // dropdown: {
                //     enabled: options.dropdownEnabled || 0 // Control dropdown display
                // }
            });

            // Populate Tagify with existing selected items based on selectedIds
            const selectedNames = selectedIds.map(id => {
                const item = data.find(item => item.id == id); // Find the name by ID
                return item ? item.name : '';  // Return the name or empty string if not found
            }).filter(name => name);  // Filter out empty names

            tagify.addTags(selectedNames);  // Add the tags to Tagify

            // Set the hidden input value to the selected IDs
            const hiddenInput = inputElement.closest('.form-group').querySelector('.hidden-input');
            hiddenInput.value = selectedIds.join(','); // Store IDs as a comma-separated string

            // Handle adding a new tag
            tagify.on('add', function (e) {
                const newTag = e.detail.data;
                const selectedItem = data.find(item => item.name === newTag.value);
                if (selectedItem) {
                    const selectedIds = tagify.value.map(tag => {
                        const item = data.find(item => item.name === tag.value);
                        return item ? item.id : null;
                    });
                    hiddenInput.value = selectedIds.join(',');
                }
            });

            // Handle removing a tag
            tagify.on('remove', function (e) {
                const removedTag = e.detail.data;
                const selectedItem = data.find(item => item.name === removedTag.value);
                if (selectedItem) {
                    const selectedIds = tagify.value.map(tag => {
                        const item = data.find(item => item.name === tag.value);
                        return item ? item.id : null;
                    });
                    hiddenInput.value = selectedIds.join(',');
                }
            });
        });
    }      
        @if ($currentStep == 6)
            // reinitializeTagify();
            initDynamicTagify();
        @endif
        @if ($currentStep == 8)
            handleGasSafeModal();
        @endif

        // // Fetching all stations and schools as JSON objects from PHP (these are arrays of objects)
        // const stations = @json($allstations); // Fetch all station names with IDs
        // const schools = @json($allschools); // Fetch all school names with IDs

        // // Utility function to initialize Tagify
        // function initTagify(inputSelector, hiddenInputSelector, data, selectedIdsString) {
        //     const inputElement = document.querySelector(inputSelector);
        //     const hiddenInput = document.querySelector(hiddenInputSelector);

        //     // Convert the comma-separated string of selected IDs into an array
        //     const selectedIds = selectedIdsString.split(',').map(id => id.trim());

        //     // Initialize Tagify
        //     const tagify = new Tagify(inputElement, {
        //         whitelist: data.map(item => item.name), // Map to get only names
        //         maxTags: 5,
        //         dropdown: {
        //             enabled: 0 // Disable dropdown
        //         }
        //     });

        //     // Populate Tagify with existing selected items based on selectedIds
        //     const selectedNames = selectedIds.map(id => {
        //         const item = data.find(item => item.id == id); // Find the name by ID
        //         return item ? item.name : '';  // Return the name or empty string if not found
        //     }).filter(name => name);  // Filter out empty names

        //     tagify.addTags(selectedNames);  // Add the tags to Tagify

        //     // Set the hidden input value to the selected IDs
        //     hiddenInput.value = selectedIds.join(','); // Comma-separated string of IDs

        //     // Handle adding a new tag
        //     tagify.on('add', function (e) {
        //         const newTag = e.detail.data; // The tag that was just added
        //         const selectedItem = data.find(item => item.name === newTag.value);
        //         if (selectedItem) {
        //             const selectedIds = tagify.value.map(tag => {
        //                 const item = data.find(item => item.name === tag.value);
        //                 return item ? item.id : null;
        //             });
        //             hiddenInput.value = selectedIds.join(','); // Store as comma-separated string
        //         }
        //     });

        //     // Handle removing a tag
        //     tagify.on('remove', function (e) {
        //         const removedTag = e.detail.data; // The tag that was just removed
        //         const selectedItem = data.find(item => item.name === removedTag.value);
        //         if (selectedItem) {
        //             const selectedIds = tagify.value.map(tag => {
        //                 const item = data.find(item => item.name === tag.value);
        //                 return item ? item.id : null;
        //             });
        //             hiddenInput.value = selectedIds.join(','); // Store as comma-separated string
        //         }
        //     });
        // }


        // // Reinitialize Tagify after step rendering
        // function reinitializeTagify() {
        //     @php
        //         // Prepare data for JavaScript
        //         $stations = $allstations->map(function ($station) {
        //             return ['id' => $station->id, 'name' => $station->name];
        //         });

        //         $schools = $allschools->map(function ($school) {
        //             return ['id' => $school->id, 'name' => $school->name];
        //         });
        //     @endphp
        //     // Fetching the JSON data for stations and schools
        //     const selectedStationIds = @json($stations);  // PHP data for stations: {1: "Bakerloo", 2: "Jubilee", 3: "Northern"}
        //     const selectedSchoolIds = @json($schools);    // PHP data for schools: {2: "Tower House School", 3: "Greenwich Academy"}

        //     // Fetch the selected comma-separated IDs stored in hidden inputs
        //     const stationIdsFromInput = document.querySelector('input[name="nearest_station"]').value;
        //     const schoolIdsFromInput = document.querySelector('input[name="nearest_school"]').value;

        //     // Call initTagify with proper data
        //     initTagify('#station_name', 'input[name="nearest_station"]', selectedStationIds, stationIdsFromInput);
        //     initTagify('#school_name', 'input[name="nearest_school"]', selectedSchoolIds, schoolIdsFromInput);
        // }
        // // Initialize Tagify for station and school fields with selected station and school IDs
        // @if ($currentStep == 6)
        //     reinitializeTagify();
        // @endif

        // General function to handle sending form data and navigating steps
        function handleStepChange(currentStep, targetStep, previous = null) {

            // Check if navigating to a previous step
            if (previous) {
                // Render the previous step and update the radio button selection
                renderStep(targetStep);
                $('input[name="step"][value="' + targetStep + '"]').prop('checked', true);
                return; // Exit early as no validation or data submission is needed for previous steps
            }

            // Validate the current step form only when moving forward
            const isValid = initValidate('#property-form-step-' + currentStep);

            // If validation passed, proceed to send data and update to the next step
            if (isValid) {
                // Send form data for current step before navigating to the target step
                sendFormData(currentStep);
                // Update the radio button selection to the target step
                $('input[name="step"][value="' + targetStep + '"]').prop('checked', true);
                // Enable the next step in the navigation
                $('input[name="step"][value="' + targetStep + '"]').prop('disabled', false);
            }
        }


        // Function to send form data for a specific step
        function sendFormData(step) {
            const formData = new FormData($('#property-form-step-' + step)[0]);
            formData.append('step', step);
            // Add property_id to the form data if it doesn't already exist
            const propertyId = $('#property_id').val();  // Get the property_id from the hidden input
            let propertyIdExists = false;

            // Check if property_id already exists in the formData
            for (let [key, value] of formData.entries()) {
                if (key === 'property_id') {
                    propertyIdExists = true;
                    break;
                }
            }

            // Append property_id only if it doesn't exist already
            if (propertyId && !propertyIdExists) {
                formData.append('property_id', propertyId);
            }
            $.ajax({
                url: '{{ route("admin.properties.store") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('.render_blade').html(response);
                    if(step == 8){
                        AIZ.uploader.previewGenerate();
                    }
                    if(step == 5){
                        // reinitializeTagify();
                        initDynamicTagify();
                    }
                    if(step == 7){
                        handleGasSafeModal();
                    }
                },
                error: function (jqXHR) {
                    if (jqXHR.status === 422) {
                        const errors = jqXHR.responseJSON.errors;
                        Object.keys(errors).forEach(key => {
                            toastr.error(errors[key][0], 'Validation Error');
                        });
                    }
                }
            });
        }
        // Define the URL based on the condition if $property exists
        @php
            $formStepRenderUrl = isset($property)
                ? route('admin.properties.step', ['step' => ':step', 'property_id' => $property->id])
                : route('admin.properties.step', ['step' => ':step']);
        @endphp
        // Function to render the view for a specific step
        function renderStep(step) {
            // Get property_id from the hidden input field if available
            const propertyId = $('#property_id').val();
            // Replace ':step' with the actual step in the URL
            let formStepRenderUrl = '{{ $formStepRenderUrl }}'.replace(':step', step);

            // Check if 'property_id' is already present in the URL
            if (propertyId && !formStepRenderUrl.includes('property_id')) {
                formStepRenderUrl += `?property_id=${propertyId}`;
            }

            $.ajax({
                url: formStepRenderUrl,
                method: 'GET',
                success: function (response) {
                    $('.render_blade').html(response);
                    if(step == 6){
                        // reinitializeTagify();
                        initDynamicTagify();
                    }
                    if(step === 8){
                        handleGasSafeModal();
                    }
                    if(step == 9){
                        AIZ.uploader.previewGenerate();
                    }
                    // Find id="property_id" and put the propertyId if it’s missing
                    if (!$('#property_id').val()) {
                        $('#property_id').val(propertyId);
                    }
                },
                error: function () {
                    toastr.error('Unable to load step. Please try again.', 'Error');
                }
            });
        }

        // Handle Next button clicks
        $(document).on('click', '.next-step', function(e) {
            e.preventDefault();
            const currentStep = $(this).data('current-step');
            const targetStep = $(this).data('next-step');
            handleStepChange(currentStep, targetStep);
        });

        // Handle Previous button clicks
        $(document).on('click', '.previous-step', function(e) {
            e.preventDefault();
            const currentStep = $(this).data('current-step');
            const targetStep = $(this).data('previous-step');
            handleStepChange(currentStep, targetStep, true);
        });

        // Handle radio button change
        $('input[name="step"]').change(function () {
            const selectedStep = $(this).val();
            renderStep(selectedStep); // Render the corresponding Blade view
        });


        //step10
        function updateRowNumbers() {
            const rows = $(".commission-row");
            rows.each(function(index) {
                const rowNumber = $(this).find(".row-number");
                rowNumber.text(index + 1);  // Update row number based on position
            });
        }

        function updateRemoveButtons() {
            const rows = $(".commission-row");
            rows.each(function (index) {
                const removeBtn = $(this).find(".remove-btn-commission");
                if (rows.length === 1) {
                    removeBtn.hide(); // Hide the remove button for the only row
                } else {
                    removeBtn.show(); // Show the remove button for all rows if more than one
                }
            });
        }

        // Add a new row
        $(document).on("click", ".add-btn-commission", function () {
            const newRow = $(".commission-row").first().clone();
            newRow.find("input, select").val(""); // Reset input and select values
            newRow.find(".remove-btn-commission").show(); // Show the remove button for new rows
            // Add empty hidden input for new record
            newRow.find("input[name='PropertyResponsibility_id[]']").val("");
            $("#commission-wrapper").append(newRow); // Append the cloned row

            updateRemoveButtons(); // Update button visibility
            updateRowNumbers(); // Update row numbers
        });

        // Remove a row
        $(document).on("click", ".remove-btn-commission", function () {
            const rows = $(".commission-row");
            const currentRow = $(this).closest(".commission-row");

            if (rows.length > 1) {
                currentRow.remove(); // Remove the current row
            } else {
                currentRow.find("input, select").val(""); // Reset inputs if it's the only row
            }
            updateRemoveButtons(); // Update button visibility
            updateRowNumbers(); // Update row numbers
        });

        // Initial setup
        updateRemoveButtons(); // Update button visibility
        updateRowNumbers(); // Update row numbers

    });


    /*
    $(document).ready(function () {

// Function to send form data
function sendFormData(step) {
    // Create a new FormData object to handle multiple fields
    const formData = new FormData($('#property-form-step-' + (step))[0]);

    // Add the step number to the FormData
    formData.append('step', step);
    // formData.append('_token', '{{-- csrf_token() --}}'); // Add CSRF token

    // AJAX call to send form data
    $.ajax({
        url: '{{ route('admin.properties.store') }}',
    method: 'POST',
        data: formData,
            processData: false, // Prevent jQuery from automatically transforming the data into a query string
                contentType: false, // Let the browser set the content type
                    success: function (response) {
                        $('.render_blade').html(response);
                    },
    error: function (jqXHR) {
        if (jqXHR.status === 422) {
            const errors = jqXHR.responseJSON.errors;
            Object.keys(errors).forEach(function (key) {
                toastr.error(errors[key][0], 'Validation Error');
            });
        }
    }
    });
}
    function renderStep(step) {
        const form_step_render_url = '{{ route('admin.properties.step', ':step') }}'.replace(':step', step);

        // Make an AJAX call to load the Blade view for the selected step
        $.ajax({
            url: form_step_render_url, // Adjust the URL to match your routing
            method: 'GET', // Use GET to fetch the view
            success: function (response) {
                $('.render_blade').html(response); // Load the response into the render_blade div
            },
            error: function () {
                toastr.error('Unable to load step. Please try again.', 'Error');
            }
        });
    }

    // Handle radio button change
    $('input[name="step"]').change(function () {
        const selectedStep = $(this).val();
        renderStep(selectedStep); // Render the corresponding Blade view
    });

    // Handle the next step button click
    $('.next-step').click(function (e) {
        e.preventDefault(); // Prevent the default action
        const currentStep = $(this).data('current-step');
        // console.log(currentStep);
        const nextStep = $(this).data('next-step');
        var initvalid = initValidate('#property-form-step-' + currentStep);
        // console.log(initvalid);

        if (initvalid) {
            sendFormData(currentStep); // Send data for the next step
            // Make sure the current step is serialized and sent to the server
            $('.nav-link[data-step="' + nextStep + '"]').data('step', nextStep); // Update the nav-link
        }
    });

    // Handle the previous step button click
    $('.previous-step').click(function (e) {
        e.preventDefault(); // Prevent the default action
        const currentStep = $(this).data('current-step');
        const previousStep = $(this).data('previous-step');
        sendFormData(currentStep); // Send data for the previous step

        // Make sure the current step is serialized and sent to the server
        $('.nav-link[data-step="' + previousStep + '"]').data('step', previousStep); // Update the nav-link
    });

});
*/
    /*
    $(document).ready(function () {


        // Handle step navigation
        // $('.nav-link').click(function (e) {
        //     e.preventDefault();
        //     const step = $(this).data('step');

        //     // Create a new FormData object to handle multiple fields
        //     const formData = new FormData($('#property-form-step-' + (step - 1))[0]);

        //     // Add the step number to the FormData
        //     formData.append('step', step);
        //     formData.append('_token', '{{ csrf_token() }}'); // Add CSRF token

    //     // AJAX call to load the next step
    //     $.ajax({
    //         url: '{{ route('admin.properties.store') }}',
    //         method: 'POST',
    //         data: formData,
    //         processData: false, // Prevent jQuery from automatically transforming the data into a query string
    //         contentType: false, // Let the browser set the content type
    //         success: function (response) {
    //             $('.col-md-9').html(response);
    //         },
    //         error: function (jqXHR) {
    //             if (jqXHR.status === 422) {
    //                 const errors = jqXHR.responseJSON.errors;
    //                 Object.keys(errors).forEach(function (key) {
    //                     toastr.error(errors[key][0], 'Validation Error');
    //                 });
    //             }
    //         }
    //     });
    // });
    // Function to send form data
    function sendFormData(step) {
        // Create a new FormData object to handle multiple fields
        const formData = new FormData($('#property-form-step-' + (step))[0]);

        // Add the step number to the FormData
        formData.append('step', step);
        // formData.append('_token', '{{-- csrf_token() --}}'); // Add CSRF token

        // AJAX call to send form data
        $.ajax({
            url: '{{ route('admin.properties.store') }}',
            method: 'POST',
            data: formData,
            processData: false, // Prevent jQuery from automatically transforming the data into a query string
            contentType: false, // Let the browser set the content type
            success: function (response) {
                $('.render_blade').html(response);
            },
            error: function (jqXHR) {
                if (jqXHR.status === 422) {
                    const errors = jqXHR.responseJSON.errors;
                    Object.keys(errors).forEach(function (key) {
                        toastr.error(errors[key][0], 'Validation Error');
                    });
                }
            }
        });
    }
    function renderStep(step) {
        const form_step_render_url = '{{ route('admin.properties.step', ':step') }}'.replace(':step', step);

        // Make an AJAX call to load the Blade view for the selected step
        $.ajax({
            url: form_step_render_url, // Adjust the URL to match your routing
            method: 'GET', // Use GET to fetch the view
            success: function (response) {
                $('.render_blade').html(response); // Load the response into the render_blade div
            },
            error: function () {
                toastr.error('Unable to load step. Please try again.', 'Error');
            }
        });
    }

    // Handle radio button change
    $('input[name="step"]').change(function () {
        const selectedStep = $(this).val();
        renderStep(selectedStep); // Render the corresponding Blade view
    });
    // Handle step navigation using .nav-link
    // $('.nav-link').click(function (e) {
    //     e.preventDefault();
    //     const step = $(this).data('step');
    //     sendFormData(step);

    //     // Make sure the current step is serialized and sent to the server
    //     $('.nav-link[data-step="' + step + '"]').data('step', step); // Update the nav-link
    // });

    // Handle the next step button click
    $('.next-step').click(function (e) {
        e.preventDefault(); // Prevent the default action
        const currentStep = $(this).data('current-step');
        // console.log(currentStep);
        const nextStep = $(this).data('next-step');
        var initvalid = initValidate('#property-form-step-' + currentStep);
        // console.log(initvalid);

        if (initvalid) {
            sendFormData(currentStep); // Send data for the next step
            // Make sure the current step is serialized and sent to the server
            $('.nav-link[data-step="' + nextStep + '"]').data('step', nextStep); // Update the nav-link
        }
    });

    // Handle the previous step button click
    $('.previous-step').click(function (e) {
        e.preventDefault(); // Prevent the default action
        const currentStep = $(this).data('current-step');
        const previousStep = $(this).data('previous-step');
        sendFormData(currentStep); // Send data for the previous step

        // Make sure the current step is serialized and sent to the server
        $('.nav-link[data-step="' + previousStep + '"]').data('step', previousStep); // Update the nav-link
    });
        // // Handle the next step button click
        // $('.next-step').click(function (e) {
        //     e.preventDefault(); // Prevent the default action
        //     const nextStep = $(this).data('next-step');
        //     const currentStep = $(this).data('current-step'); // Get the current step

        //     // Make sure the current step is serialized and sent to the server
        //     $('.nav-link[data-step="' + nextStep + '"]').data('step', nextStep); // Update the nav-link

        //     // Call the navigation function
        //     $('.nav-link[data-step="' + nextStep + '"]').click();
        // });

        // // Handle the previous step button click
        // $('.previous-step').click(function () {
        //     const previousStep = $(this).data('previous-step');
        //     $('.nav-link[data-step="' + previousStep + '"]').click();
        // });


    });
*/
</script>
@endsection
