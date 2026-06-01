@extends('backend.layout.app')

@section('content')
<div class="container">
    <h1>Report a Repair</h1>

    <!-- Main Form -->
    <form method="POST" action="{{ route('admin.property_repairs.store') }}" id="repair-form-page">
        @csrf
        <input type="hidden" name="repair_navigation" id="selected_categories">
        <input type="hidden" name="repair_category_id" id="last_selected_category">

        <div class="steps_wrapper">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Select Property</li>
                    </ol>
                </nav>
                <!-- Navigation Buttons (global for all steps) -->
                <div class="d-flex gap-2">
                    <button id="prev-btn" class="btn btn-secondary" disabled>Previous</button>
                    <button id="next-btn" class="btn btn-primary" disabled>Next</button>
                </div>
            </div>

            <!-- Step 1: Property Search -->
            <div id="step1" class="step">
                <div class="row justify-content-center align-items-center">
                    <h2 class="text-center my-3">Where is the problem?</h2>
                    <div class="col-md-6">
                        <div class="form-group text-center mt-lg-0 mt-4">
                            <label class="mb-2" for="search_property1">Search And Select Property</label>
                            <!-- Search Input -->
                            <div class="form-group">
                                <div class="rs_input input_search position-relative">
                                    <div class="right_icon position-absolute top-50 translate-middle-y end-0 pe-2">
                                        <i class="bi bi-search"></i>
                                    </div>
                                    <input type="text" id="search_property1" placeholder="Search Property" class="form-control search_property" />
                                </div>
                                <div id="error_message" class="mt-1 text-danger" style="display: none;"></div>
                            </div>
                            <!-- Search Results -->
                            <ul id="property_results" class="list-group mt-2"></ul>
                            <!-- Hidden field for selected property IDs -->
                            <input type="hidden" id="selected_properties" name="property_id" value="{{ json_encode(isset($selectedProperties) ? $selectedProperties : []) }}">
                        </div>
                    </div>
                    <!-- Dynamic Property Table -->
                    <div id="dynamic_property_table" class="d-none mt-4">
                        @php
                            $headers = ['Address', 'Type', 'Availability', 'Actions'];
                            $rows = []; // Initially empty
                        @endphp
                        <x-backend.dynamic-table :headers="$headers" :rows="$rows" class="user_add_property" />
                    </div>
                </div>
            </div>

        <!-- Step 2: Category Selection -->
        <div id="step2" class="step d-none">
            <h3 class="text-center my-3">What type of problem are you facing?</h3>
            <p class="text-center my-3">Select the area in your house where you are facing a problem.</p>

            <!-- Category Main View -->
            <div id="category-main-view" class="main-view">
                <!-- Level 1 Categories: Rendered on page load -->
                <div class="category-level" data-level="1">
                    <div class="row">
                        @foreach ($categories as $category)
                            @if(is_null($category->parent_id))  {{-- Only parent categories --}}
                                <div class="col-md-4 mb-2">
                                    <div class="form-check d-flex align-items-center">
                                        <input class="form-check-input" type="radio" name="category_1" id="repair-{{ $category->id }}" value="{{ $category->id }}">
                                        <label class="form-check-label d-flex align-items-center" for="repair-{{ $category->id }}">
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
        </div>


            <!-- Step 3: Confirmation Form -->
            <div id="step3" class="step d-none">
                <div id="repair-form" class="d-block">
                    @include('backend.repair.common_form')
                    <!-- Final Submit Button -->
                    <button type="submit" class="btn btn-primary mt-3">Submit</button>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection

