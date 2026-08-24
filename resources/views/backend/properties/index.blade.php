@extends('backend.layout.app')

@section('content')
    <div class="row g-0 view_properties">
        <div class="col-lg-5 col-12">
            <div class="property_list_wrapper pt-lg-4 pt-2 ">
                <div class="pv_wrapper">
                    <div class="pv_header">
                        <div class="row">
                            <div class="col-3">
                                <div class="pv_title">Properties</div>
                            </div>
                            <div class="col-9">
                                <x-backend.forms.search 
                                    class='' 
                                    placeholder='Search properties...' 
                                    value='{{ request("search") }}'
                                    onClick='' 
                                    id='propertySearch'
                                />
                            </div>
                            @can('create properties')
                            <div class="pv_btn">
                                <a href="{{ route('admin.properties.quick') }}" class="btn mt-2 btn-sm btn-outline-danger">
                                    Add Property
                                </a>
                            </div>
                            @endcan
                            {{-- <div class="pv_btn">
                                <x-backend.forms.button class='' name='Add Property' type='secondary' size='sm'
                                    isOutline={{ false }} isLinkBtn={{ true }}
                                    link="{{ route('admin.properties.quick') }}" onClick='onClick()' />
                            </div> --}}
                        </div>

                    </div>
                    {{-- pv_header end --}}
                    <div class="pv_card_wrapper" id="propertyListContainer">
                        @include('backend.properties.partials.property-list')
                    </div>
                    {{-- pv_card_wrapper end  --}}
                </div>
                {{-- pv_wrapper end  --}}
            </div>
        </div>
        <div class="col-lg-7 col-12 property_detail_wrapper hide_this pt-lg-4 pt-0">
            <div class="pv_detail_wrapper">

                <x-backend.properties-tabs :tabs="$tabs" class="poperty_tabs" />

                <div class="pv_detail_content">
                    <div class="pv_detail_header">
                        <div class="pv_main_title">{{ ucfirst($tabName) }} Detail</div>
                        <div class="pvdh_btns_wrapper d-flex gap-3">
                            {{-- <x-backend.link-button class="tab-owners-btn popup-tab-owners-create d-none" name="Add Owner"
                                link="{{ route('admin.owner-groups.create') }}" onClick="" /> --}}
                            {{-- <x-backend.forms.button
                                class="tab-owners-btn d-none"
                                name="Add Owner"
                                type="secondary"
                                size="sm"
                                isOutline={{false}}
                                isLinkBtn={{true}}
                                link="{{ route('admin.owner-groups.create') }}"
                                onclick=""
                                /> --}}
                            {{-- <x-backend.link-button class="tab-offers-btn popup-tab-offer-create d-none" name="Add Offer"
                                link="{{ route('admin.properties.quick') }}" onClick="" /> --}}
                            {{-- <x-backend.forms.button
                                    class="tab-offers-btn d-none"
                                    name="Add Offer"
                                    type="secondary"
                                    size="sm"
                                    isOutline={{false}}
                                    isLinkBtn={{true}}
                                    link="#"
                                    onclick=""
                                    /> --}}

                            <!-- Modal Trigger Button -->
                            <a type="button" class="tab-offers-btn btn btn-sm btn-outline-danger btn-sm d-none" data-bs-toggle="modal"
                                data-bs-target="#addOfferModal">
                                Add Offer
                            </a>
                            {{-- <a data-url="{{ route('admin.owner-groups.create') }}" class="popup-tab-owners-create btn btn_secondary btn-sm tab-owners-btn d-none">
                                        <span>Add Owner</span>
                                        <span class="icon_btn"></span>
                                    </a> --}}
                            @unless(auth()->user()->hasRole('Tenant'))
                            <a data-url="{{ route('admin.owner-groups.create_group') }}"
                                class="popup-tab-owner-group-create btn btn-sm btn-outline-danger btn-sm tab-owners-group-btn d-none">
                                <span>Add Owner Group</span>
                                <span class="icon_btn"></span>
                            </a>
                            <a data-url="{{ route('admin.tenancies.create') }}"
                                class="popup-tab-tenancy-create btn btn-sm btn-outline-danger tab-tenancy-group-btn d-none">
                                <span>Add Tenancy</span>
                                <span class="icon_btn"></span>
                            </a>
                            @endunless

                            {{-- @if (isset($property) && isset($propertyId)) --}}
                            {{-- <x-backend.outline-link-button class="" name="Edit Property"
                                    link="{{ route('admin.properties.edit', ['id' => $property->id]) }}" onClick="" /> --}}
                            <x-backend.forms.button class="edit-property-btn d-none" name="Edit Property" type="secondary"
                                size="sm" isOutline={{ false }} isLinkBtn={{ true }}
                                {{-- link="{{ route('admin.properties.edit', ['id' => $propertyId]) }}" --}} link="#" onclick="" />
                            {{-- @endif --}}
                        </div>
                    </div>
                    <div class="pv_content_detail_wrapper">
                        <i class="bi bi-chevron-left" id="backBtn"></i>
                        <div class="pv_content_detail">
                            {!! $content !!}
                            <!-- The dynamic tab content will be injected here by AJAX -->
                            {{-- render first tabs blade file from view example @include('backend.properties.tabs' . $tabname) $tabname in small case --}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="mobile_footer mobile_only">
                <div class="pvdh_btns_wrapper">
                    <x-backend.forms.mobile_button class='' name='Add Tenacy'
                        link="{{ route('admin.properties.quick') }}" iconName='plus-circle' />
                    <x-backend.forms.mobile_button class='' name='Add Offer'
                        link="{{ route('admin.properties.quick') }}" iconName='journal-plus' />
                    @if ($property)
                        <x-backend.forms.mobile_button class='' name='Edit Property'
                            link="{{ route('admin.properties.edit', ['id' => $property->id]) }}"
                            iconName='pencil-square' />
                    @endif
                </div>
            </div>
        </div>
    </div>
    <style>
        .hidden {
            display: none !important;
        }

        /* .modal-content {
            max-width: 900px;
            margin: auto;
        } */
        .modal-content {
            height: auto;
            margin: auto;
        }
        .property-brochure-btn {
            display: inline-flex !important;
            position: relative;
            z-index: 2;
            white-space: nowrap;
            width: auto !important;
            min-width: unset !important;
            max-width: max-content;
            padding: 2px 7px;
            font-size: 12px;
            line-height: 1.4;
            color: #0d6efd !important;
            background: #fff !important;
            border-color: #0d6efd !important;
        }
        .property-brochure-btn:hover,
        .property-brochure-btn:focus,
        .pv_content_wrapper:hover .property-brochure-btn,
        .pv_content_wrapper.current .property-brochure-btn {
            color: #fff !important;
            background: #0d6efd !important;
            border-color: #0d6efd !important;
            opacity: 1 !important;
        }
        .property_brochure {
            position: absolute;
            right: 20px;
            bottom: 14px;
            width: auto;
        }
        .pv_content_wrapper.property-card {
            position: relative;
            padding-bottom: 48px !important;
        }

        .add-tenant-btn {
            color: #ff4500;
            cursor: pointer;
            text-decoration: underline;
        }

        .modal-backdrop.modal-stack {
            opacity: 0.3 !important;
        }
    </style>
    <!-- property offer add Modal -->
    <div class="modal fade" id="addOfferModal" tabindex="-1" aria-labelledby="addOfferModal-label" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addOfferModal-label">Add Offer</h5>
                    <a type="button" class="btn-close" onclick="closeModel();" data-bs-dismiss="modal"
                        aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <!-- Main Form -->
                    <form action="{{ route('admin.offers.store') }}" method="POST" class="tenantOfferForm"
                        id="tenantOfferForm">
                        @csrf
                        <input type="hidden" name="property_id" class="form-control" value="">
                        <!-- Steps Container -->
                        <div id="steps-container">
                            <input type="hidden" id="mainPersonId" name="mainPersonId">
                            <div class="mb-3">
                                <label for="existingTenantIds" class="form-label">Search existing contacts</label>
                                <select id="existingTenantIds" name="existing_tenant_ids[]" class="form-control" multiple
                                    data-url="{{ route('admin.users.ajax') }}"></select>
                                <small class="text-muted">Select existing applicants, or leave empty to add a new contact below.</small>
                            </div>
                            <div class="mb-3 d-none" id="existingMainTenantGroup">
                                <label for="mainExistingTenantId" class="form-label">Main applicant</label>
                                <select id="mainExistingTenantId" name="main_existing_tenant_id" class="form-control"></select>
                            </div>
                            <!-- Tenant Forms -->
                            <div id="tenant-forms" class="step"></div>

                            <!-- Offer Details Step -->
                            <div id="offer-step" class="step hidden">
                                <h6>Offer Details</h6>
                                <div class="row">
                                    <div class="col-lg-6 col-12">
                                        <div class="mb-3">
                                            <div class="form-group">
                                                <label for="price" class="form-label">Price</label>
                                                <input type="number" class="form-control" id="price" name="price"
                                                    placeholder="Enter price" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-12">
                                        <div class="mb-3">
                                            <div class="form-group">
                                                <label for="deposit" class="form-label">Deposit</label>
                                                <input type="number" class="form-control" id="deposit" name="deposit"
                                                    placeholder="Enter deposit amount" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 col-12">
                                        <div class="mb-3">
                                            <div class="form-group">
                                                <label for="term" class="form-label">Term</label>
                                                <input type="text" class="form-control" id="term" name="term"
                                                    placeholder="Enter term" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-12">
                                        <div class="mb-3">
                                            <div class="form-group">
                                                <label for="move_in_date" class="form-label">Move-in Date</label>
                                                <input type="date" class="form-control" id="moveInDate"
                                                    name="moveInDate" required>
                                            </div>
                                        </div>
                                    </div>



                                </div>
                            </div>
                    </form>
                    <span id="addTenantButton" class="add-tenant-btn hidden" onclick="addTenant()">Add More Tenant</span>
                </div>
                <!-- Modal Footer Navigation -->
                <div class="modal-footer px-0">
                    <button type="button" class="btn btn_outline_secondary btn-sm" data-bs-dismiss="modal"
                        aria-label="Close">Cancel</button>
                    <button id="backButton" type="button" class="btn btn_secondary btn-md hidden">Back</button>
                    <button id="nextButton" type="button" class="btn btn_secondary btn-md ">Next</button>
                    <button id="submitButton" type="submit" form="tenantOfferForm"
                        class="btn btn_secondary btn-md hidden">Submit</button>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Include the Modal Component -->
    @include('backend.components.modal')
    @include('backend.events.modal')
    @include('backend.partials._calendar_modals')
    <div class="modal fade" id="importantNoteVisitModal" tabindex="-1" aria-labelledby="importantNoteVisitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importantNoteVisitModalLabel">Important Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="importantNoteVisitContent" style="white-space: pre-wrap;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn_secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.js"></script>

<script type="module">
    import { RRule } from 'https://cdn.skypack.dev/rrule';
    window.RRule = RRule;
</script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('/asset/backend/js/property-offer.js') }}"></script>
<script src="{{ asset('/asset/backend/js/common-notes.js') }}"></script>
<script src="{{ asset('/asset/backend/js/common-documents.js') }}"></script>
@endpush
@section('page.scripts')
@if (isset($propertyId) && isset($property) && $propertyId != $property->id)
{{-- @php
var_dump($propertyId);
@endphp --}}
    <script>
    const url = new URL(window.location.href);
    url.searchParams.set('property_id', '{{ $propertyId }}');
    history.replaceState(null, '', url.toString());
