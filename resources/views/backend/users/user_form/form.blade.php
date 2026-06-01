<!-- resources/views/backend/user/user_form/form.blade.php -->
@php
$stepNames = [
    1 => 'Category',
    2 => 'Personal Information',
    3 => 'Related Properties',
];

    // Determine the last enabled step based on $user->step or default to 1
    $currentStep = isset($user->quick_step) ? $user->quick_step+1 : 1;
@endphp


    <h4 class="mb-4">Quick Add Contact</h4>
    <div class="qap_breadcrumb">
            @for ($i = 1; $i <= count($stepNames); $i++)
                <div class="form-check {{ $currentStep == $i ? 'active' : '' }}">
                    <input
                        class="form-check-input"
                        type="radio"
                        name="step"
                        id="step{{ $i }}"
                        value="{{ $i }}"
                        {{ $currentStep == $i || ($i == 1 && !$user) ? 'checked' : '' }}
                        {{ $i <= $currentStep ? '' : 'disabled' }}>

                    <label class="form-check-label {{ $currentStep == $i ? 'active' : '' }}" for="step{{ $i }}">{{ $stepNames[$i] ?? 'Unknown Step'}}</label>
                </div>
            @endfor
    </div>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 render_blade">
            @include('backend.users.user_form.step' . $currentStep )
        </div>
    </div>
</div>
@include('backend.partials.assets.select2')

@section('page.scripts')
<script>
$(document).ready(function() {

    initSelect2('.select2');

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
        const isValid = initValidate('#user-form-step-' + currentStep);

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
        const formData = new FormData($('#user-form-step-' + step)[0]);
        formData.append('step', step);
        // Add user_id to the form data if it doesn't already exist
        const userId = $('#user_id').val(); // Get the user_id from the hidden input
        let userIdExists = false;

        // Check if user_id already exists in the formData
        for (let [key, value] of formData.entries()) {
            if (key === 'user_id') {
                userIdExists = true;
                break;
            }
        }

        // Append user_id only if it doesn't exist already
        if (userId && !userIdExists) {
            formData.append('user_id', userId);
        }
        $.ajax({
            url: '{{ route("admin.users.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('.render_blade').html(response);
                if(step == 1){
                    // If step 1, initialize select2 for the roles
                    initSelect2('.select2');
                }
                if(step == 2){
                    initSelectedProperties();
                }
            },
            error: function(jqXHR) {
                if (jqXHR.status === 422) {
                    const errors = jqXHR.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        toastr.error(errors[key][0], 'Validation Error');
                    });
                }
            }
        });
    }

    @php
    $formStepRenderUrl = isset($user) ?
        route('admin.users.user_step', ['step' => ':step', 'user_id' => $user->id]) :
        route('admin.users.user_step', ['step' => ':step']);
    @endphp

    // Function to render the view for a specific step
    function renderStep(step) {
        // Get user_id from the hidden input field if available
        const userId = $('#user_id').val();
        // Replace ':step' with the actual step in the URL
        let formStepRenderUrl = '{{ $formStepRenderUrl }}'.replace(':step', step);

        // Check if 'user_id' is already present in the URL
        if (userId && !formStepRenderUrl.includes('user_id')) {
            formStepRenderUrl += `?user_id=${userId}`;
        }

        $.ajax({
            url: formStepRenderUrl,
            method: 'GET',
            success: function(response) {
                $('.render_blade').html(response);
                if(step == 3){
                    initSelectedProperties();
                }
                // Find id="user_id" and put the userId if it’s missing
                if (!$('#user_id').val()) {
                    $('#user_id').val(userId);
                }
            },
            error: function() {
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
        const previous = true;

        handleStepChange(currentStep, targetStep, previous);
    });

    // Handle radio button change
    $('input[name="step"]').change(function() {
        const selectedStep = $(this).val();
        renderStep(selectedStep); // Render the corresponding Blade view
    });


    // Handle the search input events (keyup and keydown)
    $(document).on('keyup keydown', '#search_property1', function() {
        var query = $(this).val().trim();  // Get the search query

        if (query.length >= 3) {  // Trigger search when 3 or more characters are entered
            searchProperties(query);
            $('#error_message').hide(); // Hide the error message if the query length is valid
        } else {
            $('#property_results').empty(); // Clear the results if query length is less than 3 characters
            $('#error_message').text('Please enter at least 3 characters to search.').show(); // Show the error message
        }
    });

    // Function to perform the AJAX request for searching properties
    function searchProperties(query) {
        $.ajax({
            url: '{{ route('admin.users.properties.search') }}',
            method: 'GET',
            data: { query: query },
            success: function(response) {
                $('#property_results').empty();
                if (response.length > 0) {
                    response.forEach(function(property) {
                        if (!$('#property_results li[data-id="' + property.id + '"]').length) {
                            appendPropertyToResults(property);
                        }
                    });
                } else {
                    $('#property_results').append('<li class="list-group-item">No properties found.</li>');
                }
            },
            error: function() {
                $('#property_results').append('<li class="list-group-item">Error fetching results.</li>');
            }
        });
    }

    // Function to add a property to the results list
    function appendPropertyToResults(property) {
        var address = property.address || 'N/A';
        var propRefNo = property.prop_ref_no || 'N/A';
        var propName = property.prop_name || 'N/A';
        var listItem = `<li class="list-group-item property-result" data-id="${property.id}" data-type="${property.type}" data-price="${property.price}" data-availability="${property.availability}">
                            ${address} - ${propRefNo} - ${propName}
                        </li>`;
        $('#property_results').append(listItem);
    }

    // Handle property selection and append it to the dynamic table
    $(document).on('click', '.property-result', function() {
        var propertyId = $(this).data('id');
        if ($('#dynamic_property_table tbody tr[data-id="' + propertyId + '"]').length === 0) {  // Prevent duplicates
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
    });

    // Update the hidden input with the current list of selected property IDs
    function updateSelectedProperties() {
        var selectedPropertyIds = [];
        $('#dynamic_property_table tbody tr').each(function() {
            selectedPropertyIds.push($(this).data('id'));
        });
        $('#selected_properties').val(JSON.stringify(selectedPropertyIds));
    }

    // Remove function - Remove the row when the "Remove" button is clicked
    $(document).on('click', '.remove-btn', function() {
        $(this).closest('tr').remove();
        updateSelectedProperties();
    });

    // Function to initialize the selected properties when at step 3
    function initSelectedProperties() {
        const selectedProperties_f = JSON.parse($('#selected_properties').val()); // Assuming selected properties are stored in a hidden field
        if (selectedProperties_f && selectedProperties_f.length > 0) {
            searchPropertiesByIds(selectedProperties_f); // Call the function to fetch and display selected properties
        }
    }

    // Get and display the pre-selected properties when the page loads
    const selectedProperties = JSON.parse($('#selected_properties').val());
    if (selectedProperties && selectedProperties.length > 0) {
        searchPropertiesByIds(selectedProperties);
    }

    // Function to fetch property details by IDs
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
                                        <td><button class="btn btn-danger remove-btn">Remove</button></td>
                                    </tr>`;
                        $('#dynamic_property_table tbody').append(newRow);
                    }
                });
                updateSelectedProperties();
            },
            error: function() {
                toastr.error('Error fetching properties by IDs.', 'Error');
            }
        });
    }

    });


</script>
@endsection