@section('page.scripts')
{{-- @stack('domready.scripts') --}}
<script>
    $(document).ready(function () {

        // ----------------- GLOBAL VARIABLES ----------------- //
        let currentStep = 1;          // 1: Property, 2: Category, 3: Final form
        let currentLevel = 1;         // Current category level (starting at 1)
        let selectedProperty = null;  // The selected property id
        let selectedCategories = {};  // e.g., { "level_1": "5", "level_2": "9" }
        let breadcrumbItems = [];     // e.g., [ 'Select Properties', 'Select Category', 'Kitchen' ]
        let allCategories = {};       // Global variable to hold all categories grouped by parent_id

        // ----------------- FETCH ALL CATEGORIES ON PAGE LOAD ----------------- //
        $.ajax({
            url: "{{ route('admin.get.repair.categories') }}",
            method: 'GET',
            async: false,  // For simplicity; consider using Promises for production
            success: function(data) {
                allCategories = data;
                console.log("All Categories Loaded:", allCategories);
            },
            error: function() {
                console.log("Error fetching categories.");
            }
        });


        // ----------------- BREADCRUMB HANDLERS ----------------- //

        // Rebuild breadcrumb UI from the breadcrumbItems array.
        function updateBreadcrumbUI(){
            let html = '';
            breadcrumbItems.forEach((item, index) => {
                // If not the current (last) item then make clickable.
                if (index < breadcrumbItems.length - 1) {
                    html += `<li class="breadcrumb-item clickable" data-index="${index}" style="cursor:pointer;">${item}</li>`;
                } else {
                    // Final item (active) is not clickable.
                    html += `<li class="breadcrumb-item active" aria-current="page" data-index="${index}">${item}</li>`;
                }
            });
            $('ol.breadcrumb').html(html);
        }

        // Reset breadcrumb for Step 1.
        function resetBreadcrumb() {
            breadcrumbItems = ['Select Property'];
            updateBreadcrumbUI();
        }

        // Initialize breadcrumb for entering Step 2 (category selection).
        function initCategoryBreadcrumb() {
            breadcrumbItems = ['Select Property', 'Select Category'];
            updateBreadcrumbUI();
        }

        // Add an item to the breadcrumb.
        function pushBreadcrumb(name) {
            breadcrumbItems.push(name);
            updateBreadcrumbUI();
        }

        // Remove the last breadcrumb item.
        function popBreadcrumb() {
            breadcrumbItems.pop();
            updateBreadcrumbUI();
        }

        // ----------------- STEP NAVIGATION HELPER FUNCTIONS ----------------- //

        // Show a given step (hide all others).
        function showStep(step) {
            $(".step").addClass("d-none");
            $("#step" + step).removeClass("d-none");
        }

        // Enable/Disable main navigation buttons.
        function enableNextButton() { $("#next-btn").prop("disabled", false); }
        function disableNextButton() { $("#next-btn").prop("disabled", true); }
        function enablePreviousButton() { $("#prev-btn").prop("disabled", false); }
        function disablePreviousButton() { $("#prev-btn").prop("disabled", true); }

        // ----------------- INITIALIZATION ----------------- //

        // Start with Step 1 (Property selection).
        showStep(1);
        disableNextButton();
        disablePreviousButton();
        resetBreadcrumb();

        // ----------------- STEP 1: PROPERTY SELECTION ----------------- //

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
                enableNextButton();
            }
            $('#property_results').empty();
            $('#search_property1').val('');
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

        // ----------------- STEP 2: CATEGORY SELECTION ----------------- //

        // When a category radio is selected, enable Next.
        $(document).on('change', '.category-level input[type="radio"]', function(){
            enableNextButton();
        });

        // ----------------- MAIN NAVIGATION BUTTON HANDLERS ----------------- //

        $("#next-btn").on("click", function () {
            console.log(currentStep);
            // STEP 1 -> STEP 2
            if (currentStep === 1) {
                if (selectedProperty) {
                    currentStep = 2;
                    currentLevel = 1;
                    selectedCategories = {};
                    breadcrumbItems = ['Select Properties', 'Select Category']; // Reset breadcrumb properly

                    console.log("Resetting categories:", selectedCategories);
                    console.log("Resetting breadcrumb:", breadcrumbItems);
                    // Hide all category-level views, then show the one corresponding to currentLevel.
                    $(".category-level").hide();
                    $(`[data-level="${currentLevel}"]`).show();
                    updateBreadcrumbUI();

                    // initCategoryBreadcrumb(); // Set breadcrumb for category selection.
                    showStep(2);
                    disableNextButton(); // Wait until a category is chosen.
                    enablePreviousButton();
                }
            }
            // STEP 2: Process current category selection.
            else if (currentStep === 2) {

                var visibleLevel = $(`.category-level[data-level="${currentLevel}"]`);
                var selectedRadio = visibleLevel.find('input[type="radio"]:checked');
                if (!selectedRadio.length) return;
                var selectedValue = selectedRadio.val();
                var selectedName = selectedRadio.siblings('label').text().trim();
                // Save the selection.
                selectedCategories[`level_${currentLevel}`] = selectedValue;

                console.log(selectedCategories);
                // Before pushing the new selection, replace any existing selection at this level:
                breadcrumbItems = breadcrumbItems.slice(0, currentLevel);
                pushBreadcrumb(selectedName);
                // Look for child categories using our preloaded global variable
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
                    disableNextButton();
                } else {
                    // No further children: move to final step
                    currentStep = 3;
                    showStep(3);
                    disableNextButton();
                }
                // Update hidden fields for final form submission (if applicable)
                $("#selected_categories").val(JSON.stringify(selectedCategories));
                let lastCategory = selectedCategories[`level_${currentLevel}`];
                $("#last_selected_category").val(lastCategory);

                // $.ajax({
                //     url: "{{ route('admin.property_repairs.getSubCategories', ['categoryId' => '__categoryId__']) }}"
                //         .replace('__categoryId__', selectedValue),
                //     method: 'GET',
                //     success: function (data) {
                //         if (data.length) {
                //             // Load the next level.
                //             var nextLevel = currentLevel + 1;
                //             var nextLevelContainer = $(`[data-level="${nextLevel}"]`);
                //             let html = '';
                //             data.forEach(function(category) {
                //                 html += `
                //                     <div class="col-md-4 mb-2">
                //                         <div class="form-check d-flex align-items-center">
                //                             <input class="form-check-input" type="radio" name="category_${nextLevel}" id="category-${category.id}" value="${category.id}">
                //                             <label class="form-check-label d-flex align-items-center" for="category-${category.id}">
                //                                 <i class="fas fa-cogs me-2"></i> ${category.name}
                //                             </label>
                //                         </div>
                //                     </div>
                //                 `;
                //             });
                //             nextLevelContainer.find('.row').html(html);
                //             // Hide current level and show next level.
                //             visibleLevel.hide();
                //             nextLevelContainer.show();
                //             currentLevel++;
                //             disableNextButton();
                //         } else {

                //             // No further subcategories – move to Step 3.
                //             currentStep = 3;
                //             showStep(3);
                //             disableNextButton();
                //         }
                //         // Capture last selected category
                //         let lastCategory = selectedCategories[`level_${currentLevel}`];
                //         $("#selected_categories").val(JSON.stringify(selectedCategories)); // Convert categories object to JSON
                //         $("#last_selected_category").val(lastCategory);
                //     },
                //     error: function (error) {
                //         // On error, assume no further subcategories:
                //         currentStep = 3;
                //         showStep(3);
                //         disableNextButton();
                //     }
                // });
            }
            // STEP 3: Final form submission.
            else if (currentStep === 3) {
                $("#repair-form-page").submit();
            }
        });

        $("#prev-btn").on("click", function () {
            // If coming back from Step 3 to Step 2:
            if (currentStep === 3) {
                currentStep = 2;
                showStep(2);
                // Remove extra breadcrumb items if necessary.
                // (For example, if there was a "Report Issue" item, remove it here.)
                // if(breadcrumbItems[breadcrumbItems.length-1] === "Report Issue"){
                //     popBreadcrumb();
                // }
                enableNextButton();
            }
            // In Step 2:
            else if (currentStep === 2) {
                if (currentLevel > 1) {
                    // Hide and clear the current level.
                    $(`[data-level="${currentLevel}"]`).hide().find('.row').empty();
                    delete selectedCategories[`level_${currentLevel}`];
                    console.log(selectedCategories);
                    currentLevel--;
                    // Show the previous level.
                    $(`[data-level="${currentLevel}"]`).show();
                    popBreadcrumb();
                    enableNextButton();
                } else {
                    // If at the very first category level, return to Step 1.
                    currentStep = 1;
                    showStep(1);
                    disablePreviousButton();
                    resetBreadcrumb();
                    if (!selectedProperty) {
                        disableNextButton();
                    } else {
                        enableNextButton();
                    }
                }
            }
        });

        // ----------------- BREADCRUMB CLICK HANDLING ----------------- //
        // Clicking on a breadcrumb item navigates the user back to that step/level.
        $('ol.breadcrumb').on('click', 'li.clickable', function () {
            var index = $(this).data('index');
            // If the first breadcrumb item ("Select Properties") is clicked,
            // go back to Step 1.
            if (index === 0) {
                currentStep = 1;
                showStep(1);
                resetBreadcrumb();
                disablePreviousButton();
                if (!selectedProperty) {
                    disableNextButton();
                } else {
                    enableNextButton();
                }
                currentLevel = 1;
                console.log(currentLevel);
            } else {
                // Otherwise, ensure that we are in Step 2.
                currentStep = 2;
                showStep(2); // Force showing Step 2 (hide Step 3 if active)

                // Delete selected categories for levels beyond the clicked one.
                for (let lvl = index + 1; lvl <= currentLevel; lvl++) {
                    delete selectedCategories[`level_${lvl}`];
                }
                console.log(selectedCategories);
                // Set the category level based on the breadcrumb index.
                // Note: index 1 corresponds to category level 1,
                // index 2 corresponds to level 1 selection, etc.
                currentLevel = index;
                console.log(currentLevel);
                // Remove all breadcrumb items after the clicked one.
                breadcrumbItems = breadcrumbItems.slice(0, index + 1);
                updateBreadcrumbUI();
                // Hide all category-level views, then show the one corresponding to currentLevel.
                $(".category-level").hide();
                $(`[data-level="${currentLevel}"]`).show();

                enableNextButton();
            }
        });

    });
</script>
@endsection