</script>
@endif

<script>
    // Global delete functions for tab content (tabs load via jQuery .html() which strips scripts)
    var _deleteTenancyUrl = null;
    var _deleteTenancyBtn = null;

    function deleteTenancy(url, btn) {
        _deleteTenancyUrl = url;
        _deleteTenancyBtn = btn;
        $('#confirmModal').modal('show');
    }

    // Wire the confirmModal Continue button for tenancy deletes
    $(document).on('click', '#delete_form button[type="submit"]', function(e) {
        if (!_deleteTenancyUrl) return; // not a tenancy delete
        e.preventDefault();
        e.stopImmediatePropagation();
        $('#confirmModal').modal('hide');
        if (_deleteTenancyBtn) _deleteTenancyBtn.disabled = true;
        $.ajax({
            type: 'POST',
            url: _deleteTenancyUrl,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                _deleteTenancyUrl = null;
                _deleteTenancyBtn = null;
                location.reload();
            },
            error: function () {
                if (_deleteTenancyBtn) _deleteTenancyBtn.disabled = false;
                _deleteTenancyUrl = null;
                _deleteTenancyBtn = null;
                alert('Failed to delete. Please try again.');
            }
        });
    });
</script>

<script>
    function uploadImageToServer(file, editor) {
        let formData = new FormData();
        formData.append("file", file);

        $.ajax({
            url: "{{ route('notes.upload_image') }}",
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                if (data.url) {
                    editor.summernote('insertImage', data.url);
                }
            },
            error: function (err) {
                console.error("Upload failed:", err.responseText);
                alert("Image upload failed.");
            }
        });
    }

    var responseHandler = function(response) {
        location.reload();
    }

        function handleGasSafeModal() {
            // Check initially on page load
            if ($('#gas_safe_acknowledged').val() !== '1' && $('#is_gas_no').is(':checked')) {
                // If "No" is selected and gas acknowledgment is not 1, show the modal
                $('#smallModal2').modal('show');
            }

            // Event delegation for changes to the radio buttons
            $(document).on('change', 'input[name="is_gas"]', function() {
                const selected = $('input[name="is_gas"]:checked').val(); // Get the value of the selected radio

                if (selected === '1') { // Gas = Yes
                    console.log('Gas = Yes selected');
                    if ($('#gas_safe_acknowledged').val() !== '1') {
                        $('#smallModal2').modal('show');
                    }
                } else if (selected === '0') { // Gas = No
                    console.log('Gas = No selected');
                    $('#gas_safe_acknowledged').val('0'); // Reset acknowledgment if "No" is selected
                }
            });

            // Confirm Acknowledgement
            $(document).on('click', '#confirm_gas', function(event) {
                event.preventDefault();
                $('#gas_safe_acknowledged').val('1'); // Set acknowledgment
                $('#smallModal2').modal('hide'); // Hide the modal
            });

            // Cancel button click
            $(document).on('click', '#cancel_gas', function(event) {
                event.preventDefault();
                // Set the "No" radio button for "is_gas"
                $('#is_gas_no').prop('checked', true); // Select the "No" option
                // Reset the hidden input value
                $('#gas_safe_acknowledged').val('0'); // Reset the acknowledgment to 0
                $('#smallModal2').modal('hide'); // Hide the modal
            });
        }

        // Global close button function
        function closeModal() {
            $('#smallModal2').modal('hide'); // Close the modal
        }

        // Call the handler
        handleGasSafeModal();

        function openImageModal(imageSrc) {
            $("#previewImage").attr("src", imageSrc); // Set image source
            $("#imagePreviewModal").modal("show"); // Show modal
        }

        // Hide modal when close button is clicked
        $("#closeModalBtn").click(function() {
            $("#imagePreviewModal").modal("hide");
        });

        // Hide modal when clicking outside modal content
        $(document).on("click", function(event) {
            if (!$(event.target).closest(".modal-content").length) {
                $("#imagePreviewModal").modal("hide");
            }
        });

        // Utility function to initialize Tagify dynamically based on data attributes
        function initDynamicTagify() {
            $('.tagify-input').each(function() {
                let $inputElement = $(this);
                let values = $inputElement.data('values') || ''; // Pre-selected values
                let options = $inputElement.data('options') || {}; // Max tags, dropdown options
                let idValue = $inputElement.data('id-value') || []; // ID-Value pairs

                let data = idValue; // Use the provided ID-Value data

                // Parse the pre-selected values
                // let selectedIds = [];
                // if (typeof values === 'string' && values.includes(',')) {
                //     selectedIds = values.split(',').map(id => id.trim());
                // } else if (typeof values === 'string' && (values.startsWith('{') || values.startsWith('['))) {
                //     try {
                //         selectedIds = JSON.parse(values).map(item => item.trim());
                //     } catch (e) {
                //         console.error("Error parsing data-values:", e);
                //         selectedIds = [];
                //     }
                // } else if (values) {
                //     selectedIds = [values.trim()];
                // }
                let selectedIds = [];

                if (Array.isArray(values)) {
                    selectedIds = values.map(id => id.toString().trim());
                } else if (typeof values === 'string') {
                    const trimmed = values.trim();

                    if (trimmed.startsWith('[') || trimmed.startsWith('{')) {
                        try {
                            let parsed = JSON.parse(trimmed);
                            if (Array.isArray(parsed)) {
                                selectedIds = parsed.map(id => id.toString().trim());
                            } else {
                                selectedIds = [parsed.toString().trim()];
                            }
                        } catch (e) {
                            console.error("Error parsing data-values JSON:", e);
                        }
                    } else if (trimmed.includes(',')) {
                        selectedIds = trimmed.split(',').map(id => id.trim());
                    } else if (trimmed) {
                        selectedIds = [trimmed];
                    }
                } else if (typeof values === 'number') {
                    selectedIds = [values.toString()];
                }


                // Initialize Tagify
                let tagify = new Tagify($inputElement[0], {
                    whitelist: data.map(item => item.name),
                    maxTags: options.maxTags || 5,
                    dropdown: {
                        enabled: options.dropdownEnabled === 1,
                        maxItems: options.maxItems || 10,
                        searchKeys: options.searchKeys || ['name'],
                        closeOnSelect: options.closeOnSelect || false,
                    },
                    pattern: /[\w\s]/,
                });

                // Populate Tagify with existing selected items
                let selectedNames = selectedIds.map(id => {
                    let item = data.find(item => item.id == id);
                    return item ? item.name : '';
                }).filter(name => name);

                tagify.addTags(selectedNames);

                // Update the hidden input field
                let $hiddenInput = $inputElement.closest('.form-group').find('.hidden-input');
                $hiddenInput.val(selectedIds.join(','));

                // Handle adding a new tag
                tagify.on('add', function(e) {
                    let newTag = e.detail.data;
                    let selectedItem = data.find(item => item.name === newTag.value);
                    if (selectedItem) {
                        let selectedIds = tagify.value.map(tag => {
                            let item = data.find(item => item.name === tag.value);
                            return item ? item.id : null;
                        });
                        $hiddenInput.val(selectedIds.join(','));
                    }
                });

                // Handle removing a tag
                tagify.on('remove', function(e) {
                    let removedTag = e.detail.data;
                    let selectedItem = data.find(item => item.name === removedTag.value);
                    if (selectedItem) {
                        let selectedIds = tagify.value.map(tag => {
                            let item = data.find(item => item.name === tag.value);
                            return item ? item.id : null;
                        });
                        $hiddenInput.val(selectedIds.join(','));
                    }
                });
            });
        }



    // Open modal and load form via AJAX
    $(document).on('click', '.editForm, .addForm', function() {
        let formType = $(this).data("form");
        let propertyId = $(this).data("id");
        let noteId     = $(this).data('note-id') || '';
        let formTitles = {
            "availability_pricing": "Edit Availability & Pricing",
            "property_info": "Edit Property Information",
            "property_features": "Edit Property Features",
            "property_compliance": "Edit Compliance Details",
            "property_media": "Edit Media Details",
            "property_accessibility": "Edit Property Accessibility",
            "property_services": "Edit Property Services",
            "property_status": "Edit Property Status",
            "property_description": "Edit Description",
            "responsibility": "Edit Responsibility Mapping",
            "notes": "Edit Important Note",
            notes_tab: noteId ? 'Edit Note' : 'Add Note',
        };
        
        let modalTitle = formTitles[formType] || "Edit Details"; // Default title if form type is not found

        $("#extraLargeModal .modal-title").html(modalTitle); // Set dynamic title

        // Remove previous modal size classes
        $("#extraLargeModal .modal-dialog").removeClass("modal-sm modal-lg modal-xl");

        // Apply the appropriate modal size based on the formType
        if (formType === "property_status" || formType === "notes" || formType === "property_services" || formType === "property_description") {
            // Use small modal for "notes" or "notes_tab"
            $("#extraLargeModal .modal-dialog").addClass("modal-md");
        // } else if (formType === "property_info") {
            // Use large modal for "property_details" or "availability_pricing"
            // $("#extraLargeModal .modal-dialog").addClass("modal-lg");
        } else {
            // Default size (medium size) for other forms
            $("#extraLargeModal .modal-dialog").addClass("modal-xl");
        }

        $.ajax({
            url: "{{ route('admin.properties.loadForm') }}", // Route to get form dynamically
            type: "GET",
            data: { form_type: formType, property_id: propertyId, note_id: noteId },
            success: function (response) {
                $("#extraLargeModal .modal-body").html(response.form_html);
                $("#extraLargeModal").modal("show");

                    // **Trigger the function ONLY for a specific form**
                    if (formType === "property_compliance") {
                    $('.select2').select2();
                    toggleEPCRating();
                }
                if (formType === "property_media") {
                        AIZ.uploader.previewGenerate();
                    }
                    if (formType === "property_accessibility") {
                        initDynamicTagify();
                    // initPlaces('#places-wrapper', '#add-place-btn');
                    AIZ.extra.addMore();
                    AIZ.extra.removeParent();
                    }
                    if (formType === "availability_pricing") {
                        $('.select2').select2();
                    }
                if (formType === "notes_tab") {
                    AIZ.plugins.textEditor();
                }
                if (formType === "property_info") {
                    toggleDescriptions();
                }
                if (formType === "responsibility") {
                    $('.select2').select2();
                }
                },
                error: function(error) {
                    console.error(error);
                    let errorMessage = error.responseJSON?.message ||
                        'An error occurred while saving the compliance record.';
                    AIZ.plugins.notify('danger', errorMessage);
                }
            });
        });
        $(document).on("submit", "#extraLargeModal form", function(e) {
            if ($(this).is('#editTenancyForm, #addTenancyForm, #owner-group-form')) {
                return;
            }

            e.preventDefault();

            let form = $(this);
            let formData = form.serialize();
            let formType = form.find('input[name="form_type"]').val(); // Get form type dynamically
            let propertyId = form.find('input[name="property_id"]').val(); // Get property ID

        $.ajax({
            url: "{{ route('admin.properties.saveForm') }}",
            type: "POST",
            data: formData,
            success: function (response) {
                console.log(response);
                console.log("Form Type:", formType);
                console.log("Property ID:", propertyId);
                // Check if the response indicates success
                if (response.success) {
                    // Dynamically update the relevant accordion section
                    if (formType === "responsibility") {
                        $("#section-" + formType + "-" + propertyId).replaceWith(response.updated_html);
                    } else {
                        $("#section-" + formType + "-" + propertyId).html(response.updated_html);
                    }

                    // Close the modal
                    $("#extraLargeModal").modal("hide");
                    AIZ.plugins.notify('success', response.message);
                } else {
                    alert("Error: " + response.error);
                }
            },
            error: function (error) {
                console.error(error);
                let errorMessage = error.responseJSON?.message || 'An error occurred while saving the form.';
                AIZ.plugins.notify('danger', errorMessage);
            }
        });
    });
    
    // “View” button handler
    // $(document).on('click', '.viewNote', function(){
    //     const type    = $(this).data('type');
    //     const content = $(this).data('content');

    //     $("#largeModal .modal-title").html(type);
    //     $("#largeModal .modal-body").html(content);
    //     $("#largeModal").modal("show");
    // });
    $(document).on('click', '.viewNote', function() {
        const noteId = $(this).data('id');
        const noteUrl = $(this).data('url');
        const type   = $(this).data('type');

        $.ajax({
            url: noteUrl,
            method: 'GET',
            success: function(response) {
                $("#extraLargeModal .modal-title").text(type);
                $("#extraLargeModal .modal-body").html(response.content); // show as plain text
                $("#extraLargeModal").modal("show");
            },
            error: function() {
                alert("Failed to load note content.");
            }
        });
    });


    $(document).ready(function() {
        let isExpanded = true; // Initially, all accordions are open
    
        $(document).on('click', '#toggleAll', function() {
            if (isExpanded) {
                $(".accordion-collapse").collapse('hide'); // Collapse all
                $(this).text("Expand All");
            } else {
                $(".accordion-collapse").collapse('show'); // Expand all
                $(this).text("Collapse All");
            }
            isExpanded = !isExpanded; // Toggle state
        });

            // Step 1: Event listener for clicks on the document for the "Add New User" button
            $(document).on('click', '#addUserBtn', function() {
                $('#mainForm').hide(); // Hide the main form
                $('#addUserFormContainer').show(); // Show the Add User form
            });

            // Step 2: Event listener for clicks on the document for the "Back" button
            $(document).on('click', '#backToMainForm', function() {
                $('#addUserFormContainer').hide(); // Hide the Add User form
                $('#mainForm').show(); // Show the main form
            });

            // Step 3: Handle the form submission for adding a new user via AJAX
            $(document).on('submit', '#addUserForm', function(event) {
                event.preventDefault(); // Prevent normal form submission

                // Clear any previous error messages
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                var formData = $(this).serialize(); // Serialize the form data

                $.ajax({
                    url: '{{ route('admin.users.quick_user_store') }}', // Make sure this route exists for adding users
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        // Assuming the response contains the new user's ID and full name
                        if (response.success) {
                            // Add the new user to the dropdown in the main form
                            $('#user_id').append(
                                `<option value="${response.user.id}">${response.user.name}</option>`
                            );

                            // Optionally, select the new user
                            // $('#user_id').val(response.user.id);

                            // Hide the Add User form and show the Main Form
                            $('#addUserFormContainer').hide();
                            $('#mainForm').show();

                            // Reset the Add User form
                            $('#addUserForm')[0].reset();
                        } else {
                            alert('Failed to add user.');
                        }
                    },
                    error: function(xhr) {
                        // Check if the status code is 422 (validation error)
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON
                                .errors; // Assuming errors are structured like this
                            console.log(errors); // Log the errors object for debugging

                            // Clear previous error messages and styling
                            $('input').removeClass('is-invalid');
                            $('.invalid-feedback').remove();

                            // Loop through the errors and display them in the form
                            $.each(errors, function(field, messages) {
                                // Check if the field exists in the form
                                var input = $('#user_' + field);

                                if (input.length >
                                    0) { // Make sure the input field exists
                                    input.addClass(
                                        'is-invalid'
                                        ); // Add the 'is-invalid' class to the field

                                    // Check if the field already has an error message to avoid appending multiple messages
                                    if (input.next('.invalid-feedback').length === 0) {
                                        input.after('<div class="invalid-feedback">' +
                                            messages[0] + '</div>');
                                    }
                                } else {
                                    console.log('Input field with id ' + field +
                                        ' not found!');
                                }
                            });
                        } else {
                            alert('An error occurred while adding the user.');
                        }
                    }


                });
            });
        });




        var responseHandler = function(response) {
            location.reload();
        }
        $(document).ready(function() {

            // Function to check if the device is mobile
            function is_mobile() {
                return (
                    /Mobi|Android/i.test(navigator.userAgent) || $(window).width() < 768
                );
            }

            if (is_mobile()) {
                $(document).on('click', '.property-card', function() {
                    $('#backBtn').addClass('property_bk_btn_show');
                    $('.property_detail_wrapper').removeClass('hide_this');
                    $('.property_list_wrapper').toggleClass('hide_this'); // Hide left column
                    $('.property_detail_wrapper').addClass('show_this'); // Show right column
                });

                $(document).on('click', '#backBtn', function() {
                    $('#backBtn').removeClass('property_bk_btn_show');
                    $('.property_detail_wrapper').addClass('hide_this');
                    $('.property_detail_wrapper').toggleClass('show_this'); // Hide right column
                    $('.property_list_wrapper').toggleClass('hide_this'); // Show left column
                });
            }

            $(document).on('click', '.popup-tab-owners-create', function(e) {
                e.preventDefault(); // Prevent the default action (e.g., following the link)

                // Get the URL from the link (you can dynamically get the URL as needed)
                var url = $(this).attr(
                    'data-url'); // Assuming you're passing the URL in the 'href' attribute
                var header = 'Add Owner'; // You can set a custom header or get it dynamically
                // Access the data-property-id using JavaScript
                var propertyId = document.getElementById('hidden-property-id').getAttribute(
                    'data-property-id') ?? '';

                smallModal(url, header);
                // Ensure the modal content is loaded and then set the property_id in the hidden input field inside the modal form
                $('#smallModal').on('shown.bs.modal', function() {
                    // Set the property_id in the hidden input field inside the modal form
                    $("input[name='property_id']").val(propertyId);
                });
            });

            // Function to handle the AJAX form submission
            function submitOwnerGroupForm(e) {
                var form = $('#owner-group-form');
                var btn = form.find('button[type="submit"]');
                var btn_text = btn.html();

                e.preventDefault(); // Prevent default form submission

                if (!form.find('input[name="property_id"]').val()) {
                    AIZ.plugins.notify('danger', 'Unable to determine the property. Please close the form and try again.');
                    return;
                }

                if (form.find('input[name="is_main"]:checked').length === 0) {
                    AIZ.plugins.notify('danger', 'Please select a main user.');
                    return;
                }

                btn.prop('disabled', true);
                btn.text('Saving...');

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: form.serialize(),
                    success: function(response) {
                        btn.html(btn_text);
                        btn.prop('disabled', false);

                        if (response.status) {
                            AIZ.plugins.notify('success', response.notification);
                            // Close the modal on success
                            $('#smallModal').modal('hide');
                            setTimeout(function() {
                                location.reload(); // Reload the page after 1 second
                            }, 1000);
                        } else {
                            // Handle validation errors
                            if (response.errors) {
                                // Loop through each error and display it
                                $.each(response.errors, function(field, messages) {
                                    AIZ.plugins.notify('danger', messages.join(', ')); // ✅ replaced toastr
                                });
                            }
                            // Check if the response is asking for confirmation
                            if (response.notification.includes(
                                    'Do you want to archive the existing one and activate the new group?'
                                ) ||
                                response.notification.includes(
                                    'Are you sure you want to archive this active owner group?') ||
                                response.notification.includes(
                                    'Do you want to archive it and activate this one?')) {

                                // Display confirmation dialog for archiving the existing group
                                if (confirm(response.notification)) {
                                    // If user confirms, add a hidden field to the form to confirm archiving
                                    $('<input>').attr({
                                        type: 'hidden',
                                        name: 'confirm_archive',
                                        value: 'yes'
                                    }).appendTo(form);

                                    // Resubmit the form with the confirmation
                                    submitOwnerGroupForm(e);
                                }
                            } else {
                                AIZ.plugins.notify('danger', response.notification);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        btn.html(btn_text);
                        btn.prop('disabled', false);

                        let defaultMessage = "There was an error with the form submission. Please try again.";

                        try {
                            const response = xhr.responseJSON || JSON.parse(xhr.responseText);

                            // Show detailed field errors if available
                            if (response.errors) {
                                $.each(response.errors, function(field, messages) {
                                    // Show each field's error message(s)
                                    AIZ.plugins.notify('danger', messages.join(', '));
                                });
                            } else if (response.message) {
                                // Show general message if no field-level errors
                                AIZ.plugins.notify('danger', response.message);
                            } else {
                                AIZ.plugins.notify('danger', defaultMessage);
                            }
                        } catch (e) {
                            // JSON parsing failed or unexpected response
                            AIZ.plugins.notify('danger', defaultMessage);
                        }
                    }

                });
            }

            // Bind the function to the form submission using event delegation
            $(document).on('submit', '#owner-group-form', function(e) {
                submitOwnerGroupForm(e); // Call the submit function when the form is submitted
            });

            $(document).on('click', '.popup-tab-owner-group-create', function(e) {
                e.preventDefault(); // Prevent the default action (e.g., following the link)

                // Get the URL for the modal (you can dynamically fetch it as needed)
                var url = $(this).attr('data-url'); // URL passed in the 'data-url' attribute
                var header = 'Add Owner Group'; // Custom header or dynamic header
                var propertyId = document.getElementById('hidden-property-id').getAttribute(
                    'data-property-id') ?? ''; // Fetch the property_id

                // Initialize fields only after the AJAX response has been inserted into the modal.
                smallModal(url, header, function(modal) {
                    const form = modal.find('#owner-group-form');
                    const userSelect = form.find('#user_id');
                    const userOptionsContainer = form.find('#user-options');

                    form.find("input[name='property_id']").val(propertyId);

                    function renderMainOwnerOptions() {
                        const selectedUsers = userSelect.val() || [];
                        const selectedMainUser = userOptionsContainer
                            .find('input[name="is_main"]:checked')
                            .val();

                        userOptionsContainer.empty();

                        if (selectedUsers.length === 0) {
                            return;
                        }

                        userOptionsContainer.append(
                            '<label class="mb-2">Select Main User <span class="text-danger">*</span></label>'
                        );

                        selectedUsers.forEach(function(userId) {
                            const userName = userSelect
                                .find('option[value="' + userId + '"]')
                                .text();
                            const isChecked = selectedMainUser == userId ||
                                (selectedUsers.length === 1 && !selectedMainUser);

                            userOptionsContainer.append(
                                '<div class="form-check">' +
                                    '<input type="radio" name="is_main" value="' + userId +
                                        '" id="is_main_' + userId +
                                        '" class="form-check-input" ' +
                                        (isChecked ? 'checked' : '') + '>' +
                                    '<label for="is_main_' + userId +
                                        '" class="form-check-label">' + userName + '</label>' +
                                '</div>'
                            );
                        });
                    }

                    userSelect
                        .off('change.ownerGroupCreate')
                        .on('change.ownerGroupCreate', renderMainOwnerOptions);
                    renderMainOwnerOptions();
                });
            });

            // Trigger the modal when an element with the 'popup-tab-owner-group-edit' class is clicked
            $(document).on('click', '.popup-tab-owner-group-edit', function(e) {
                e.preventDefault(); // Prevent the default action (e.g., following the link)

                // Get the URL from the link (you can dynamically get the URL as needed)
                // var url = $(this).attr('href'); // Assuming you're passing the URL in the 'href' attribute
                var url = $(this).attr('data-url'); // URL passed in the 'data-url' attribute
                var header = 'Edit Owner Group'; // You can set a custom header or get it dynamically
                // Access the data-property-id using JavaScript
                var propertyId = document.getElementById('hidden-property-id').getAttribute(
                    'data-property-id') ?? '';

                smallModal(url, header);
                // Ensure the modal content is loaded and then set the property_id in the hidden input field inside the modal form
                $('#smallModal').on('shown.bs.modal', function() {
                    // Set the property_id in the hidden input field inside the modal form
                    $("input[name='property_id']").val(propertyId);
                    initSelect2('.select2');

                    const userSelect2 = $('#user_id');
                    const userOptionsContainer2 = $('#user-options');

                    // Store the previously selected main user
                    let previouslySelectedMainUser = $('input[name="is_main"]:checked').val() ||
                        null;

                    // Listen for changes in the user dropdown
                    userSelect2.on('change', function() {
                        const selectedUsers = userSelect2.val() || [];
                        userOptionsContainer2.empty();

                        if (selectedUsers.length > 0) {
                            // Add default label
                            userOptionsContainer2.append(`
                            <label class="mb-2">Select Main User</label>
                        `);

                            // Add radio buttons for each selected user
                            selectedUsers.forEach(userId => {
                                const userName = userSelect2.find(
                                        `option[value="${userId}"]`)
                                    .text(); // Get the name from the option
                                const isChecked = previouslySelectedMainUser ===
                                    userId ? 'checked' :
                                    ''; // Preserve previously selected main user
                                userOptionsContainer2.append(`
                                <div class="form-check">
                                    <input type="radio" name="is_main" value="${userId}" id="is_main_${userId}" class="form-check-input" ${isChecked}>
                                    <label for="is_main_${userId}" class="form-check-label">${userName}</label>
                                </div>
                            `);
                            });

                            // Check if the previously selected main user is no longer in the selected users
                            if (!selectedUsers.includes(previouslySelectedMainUser)) {
                                // Reset previously selected main user
                                previouslySelectedMainUser = null;
                                // alert('Please reselect the main user as the previous one is no longer selected.');
                            }
                        }
                    });

                    // Update the stored value when a main user is selected
                    $(document).on('change', 'input[name="is_main"]', function() {
                        previouslySelectedMainUser = $(this).val();
                    });


                });
            });

            // Trigger the modal when an element with the 'popup-tab-offer-create' class is clicked
            $(document).on('click', '.popup-tab-offer-create', function(e) {
                e.preventDefault(); // Prevent the default action (e.g., following the link)

                // Get the URL from the link (you can dynamically get the URL as needed)
                var url = $(this).attr('href'); // Assuming you're passing the URL in the 'href' attribute
                var header = 'Add Offer'; // You can set a custom header or get it dynamically
                // Access the data-property-id using JavaScript
                var propertyId = document.getElementById('hidden-property-id').getAttribute(
                    'data-property-id') ?? '';

                smallModal(url, header);
                // Ensure the modal content is loaded and then set the property_id in the hidden input field inside the modal form
                $('#smallModal').on('shown.bs.modal', function() {
                    // Set the property_id in the hidden input field inside the modal form
                    $("input[name='property_id']").val(propertyId);
                });
            });

        });

        // document.querySelectorAll('.tab-link').forEach(tab => {
        //     tab.addEventListener('click', function(event) {
        //         event.preventDefault();

        //         // Remove active class from all tabs and tab content
        //         document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));
        //         document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));

        //         // Add active class to the clicked tab and corresponding content
        //         this.classList.add('active');
        //         document.getElementById(this.getAttribute('href').substring(1)).classList.add('active');
        //     });
        // });



        $(document).on('click', '.popup-tab-tenancy-create', function(e) {
            e.preventDefault();

            var baseUrl    = $(this).attr('data-url');
            var header     = 'Add Tenancy';

            // Try multiple sources for property ID in order of reliability
            var propertyId = $('.property-card.current').data('property-id')      // active card
                          || $('.pv_content_wrapper.current').data('property-id') // alternate selector
                          || $(this).attr('data-property-id')                     // stamped by loadTabContent
                          || new URLSearchParams(window.location.search).get('property_id') // URL param
                          || '';

            console.log('[AddTenancy] propertyId resolved:', propertyId);

            if (!propertyId) {
                alert('Could not determine the property. Please click on a property first.');
                return;
            }

            // Pass property_id as query param — controller pre-populates the hidden field server-side
            var url = baseUrl + '?property_id=' + propertyId;

            $("#extraLargeModal .modal-body").html("Loading...");
            $("#extraLargeModal .modal-title").html("Loading...");
            $("#extraLargeModal").modal("show");

            $.ajax({
                url: url,
                success: function(response) {
                    $("#extraLargeModal .modal-body").html(response);
                    $("#extraLargeModal .modal-title").html(header);
                    initSelect3('.select2');
                }
            });
        });
        $(document).on('click', '.popup-tab-tenancy-view', function(e) {
            e.preventDefault();
            // Get the URL from data-url attribute
            var url = $(this).attr('data-url');
            var header = 'Tenancy Details'; // Modal header

            // Open modal (assuming largeModal is your helper for loading content)
            extralargeModal(url, header);

            // When modal is fully shown
            $('#extraLargeModal').on('shown.bs.modal', function() {
                // If you want to enhance any fields inside view (e.g., select2 if used in view)
                initSelect3('.select2');
            });
        });
        $(document).on('click', '.popup-tab-tenancy-edit', function(e) {
            e.preventDefault(); // Prevent the default action (e.g., following the link)

            // Get the URL for the modal (you can dynamically fetch it as needed)
            var url = $(this).attr('data-url'); // URL passed in the 'data-url' attribute
            var header = 'Edit Tenancy'; // Custom header or dynamic header
            var propertyId = document.getElementById('hidden-property-id').getAttribute('data-property-id') ??
                ''; // Fetch the property_id

            // Open the modal (assuming smallModal is a function that handles modal rendering)
            extralargeModal(url, header);

            // Ensure modal content is loaded and set the property_id in the hidden field inside the modal form
            $('#extraLargeModal').on('shown.bs.modal', function() {
                // Set the property_id in the hidden input field inside the modal form
                $("input[name='property_id']").val(propertyId);
                initSelect3('.select2');
            });
        });

        $('#editTenancyForm').on('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            // Gather the form data
            var formData = new FormData(this); // This includes the form fields and file inputs

            // Send the AJAX request
            $.ajax({
                url: $(this).attr('action'), // Use the form's action attribute
                method: 'POST', // Form method (use 'PUT' or 'PATCH' if it's an update)
                data: formData, // Form data
                processData: false, // Prevent jQuery from automatically transforming the data
                contentType: false, // Let the browser set the content type
                success: function(response) {
                    // Handle success response
                    if (response.success) {
                        // Display success message
                        flashMessage('Tenancy updated successfully!', 'success');
                        // Optionally redirect or update the UI (e.g., close modal, refresh data)
                        location.reload(); // Reload the page (if necessary)
                    } else {
                        // Handle errors if any (validation errors, etc.)
                        flashMessage(response.message || 'An error occurred, please try again.',
                            'error');
                    }
                },
                error: function(xhr, status, error) {
                    // Handle AJAX error (e.g., network issue, server issue)
                    flashMessage('An error occurred while submitting the form. Please try again.',
                        'error');
                }
            });
        });


        // Function to show a flash message (You can customize this to use your preferred alert system)
        function flashMessage(message, type) {
            var flashMessage = $('<div>', {
                class: 'flash-message ' + type,
                text: message
            }).appendTo('body').fadeIn().delay(3000).fadeOut();
        }

        document.addEventListener('show.bs.modal', function(event) {
            const zIndex = 1040 + (10 * document.querySelectorAll('.modal.show').length);
            const modal = event.target;

            modal.style.zIndex = zIndex;
            setTimeout(function() {
                const backdrop = document.querySelectorAll('.modal-backdrop:not(.modal-stack)');
                backdrop.forEach(function(el) {
                    el.style.zIndex = zIndex - 1;
                    el.classList.add('modal-stack');
                });
            }, 0);
        });



        $(document).ready(function() {

            // Function to get URL parameters
            function getUrlParameter(name) {
                var urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(name);
            }

            // Check if URL parameters are present (property_id and tabname)
            function hasUrlParams() {
                var urlParams = new URLSearchParams(window.location.search);
                return urlParams.has('property_id') && urlParams.has('tabname');
            }

            // Function to update the title dynamically
            function updateTitle(tabName, propertyId = null) {
                // Capitalize the first letter of the tab name for display
                var formattedTitle = tabName.charAt(0).toUpperCase() + tabName.slice(1);

                // Update the content of the title div
                $('.pv_main_title').text(formattedTitle + ' Detail');

                // Show or hide the button based on the tabName
                if (tabName === 'owners') {
                    // $('.tab-owners-btn').removeClass('d-none'); // Show the button for 'owner' tab
                    $('.tab-owners-group-btn').removeClass('d-none'); // Show the button for 'owner' tab
                } else {
                    // $('.tab-owners-btn').addClass('d-none'); // Hide the button for other tabs
                    $('.tab-owners-group-btn').addClass('d-none'); // Hide the button for other tabs
                }
                if (tabName === 'offers') {
                    $('.tab-offers-btn').removeClass('d-none'); // Show the button for 'owner' tab
                } else {
                    $('.tab-offers-btn').addClass('d-none'); // Hide the button for other tabs
                }
                if (tabName === 'tenancy') {
                    $('.tab-tenancy-group-btn').removeClass('d-none'); // Show the button for 'owner' tab
                } else {
                    $('.tab-tenancy-group-btn').addClass('d-none'); // Hide the button for other tabs
                }
                // Update the "Edit Property" button dynamically if property ID exists
                // if (propertyId) {
                //     var editButtonLink = '{{ route('admin.properties.edit', ['id' => ':id']) }}'.replace(':id', propertyId);
                //     $('.pvdh_btns_wrapper .edit-property-btn').removeClass('d-none').attr('href', editButtonLink);
                //         // console.log(editButtonLink);
                // } else {
                //     $('.pvdh_btns_wrapper .edit-property-btn').addClass('d-none'); // Hide the button if no property ID
                // }

            }

            function showImportantNoteForCard(card) {
                if (!card || !card.length) {
                    return;
                }

                var importantNote = (card.attr('data-important-note') || '').trim();

                if (importantNote) {
                    $('#importantNoteVisitContent').text(importantNote);
                    $('#importantNoteVisitModal').modal('show');
                }
            }

            // Handle Tab Clicks
            // Event listener for property cards (left side)
            $(document).on('click', '.property-card', function() {
                var propertyId = $(this).data('property-id');
                $('.property-card').removeClass('current');
                $(this).addClass('current');
                var tabName = $('.tab-link.active').data('tab-name');
                showImportantNoteForCard($(this));
                loadTabContent(propertyId, tabName);
            });

            // Event listener for tabs (right side)
            $(document).on('click', '.tab-link', function(e) {
                e.preventDefault();
                var tabName = $(this).data('tab-name');
                var propertyId = $('.property-card.current').data('property-id');
                $('.tab-link').removeClass('active');
                $(this).addClass('active');
                loadTabContent(propertyId, tabName); // Load content dynamically
            });

            // Check if URL parameters are present (property_id and tabname)
            function hasUrlParams() {
                var urlParams = new URLSearchParams(window.location.search);
                return urlParams.has('property_id') && urlParams.has('tabname');
            }
            // Call the appropriate function based on URL parameters or default
            if (hasUrlParams()) {
                activateTabFromUrl(); // Handle tabs based on URL parameters
            } else {
                simulateTabClickAndPropertyCard(); // Default behavior
            }


            // Scroll the property list so the given card is visible
            function scrollToCard(card) {
                if (!card || !card.length) return;
                var container = $('.pv_card_wrapper');
                if (!container.length) return;
                var cardTop    = card.position().top;
                var cardHeight = card.outerHeight();
                var containerHeight = container.height();
                container.animate({
                    scrollTop: container.scrollTop() + cardTop - (containerHeight / 2) + (cardHeight / 2)
                }, 300);
            }

            // Function to activate tab based on URL parameter
            function activateTabFromUrl() {
                var tabName = getUrlParameter('tabname'); // Get tabname from URL
                var propertyId = getUrlParameter('property_id'); // Get property_id from URL

                if (tabName && propertyId) {
                    // Find the tab and property card with the matching data attributes
                    var selectedTab = $('.tab-link[data-tab-name="' + tabName + '"]');
                    var selectedPropertyCard = $('.property-card[data-property-id="' + propertyId + '"]');

                    // Mark the selected tab as active
                    $('.tab-link').removeClass('active');
                    selectedTab.addClass('active');

                    // If the card is already in the DOM, highlight it and load content
                    if (selectedPropertyCard.length) {
                        $('.property-card').removeClass('current');
                        selectedPropertyCard.addClass('current');
                        scrollToCard(selectedPropertyCard);
                        showImportantNoteForCard(selectedPropertyCard);
                        loadTabContent(propertyId, tabName);
                    } else {
                        // Card is on a different page — reload the list to the correct page first
                        $.ajax({
                            url: '{{ route('admin.properties.index') }}',
                            type: 'GET',
                            data: { list_only: 1, highlight_id: propertyId },
                            success: function(response) {
                                $('#propertyListContainer').html(response.html);
                                // Now highlight and scroll to the card
                                $('.property-card').removeClass('current');
                                var card = $('.property-card[data-property-id="' + propertyId + '"]');
                                card.addClass('current');
                                scrollToCard(card);
                                showImportantNoteForCard(card);
                                loadTabContent(propertyId, tabName);
                            },
                            error: function() {
                                // Property doesn't exist — fall back to first card
                                var firstCard = $('.property-card').first();
                                var firstId   = firstCard.data('property-id');
                                firstCard.addClass('current');
                                showImportantNoteForCard(firstCard);
                                loadTabContent(firstId, tabName);
                            }
                        });
                    }
                }
            }

            // Simulate the first tab and first property card selection on page load
            function simulateTabClickAndPropertyCard() {
                var firstPropertyCard = $('.property-card').first(); // Get the first property card
                var firstTab = $('.tab-link').first(); // Get the first tab

                // Get the propertyId and tabName from the first property card and tab
                var propertyId = firstPropertyCard.data('property-id');
                var tabName = firstTab.data('tab-name');
                console.log(propertyId);
                console.log(tabName);

                // Trigger the AJAX load
                if (propertyId && tabName) {
                    loadTabContent(propertyId, tabName);
                    firstPropertyCard.addClass('current'); // Add 'current' class to the first property card
                    firstTab.addClass('active'); // Add 'active' class to the first tab
                    showImportantNoteForCard(firstPropertyCard);
                }
            }

            // Call the simulateTabClickAndPropertyCard function on document ready only if URL parameters are NOT present
            // if (!hasUrlParams()) {
            //     simulateTabClickAndPropertyCard();
            // }

            // Function to load tab content dynamically via AJAX
            function loadTabContent(propertyId, tabName) {
                // Correctly format the URL with query parameters instead of placeholders
                var url = '{{ route('admin.properties.index') }}' + '?property_id=' + propertyId + '&tabname=' +
                    tabName;

                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('.pv_content_detail').html(response.content);
                        updateTitle(tabName, propertyId);
                        if (tabName === 'documents') {
                            $('.pv_content_detail .documents-component').trigger('documents:refresh');
                        }
                        if (tabName === 'notes') {
                            $('.pv_content_detail .notes-component').trigger('notes:refresh');
                        }
                        // Stamp the current property ID onto action buttons so click handlers can read it reliably
                        $('.tab-tenancy-group-btn, .tab-owners-group-btn, .tab-offers-btn').attr('data-property-id', propertyId);
                        window.history.pushState(null, null, url);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading tab content:', error);

                        let message = "Something went wrong.";

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        // AIZ Notify (or switch to toastr or SweetAlert if needed)
                        AIZ.plugins.notify('danger', message);

                        // Optional: fallback content or redirect
                        $('.pv_content_detail').html('<div class="alert alert-danger">' + message + '</div>');
                    }
                });
            }

            $(document).on('change', '#pets_allow', function() {
                // Set the value to 1 if checked, otherwise set to 0
                this.value = this.checked ? 1 : 0;
            });

            // Trigger change once on page load to set initial value
            // $(function() {
            //     $('#pets_allow').trigger('change');
            // });

        });
    </script>

    <script>
        // Function to open the compliance modal and fetch the form
        function openComplianceModal(complianceTypeId, complianceRecordId = null) {
            var propertyId = document.getElementById('hidden-property-id').getAttribute('data-property-id') ??
                ''; // Fetch the property_id

            let url = complianceRecordId ?
                '{{ route('admin.compliance.type.form', [':complianceTypeId', ':complianceRecordId']) }}'
                .replace(':complianceTypeId', complianceTypeId)
                .replace(':complianceRecordId', complianceRecordId) :
                '{{ route('admin.compliance.type.form', ':complianceTypeId') }}'.replace(':complianceTypeId',
                    complianceTypeId);

            $.ajax({
                url: url,
                // url: '{{ route('admin.compliance.type.form', ':complianceTypeId') }}'.replace(':complianceTypeId', complianceTypeId),
                type: 'GET',
                success: function(response) {

                    // Load the dynamic form content into the modal body
                    $('#complianceModalLabel').html(response.heading);
                    $('#complianceModalBody').html(response.content);

                    // Find the form inside the modal and get its ID
                    var formId = $('#complianceModalBody form').attr('id');
                    // Set the property_id in the hidden input field inside the modal form
                    $("input[name='property_id']").val(propertyId);
                    $("input[name='compliance_type_id']").val(complianceTypeId);

                    AIZ.uploader.previewGenerate();

                    // Set the form ID dynamically to the submit button
                    $('#submitComplianceForm').attr('form', formId);

                    // Show the modal
                    $('#complianceModal').modal('show');
                },
                error: function(error) {
                    console.log(error);
                }
            });
        }
        $(document).on('click', '#submitComplianceForm', function(e) {
            e.preventDefault(); // Prevent the default form submission behavior
            let formId = $(this).attr('form'); // Get the form ID dynamically
            let formData = new FormData(document.getElementById(formId));

            $.ajax({
                url: formData.get('record_id') ?
                    '{{ route('admin.compliance.update') }}' : '{{ route('admin.compliance.store') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        AIZ.plugins.notify('success', response.message);
                        $('#complianceModal').modal('hide'); // Close the modal
                        location.reload(); // Optionally reload the page to update the compliance list
                    } else {
                        AIZ.plugins.notify('danger', 'Failed to save compliance record.');
                    }
                },
                error: function(error) {
                    console.error(error);
                    let errorMessage = error.responseJSON?.message ||
                        'An error occurred while saving the compliance record.';
                    AIZ.plugins.notify('danger', errorMessage);
                }
            });
        });

        let complianceRecordIdToDelete = null;

        // Confirm Delete Record
        function confirmDelete(complianceRecordId) {
            complianceRecordIdToDelete = complianceRecordId;
            $('#deleteConfirmationModal').modal('show');
        }

        // Execute Delete Action
        $(document).on('click', '#confirmDeleteBtn', function() {
            if (complianceRecordIdToDelete) {
                $.ajax({
                    url: `{{ route('admin.compliance.delete', ':complianceRecordId') }}`.replace(
                        ':complianceRecordId', complianceRecordIdToDelete),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}' // Include the CSRF token in the request
                    },
                    success: function(response) {
                        if (response.success) {
                            AIZ.plugins.notify('success', response.message);
                            $('#deleteConfirmationModal').modal('hide');
                            location.reload(); // Reload page to reflect the changes
                        } else {
                            AIZ.plugins.notify('danger', 'Failed to delete compliance record.');
                        }
                    },
                    error: function(error) {
                        console.error(error);
                        let errorMessage = error.responseJSON?.message ||
                            'An error occurred while deleting the compliance record.';
                        AIZ.plugins.notify('danger', errorMessage);
                    }
                });
            }
        });
    </script>
    <script>
        // Filter submit
        $(document).on('submit', '#appointments-filter-form', function(e) {
            e.preventDefault();
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();
            if (startDate && endDate && endDate < startDate) {
                alert("The end date can't be less than the start date.");
                return;
            }
            fetchAppointments($(this).serialize());
        });

        // Pagination click
        $(document).on('click', '#appointments-results .pagination a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            let params = url.split('?')[1];
            fetchAppointments(params);
        });

        // Common fetch function
        function fetchAppointments(queryString) {
            let propertyId = '{{ $propertyId ?? request('property_id') }}';
            let finalQuery = `property_id=${propertyId}&tabname=appointments&ajax_only=1&${queryString}`;

            $.ajax({
                url: '{{ route('admin.properties.index') }}?' + finalQuery,
                method: 'GET',
                beforeSend: function() {
                    $('#appointments-results').html('<p>Loading...</p>');
                },
                success: function(res) {
                    $('#appointments-results').html(res.content);
                },
                error: function() {
                    $('#appointments-results').html('<p class="text-danger">Error loading appointments.</p>');
                }
            });
        }

        $(document).on('click', '#reset-appointments-filter', function() {
            $('#appointments-filter-form')[0].reset(); // Clear form
            fetchAppointments(''); // Reload unfiltered list
        });

        // Pagination click
        $(document).on('click', '#appointments-results .pagination a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            let params = url.split('?')[1];
            fetchAppointments(params);
        });

        let statusChangeData = {};

        $(document).on('click', '.change-status-btn', function(e) {
            e.preventDefault();
            statusChangeData.id = $(this).data('id');
            statusChangeData.status = $(this).data('status');

            $('#new-status-text').text(statusChangeData.status);
            $('#confirmStatusChangeModal').modal('show');
        });

        $(document).on('click', '#confirmStatusChangeBtn', function(e) {
            console.log(1);
            e.preventDefault();
            $.ajax({
                url: `{{ route('backend.events.changeStatus', ':id') }}`.replace(
                        ':id', statusChangeData.id),
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    status: statusChangeData.status
                },
                success: function(response) {
                    $('#confirmStatusChangeModal').modal('hide');
                    fetchAppointments($('#appointments-filter-form').serialize()); // refresh data
                },
                error: function() {
                    alert('Failed to update status.');
                }
            });
        });

        let deleteType = '';
        let deleteId = '';
        let deleteStart = '';

        $(document).on('click', '.delete-option', function (e) {
            e.preventDefault();

            const row = $(this).closest('tr');
            deleteLabel = $(this).data('label');
            // deleteType = $(this).data('type');
            deleteId = $(this).data('id');
            deleteStart = $(this).data('start');

            // $('#deleteTypeLabel').text(deleteType.toUpperCase());
            $('#deleteTypeLabel').text(deleteLabel.toUpperCase());
            $('#deleteEventId').val(deleteId);
            $('#deleteOccurrenceStart').val(deleteStart);
            $('#deleteChoiceAction').val(deleteType);

            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmEventModal'));
            modal.show();
        });

        $('#deleteForm').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                url: '/events/delete-instance/' + deleteId,
                method: 'POST',
                data: $(this).serialize(),
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res.success) {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert('Error: ' + res.message);
                    }
                },
                error: function (xhr) {
                    alert('Something went wrong');
                }
            });
        });

    // Property Search and Pagination
    let propertySearchTimeout;
    $('#propertySearch').on('input', function() {
        clearTimeout(propertySearchTimeout);
        const searchValue = $(this).val();
        
        propertySearchTimeout = setTimeout(function() {
            loadPropertyList(searchValue);
        }, 500); // Debounce 500ms
    });

    // Handle pagination clicks
    $(document).on('click', '#propertyListContainer .pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const searchValue = $('#propertySearch').val();
        
        $.ajax({
            url: url,
            type: 'GET',
            data: { 
                list_only: 1,
                search: searchValue
            },
            success: function(response) {
                $('#propertyListContainer').html(response.html);
            },
            error: function() {
                console.error('Failed to load page');
            }
        });
    });

    function loadPropertyList(search = '') {
        $.ajax({
            url: '{{ route('admin.properties.index') }}',
            type: 'GET',
            data: { 
                list_only: 1,
                search: search
            },
            success: function(response) {
                $('#propertyListContainer').html(response.html);
            },
            error: function() {
                console.error('Failed to load properties');
            }
        });
    }

    function initPropertyAppointmentSelect($select, name, multiple) {
        $select.attr('name', name).prop('multiple', multiple);

        // The appointments tab is loaded over AJAX. Do not prevent the modal
        // from opening if Select2 has not finished loading on this page.
        if (!$.fn.select2) return;

        if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
        $select.select2({
            dropdownParent: $('#eventModal'),
            width: '100%',
            placeholder: multiple ? 'Select contacts' : 'Select a value',
            ajax: {
                url: $select.data('url'), dataType: 'json', delay: 250,
                data: params => ({q: params.term}),
                processResults: data => ({results: data.results})
            }
        });
    }

    function showPropertyAppointmentModal(selector) {
        const modal = document.querySelector(selector);
        if (window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        } else {
            $(modal).modal('show');
        }
    }

    function hidePropertyAppointmentModal(selector) {
        const modal = document.querySelector(selector);
        if (window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(modal).hide();
        } else {
            $(modal).modal('hide');
        }
    }

    // The recurrence builder opens on top of the appointment form. Bootstrap
    // gives every modal the same base z-index, so raise this child modal and
    // its backdrop explicitly rather than leaving it hidden behind the form.
    $(document).on('show.bs.modal', '#rruleModal', function () {
        $(this).css('z-index', 1065);
        setTimeout(function () {
            $('.modal-backdrop').last().css('z-index', 1060).addClass('appointment-rrule-backdrop');
        }, 0);
    });

    $(document).on('hidden.bs.modal', '#rruleModal', function () {
        $(this).css('z-index', '');
        $('.appointment-rrule-backdrop').last().remove();
    });

    function renumberPropertyAppointmentReminders() {
        $('#reminderList .reminder-row').each(function (index) {
            $(this).find('input[type="number"]').attr('name', `reminders[${index}][minutes_before]`);
            $(this).find('input[type="hidden"]').attr('name', `reminders[${index}][channel]`).val('email');
        });
    }

    // The calendar page previously owned these handlers, but the appointment
    // form is rendered in the property page as well. Delegate them here so
    // they work after an Appointments tab is loaded through AJAX.
    $(document).on('click', '#eventModal #addReminderBtn', function (event) {
        event.preventDefault();
        const template = document.getElementById('reminderTpl');
        if (!template) return;

        const $row = $(template.content.cloneNode(true)).find('.reminder-row');
        $row.find('input[type="number"]').val('');
        $('#reminderList').append($row);
        renumberPropertyAppointmentReminders();
    });

    $(document).on('click', '#eventModal .removeReminderBtn', function (event) {
        event.preventDefault();
        $(this).closest('.reminder-row').remove();
        renumberPropertyAppointmentReminders();
    });

    function updatePropertyAppointmentRecurrenceFrequency() {
        const frequency = $('#freqSelect').val();
        const units = {
            DAILY: 'day(s)',
            WEEKLY: 'week(s)',
            MONTHLY: 'month(s)',
            YEARLY: 'year(s)',
        };

        $('#intervalLabel').text(units[frequency] || 'day(s)');
        $('#byDayContainer').toggleClass('d-none', frequency !== 'WEEKLY');
        $('#byOrdinalContainer').toggleClass('d-none', frequency !== 'MONTHLY');
    }

    function updatePropertyAppointmentRecurrenceEnd() {
        const endType = $('#endTypeSelect').val();
        $('#endAfterContainer').toggleClass('d-none', endType !== 'AFTER');
        $('#endByDateContainer').toggleClass('d-none', endType !== 'BYDATE');
    }

    function renderPropertyAppointmentRecurrenceSummary(rule) {
        $('#rruleSummary').text(rule ? rule.toText() : 'No recurrence');
    }

    function resetPropertyAppointmentRecurrenceBuilder() {
        $('#freqSelect').val('DAILY');
        $('#intervalInput').val(1);
        $('#endTypeSelect').val('NEVER');
        $('#endAfterCount').val(1);
        $('#endByDateInput').val('');
        $('#byDayContainer input[type="checkbox"]').prop('checked', false);
        $('#bySetPos').val('1');
        $('#byDayOrdinal').val('MO');
        $('#exdateList').empty();
        updatePropertyAppointmentRecurrenceFrequency();
        updatePropertyAppointmentRecurrenceEnd();
    }

    function addPropertyAppointmentExdate(value = '') {
        $('#exdateList').append(
            $('<div>', { class: 'input-group mb-2' }).append(
                $('<input>', { type: 'date', class: 'form-control exdateInput', value }),
                $('<button>', { type: 'button', class: 'btn btn-outline-danger removeExdateBtn', text: '×' })
            )
        );
    }

    $(document).on('click', '#eventModal #editRRuleBtn', function (event) {
        event.preventDefault();
        const RRule = window.RRule;
        if (!RRule) {
            AIZ.plugins.notify('danger', 'The recurrence builder is still loading. Please try again in a moment.');
            return;
        }

        resetPropertyAppointmentRecurrenceBuilder();
        const rruleString = $('#rruleInput').val().trim();
        const exdates = $('#exdatesInput').val().trim();

        if (rruleString) {
            try {
                const rule = RRule.fromString(rruleString);
                const frequencies = {
                    [RRule.DAILY]: 'DAILY',
                    [RRule.WEEKLY]: 'WEEKLY',
                    [RRule.MONTHLY]: 'MONTHLY',
                    [RRule.YEARLY]: 'YEARLY',
                };
                $('#freqSelect').val(frequencies[rule.options.freq] || 'DAILY');
                $('#intervalInput').val(rule.options.interval || 1);

                if (rule.options.count) {
                    $('#endTypeSelect').val('AFTER');
                    $('#endAfterCount').val(rule.options.count);
                } else if (rule.options.until) {
                    $('#endTypeSelect').val('BYDATE');
                    $('#endByDateInput').val(rule.options.until.toISOString().slice(0, 10));
                }

                if (rule.options.byweekday) {
                    const days = Array.isArray(rule.options.byweekday) ? rule.options.byweekday : [rule.options.byweekday];
                    const dayIds = ['chkMO', 'chkTU', 'chkWE', 'chkTH', 'chkFR', 'chkSA', 'chkSU'];
                    days.forEach(day => {
                        const weekday = typeof day === 'number' ? day : day.weekday;
                        if (weekday !== undefined) $(`#${dayIds[weekday]}`).prop('checked', true);
                    });
                }
                updatePropertyAppointmentRecurrenceFrequency();
                updatePropertyAppointmentRecurrenceEnd();
            } catch (error) {
                console.warn('Unable to load recurrence rule.', error);
            }
        }

        try {
            JSON.parse(exdates || '[]').forEach(addPropertyAppointmentExdate);
        } catch (error) {
            console.warn('Unable to load recurrence exclusions.', error);
        }

        showPropertyAppointmentModal('#rruleModal');
    });

    $(document).on('change', '#rruleModal #freqSelect', updatePropertyAppointmentRecurrenceFrequency);
    $(document).on('change', '#rruleModal #endTypeSelect', updatePropertyAppointmentRecurrenceEnd);

    $(document).on('click', '#rruleModal #addExdateBtn', function (event) {
        event.preventDefault();
        addPropertyAppointmentExdate();
    });

    $(document).on('click', '#rruleModal .removeExdateBtn', function () {
        $(this).closest('.input-group').remove();
    });

    $(document).on('click', '#rruleModal #saveRRuleBtn', function (event) {
        event.preventDefault();
        const RRule = window.RRule;
        if (!RRule) {
            AIZ.plugins.notify('danger', 'The recurrence builder is unavailable. Please reload the page and try again.');
            return;
        }

        const frequency = $('#freqSelect').val();
        const options = {
            freq: RRule[frequency],
            interval: Math.max(1, parseInt($('#intervalInput').val(), 10) || 1),
        };

        if (frequency === 'WEEKLY') {
            const days = $('#byDayContainer input:checked').map(function () {
                return RRule[$(this).val()];
            }).get();
            if (days.length) options.byweekday = days;
        }

        if (frequency === 'MONTHLY') {
            options.bysetpos = parseInt($('#bySetPos').val(), 10);
            options.byweekday = [RRule[$('#byDayOrdinal').val()]];
        }

        if ($('#endTypeSelect').val() === 'AFTER') {
            options.count = Math.max(1, parseInt($('#endAfterCount').val(), 10) || 1);
        }

        if ($('#endTypeSelect').val() === 'BYDATE' && $('#endByDateInput').val()) {
            options.until = new Date(`${$('#endByDateInput').val()}T23:59:59`);
        }

        try {
            const rule = new RRule(options);
            const exdates = $('#exdateList .exdateInput').map(function () {
                return this.value;
            }).get().filter(Boolean);

            $('#rruleInput').val(rule.toString());
            $('#exdatesInput').val(JSON.stringify(exdates));
            renderPropertyAppointmentRecurrenceSummary(rule);
            hidePropertyAppointmentModal('#rruleModal');
        } catch (error) {
            AIZ.plugins.notify('danger', 'Unable to save this recurrence rule.');
            console.error(error);
        }
    });

    $(document).on('click', '#btn-add-appointment', function () {
        // Keep the property ID on the dynamically-loaded trigger itself, then
        // fall back to the tab fields used by older property-tab responses.
        const propertyId = $(this).data('property-id')
            || $('#appointments-filter-form input[name="property_id"]').val()
            || $('#hidden-property-id').data('property-id');
        const propertyLabel = $(this).data('property-label') || `Property #${propertyId}`;
        if (!propertyId) {
            AIZ.plugins.notify('danger', 'Select a property before adding an appointment.');
            return;
        }
        const form = $('#eventForm')[0];
        if (!form) return;
        form.reset();
        $('#eventForm input[name="form_action"]').val('create');
        $('#eventForm input[name="event_id"], #eventForm input[name="instance_id"], #eventForm input[name="master_id"], #eventForm input[name="choice_action"], #eventForm input[name="original_start"], #eventForm input[name="original_end"]').val('');
        $('#eventForm [data-error-for]').empty();
        $('#sub_type_id').html('<option value="">— Select Sub-Type —</option>');
        $('#reminderList').empty();
        $('#rruleInput, #exdatesInput').val('');
        $('#rruleSummary').text('No recurrence');

        // Select2 retains dynamically created options after a form reset. Clear
        // them before each opening so this appointment starts with exactly one
        // linked property and no previous invitees or repairs.
        const $propertySelect = $('#property-select');
        const $inviteSelect = $('#invite-select');
        const $repairSelect = $('#repair-select');
        [$propertySelect, $inviteSelect, $repairSelect].forEach(function ($select) {
            if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
            $select.empty();
        });

        initPropertyAppointmentSelect($propertySelect, 'property_ids[]', true);
        initPropertyAppointmentSelect($inviteSelect, 'invite_ids[]', true);
        initPropertyAppointmentSelect($repairSelect, 'repair_ids[]', true);
        $propertySelect.append(new Option(propertyLabel, propertyId, true, true)).trigger('change');
        const eventModal = document.getElementById('eventModal');
        if (window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(eventModal).show();
        } else {
            $(eventModal).modal('show');
        }
    });

    $(document).on('change', '#eventModal #type_id', function () {
        const $subType = $('#eventModal #sub_type_id').html('<option value="">— Select Sub-Type —</option>');
        if (!this.value) return;
        $.getJSON('/admin/api/event-sub-types/' + this.value, function (data) {
            $.each(data, function (id, name) { $subType.append(new Option(name, id)); });
        });
    });

    $(document).on('submit', '#eventForm', function (event) {
        if ($('#eventForm input[name="form_action"]').val() !== 'create') return;
        event.preventDefault();
        $.ajax({
            url: '{{ route('backend.events.store') }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function () {
                const eventModal = document.getElementById('eventModal');
                if (window.bootstrap?.Modal) {
                    bootstrap.Modal.getOrCreateInstance(eventModal).hide();
                } else {
                    $(eventModal).modal('hide');
                }
                fetchAppointments($('#appointments-filter-form').serialize());
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Unable to save appointment.';
                alert(message);
            }
        });
    });

    </script>
@endsection
