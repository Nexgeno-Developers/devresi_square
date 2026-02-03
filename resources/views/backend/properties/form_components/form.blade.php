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
        6 => 'Price',
        7 => 'Valid EPC',
        8 => 'Media',
        9 => 'Responsibility',
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
@endsection

@section('page.scripts')
<script type="text/javascript">
		$(document).ready(function() {
			AIZ.plugins.aizUppy();
		});
	</script>
<script>
    
    $(document).ready(function () {
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
                    if(step == 7){
                        AIZ.uploader.previewGenerate();
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
                    if(step == 8){
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