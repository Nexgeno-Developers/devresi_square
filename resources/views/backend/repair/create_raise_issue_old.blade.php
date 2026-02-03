@extends('backend.layout.app')

@section('content')
<div class="container">
    <h1>Report a Repair</h1>

    <form id="repair-form-page">

        <div class="steps_wrapper">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Breadcrumb Navigation -->
                <nav aria-label="main-breadcrumb">
                    <ul class="main-breadcrumb d-flex">
                        <li class="main-breadcrumb-item active" aria-current="page">Raise Repair Issue</li>
                        <li class="main-breadcrumb-item active" aria-current="page">Raise Repair Issue</li>
                        <!-- Breadcrumb items will be added dynamically -->
                    </ul>
                </nav>

                <!-- Navigation Buttons -->
                <div class="d-flex">
                    <button id="prev-btn" class="btn btn-secondary" disabled>Previous</button>
                    <button id="next-btn" class="btn btn-primary" disabled>Next</button>
                </div>
            </div>
            <!-- Step 1: Search Property -->
            <div id="step1" class="step">
                <div class="row justify-content-center align-items-center">
                    <h2 class="text-center my-3">Where is the problem?</h2>
                    <div class="col-md-6">
                        <div class="from-group text-center mt-lg-0 mt-4">
                            <label class="mb-2" for="search_property1">Search And Select Property</label>
                            <!-- Search input field for properties -->
                            <div class="form-group">
                                <div class="rs_input input_search">
                                    <div class="right_icon d-flex align-items-center"><i class="bi bi-search"></i></div>
                                    <input type="text" id="search_property1" placeholder="Search Property" class="form-control search_property" />
                                </div>
                                <div id="error_message" style="color: red; display: none;"></div>
                            </div>
                            <!-- Search results listing -->
                            <ul id="property_results" class="list-group mt-2"></ul>
                            <!-- Selected Properties -->
                            <input type="hidden" id="selected_properties" name="selected_properties" value="{{ json_encode(isset($selectedProperties) ? $selectedProperties : []) }}">
                        </div>
                    </div>
                    <!-- Dynamic Property Table -->
                    <div id="dynamic_property_table" class="d-none mt-4">
                        @php
                            $headers = ['id' => 'id', 'Address', 'Type', 'Availability'];
                            $rows = []; // Start with an empty array of rows
                        @endphp
                        <x-backend.dynamic-table :headers="$headers" :rows="$rows" class='user_add_property' />
                    </div>
                </div>
            </div>

            <!-- Step 2: Search or manually select repair category -->
            <div id="step2" class="step d-none">

                <h3 class="text-center my-3">What type of a problem are you facing?</h2>
                <p class="text-center my-3">Do not worry please report a problem for property manager to review and take appropriate action.</p>

                <div class="search-issue">
                    <p class="text-center mt-lg-5">Search your problem</p>
                    <div class="row justify-content-center align-items-center">
                        <div class="col-6">
                            <x-search-dropdown name="select_repair_category" route="{{ route('admin.get.repair.categories') }}" placeholder="Search repair category..." />
                        </div>
                    </div>
                </div>

                <h3 class="or-text text-center my-3">OR</h3>

                <div class="manual-search-issue">

                    <div class="d-flex justify-content-center gap-3 flex-column align-items-center">
                        <p class="text-center my-2">Select manually your problem area</p>
                        <x-backend.forms.button
                            class='text-center hide-search-show-manual'
                            name='Select Catgeory'
                            type='secondary'
                            size='sm'
                            isOutline={{true}}
                            isLinkBtn={{false}}
                            link='https://#'
                            onClick=''
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <!-- Breadcrumb Navigation -->
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item active" aria-current="page">Raise Repair Issue</li>
                                <!-- Breadcrumb items will be added dynamically -->
                            </ol>
                        </nav>

                        <!-- Navigation Buttons -->
                        <div class="d-flex">
                            <button id="prev-btn-issue" class="btn btn-secondary d-none">Previous</button>
                            <button id="next-btn-issue" class="btn btn-primary d-none" disabled>Next</button>
                        </div>
                    </div>

                    <div id="category-main-view" class="main-view d-none">
                        <!-- Default Level 1 (Parent Categories) -->
                        <div class="category-level" data-level="1">
                            <div class="row">
                                @foreach ($categories as $category)
                                    <div class="col-md-4">
                                        <div class="form-check d-flex align-items-center">
                                            <input class="form-check-input" type="radio" name="repair_category"
                                                id="repair-{{ $category->id }}" value="{{ $category->id }}">
                                            <label class="form-check-label d-flex align-items-center"
                                                for="repair-{{ $category->id }}">
                                                <i class="fas fa-cogs me-2"></i> <!-- Font Awesome icon -->
                                                {{ $category->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Dynamically Generated Levels -->
                        @for ($level = 2; $level <= $maxLevel; $level++)
                            <div class="category-level" data-level="{{ $level }}" style="display: none;">
                                <!-- Placeholder for level {{ $level }} -->
                                <div class="row"></div>
                            </div>
                        @endfor
                    </div>
                </div>


            </div>

            <!-- Step 3: Confirmation Form (if category is manually selected) -->
            <div id="step3" class="step d-none">
                <!-- Include Common Form on the Last Step -->
                <div id="repair-form" class="d-none">
                    @include('backend.repair.common_form')
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>

        </div>

    </form>
</div>
@endsection

@section('page.scripts')

    @stack('domready.scripts')
    <script>
        $(document).on('click', '.hide-search-show-manual', function () {
            $(this).addClass("d-none");
            $('#category-main-view').removeClass("d-none");
            $('.search-issue').addClass("d-none");
            $('.or-text').addClass("d-none");
            $('#next-btn-issue').removeClass("d-none");
        });

        // Update Breadcrumb
        function updateBreadcrumb(name) {
            const breadcrumb = $('ol.breadcrumb');

            // Remove existing "active" class and mark last item as active
            breadcrumb.find('li').removeClass('active').last().addClass('active');

            // Correct template literal syntax
            breadcrumb.append(`
                <li class="breadcrumb-item active" aria-current="page">${name}</li>
            `);
        }

        $(document).ready(function () {

            function logSearchValue() {
                const searchValue = document.getElementById('searchInput').value;
                console.log("Search Value:", searchValue);
            }

            let selectedProperty = null;  // Track selected property
            // let selectedCategory = null;  // Track selected repair category
            let currentStep = 1;          // Track current step

            let currentLevel = 1;
            let selectedCategories = {}; // Store selected category IDs by level

            // --------------------------- STEP NAVIGATION FUNCTIONS --------------------------- //
            function showStep(step) {
                $(".step").addClass("d-none");
                $("#step" + step).removeClass("d-none");

                // Update breadcrumb
                $(".breadcrumb-item").addClass("d-none");
                $("#breadcrumb-step" + step).removeClass("d-none");

                currentStep = step;
            }

            function enableNextButton() {
                $("#next-btn").prop("disabled", false);
            }

            function disableNextButton() {
                $("#next-btn").prop("disabled", true);
            }

            function enablePreviousButton() {
                $("#prev-btn").prop("disabled", false);
            }

            function disablePreviousButton() {
                $("#prev-btn").prop("disabled", true);
            }

            // --------------------------- NEXT/PREVIOUS BUTTON HANDLING --------------------------- //
            $("#next-btn").on("click", function () {
                console.log(currentStep);
                if (currentStep === 1 && selectedProperty) {
                    showStep(2);
                    enablePreviousButton();
                    disableNextButton();
                } else if (currentStep === 2 && selectedCategory) {
                    showStep(3);
                    $("#repair-form").removeClass("d-none");
                }
            });

            $("#prev-btn").on("click", function () {
                // console.log(currentStep);
                if (currentStep > 1) {
                    currentStep = currentStep - 1;
                    showStep(currentStep);
                    if(currentStep === 1 && selectedProperty){
                        enableNextButton();
                    }else{
                        disableNextButton();
                    }
                    if(currentStep = 1){
                        disablePreviousButton();
                    }
                }
                // console.log(currentStep);
            });

            // --------------------------- INITIALIZATION --------------------------- //
            showStep(1);

            // --------------------------- STEP 1: PROPERTY SEARCH --------------------------- //

            // Handle the search input events (keyup and keydown)
            $(document).on('keyup keydown', '#search_property1', function () {
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
            $(document).on('click', '.property-result', function () {
                //store selected property in variable
                selectedProperty = $(this).data("id");
                console.log(selectedProperty);

                // Remove any previously selected property and prevent add more then 1 property
                $('#dynamic_property_table tbody').empty();

                $('#dynamic_property_table').removeClass('d-none');
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
                    enableNextButton();
                }
                $('#property_results').empty();
                $('#search_property1').val('');
            });

            // Update the hidden input with the current list of selected property IDs
            function updateSelectedProperties() {
                var selectedPropertyIds = [];
                $('#dynamic_property_table tbody tr').each(function () {
                    selectedPropertyIds.push($(this).data('id'));
                });
                $('#selected_properties').val(JSON.stringify(selectedPropertyIds));
            }

            // Remove function - Remove the row when the "Remove" button is clicked
            $(document).on('click', '.remove-btn', function () {
                $(this).closest('tr').remove();
                updateSelectedProperties();
                // Check if there are no rows left in the table
                if ($('#dynamic_property_table tbody tr').length < 1) {
                    $('#dynamic_property_table').addClass('d-none');

                    disableNextButton();
                }
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
                    url: '{{ route('properties.search') }}',
                    method: 'GET',
                    data: { ids: propertyIds },
                    success: function (response) {
                        $('#dynamic_property_table tbody').empty();
                        response.forEach(function (property) {
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
                    error: function () {
                        toastr.error('Error fetching properties by IDs.', 'Error');
                    }
                });
            }

            // --------------------------- STEP 2: CATEGORY SELECTION --------------------------- //

            function checkLastStep(selectedCategories) {
                $.ajax({
                    url: "{{ route('admin.repair.checkLastStep') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        selectedCategories: selectedCategories
                    },
                    success: function (response) {
                        if (response.isLastStep) {
                            // Hide the next button and show the form if it's the last step
                            $('#next-btn-issue').prop('disabled', true);
                            // $('#prev-btn-issue').addClass('d-none');
                            // $('#next-btn-issue').addClass('d-none');
                            showStep(3);
                            $('#repair-form').removeClass('d-none'); // Show the form
                        }
                    },
                    error: function (error) {
                        AIZ.plugins.notify('error', 'An error occurred while checking the last step.');
                    }
                });
            }

            $(document).on('change', 'input[type="radio"]', function () {
                const selectedRadioValue = $(this).val();
                const selectedNameRadio = $(this).siblings('label').text().trim();
                console.log("Selected Value:", selectedRadioValue);
                console.log("Selected Name:", selectedNameRadio);
                // Add to breadcrumb
                // updateBreadcrumb(selectedNameRadio);

                $('#next-btn-issue').prop('disabled', false);
                // Check if the repair form is visible
                if (!$('#repair-form').hasClass('d-none')) {
                    $('#repair-form').addClass('d-none'); // Hide the form
                }

                // Optional: Clear breadcrumb or other UI elements tied to the form
            });

            // Handle Next Button Click
            $('#next-btn-issue').on('click', function () {
                const visibleLevel = $(`.category-level[data-level="${currentLevel}"]`);
                const selectedRadio = visibleLevel.find('input[type="radio"]:checked');

                if (!selectedRadio.length) return; // No category selected

                const selectedValue = selectedRadio.val();
                const selectedName = selectedRadio.siblings('label').text().trim();

                // Store the selected category for the current level
                selectedCategories[currentLevel] = selectedValue;
                console.log('Selected Categories:', selectedCategories);

                // Fetch Subcategories for the Next Level
                $.ajax({
                    url: "{{ route('admin.property_repairs.getSubCategories', ['categoryId' => '__categoryId__']) }}"
                        .replace('__categoryId__', selectedValue),
                    method: 'GET',
                    success: function (data) {
                        if (data.length) {
                            const nextLevelContainer = $(`[data-level="${currentLevel + 1}"]`);
                            let html = '';
                            data.forEach((category) => {
                                html += `
                                    <div class="col-md-4">
                                        <div class="form-check d-flex align-items-center">
                                            <input class="form-check-input" type="radio" name="category_${currentLevel + 1}" id="category-${category.id}" value="${category.id}">
                                            <label class="form-check-label d-flex align-items-center" for="category-${category.id}">
                                                <i class="fas fa-cogs me-2"></i> ${category.name}
                                            </label>
                                        </div>
                                    </div>
                                `;
                            });

                            nextLevelContainer.find('.row').html(html);
                            $('.category-level').hide(); // Hide all levels
                            nextLevelContainer.show(); // Show next level
                            currentLevel++;
                            $('#prev-btn-issue').removeClass('d-none'); // Show Previous button
                            $('#next-btn-issue').prop('disabled', true); // Disable Next button
                            // Add to breadcrumb
                            updateBreadcrumb(selectedName);
                        } else {
                            // Call checkLastStep to determine if it's the last step
                            checkLastStep(selectedCategories); // Call the function here
                        }


                    },
                    error: function (error) {
                        // Call checkLastStep to determine if it's the last step
                        checkLastStep(selectedCategories); // Call the function here
                        // if (error.responseJSON && error.responseJSON.message) {
                        //     AIZ.plugins.notify('error', error.responseJSON.message); // Show error message using AIZ notification
                        // } else {
                        //     AIZ.plugins.notify('error', 'An unknown error occurred while fetching subcategories.'); // Default error message
                        // }
                    }
                });
            });

            // Handle Previous Button Click
            $('#prev-btn-issue').on('click', function () {
                if (currentLevel > 1) {
                    // Remove the current level's selected category
                    delete selectedCategories[currentLevel];
                    console.log('Selected Categories:', selectedCategories);
                    $(`[data-level="${currentLevel}"]`).hide().find('.row')
                        .empty(); // Hide and clear current level
                    currentLevel--;
                    $(`[data-level="${currentLevel}"]`).show(); // Show previous level
                    $('ol.breadcrumb li').last().remove(); // Remove last breadcrumb

                    if (currentLevel === 1) {
                        $('#prev-btn-issue').addClass('d-none'); // Hide Previous button if on first level
                    }
                    $('#next-btn-issue').prop('disabled', false); // Enable Next button
                }

                if (!$('#repair-form').hasClass('d-none')) {
                    $('#repair-form').addClass('d-none'); // Hide the form
                }


            });

        });
    </script>
    @endsection
