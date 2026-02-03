@extends('backend.layout.app')

@section('content')
    <div class="row g-0 view_users">
        <div class="col-lg-5 col-12">
            <div class="property_list_wrapper pt-lg-4 pt-2 ">
                <div class="pv_wrapper">
                    <div class="pv_header">
                        <div class="row">
                            <div class="col-3">
                                <div class="pv_title">Contacts</div>
                            </div>
                            <div class="col-9">
                                <x-backend.forms.search
                                    class=''
                                    placeholder='Search'
                                    value=''
                                    onClick='onClick()'
                                />
                            </div>
                            @can('Create Contacts')
                            <div class="pv_btn">
                                <a href="{{ route('admin.users.create') }}" class="btn mt-2 btn-sm btn-outline-danger">
                                    Add Contact
                                </a>
                            </div>
                            @endcan
                        </div>

                    </div>
                    
                    
                    <div class="pv_card_wrapper">
                        {{-- Dev Note: if select user from list add class 'current' to user card --}}
                        @foreach ($users as $user)
                            @php
                                $nameParts = array_filter([
                                    $user['first_name'] ?? '',
                                    $user['middle_name'] ?? '',
                                    $user['last_name'] ?? '',
                                ]);

                                $fullName = !empty($user['name'])
                                    ? $user['name']
                                    : implode(' ', $nameParts);
                            @endphp

                            <x-backend.user-card
                                class="user-card"
                                user-name="{{ $fullName }}"
                                email="{{ $user['email'] }}"
                                phone="{{ $user['phone'] }}"
                                card-style=""
                                user-id="{{ $user['id'] }}" />
                        @endforeach

                    </div>                 
                    
                </div>
            </div>
        </div>
        <div class="col-lg-7 col-12 property_detail_wrapper hide_this pt-lg-4 pt-0">
            <div class="pv_detail_wrapper">

                <x-backend.users-tabs :tabs="$tabs" class="user_tabs" />

                <div class="pv_detail_content">
                    <div class="pv_detail_header">
                        <div class="pv_main_title">{{ ucfirst($tabName) }} Detail</div>
                        <div class="pvdh_btns_wrapper d-flex gap-3">
                            
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
                    
                </div>
            </div>
        </div>
    </div>
    <style>
        .hidden {
            display: none !important;
        }
        .modal-content {
            height: auto;
            margin: auto;
        }
        .modal-backdrop.modal-stack {
            opacity: 0.3 !important;
        }
    </style>

</div>

    <!-- Include the Modal Component -->
    @include('backend.components.modal')
@endsection
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('/asset/backend/js/common-notes.js') }}"></script>
<script src="{{ asset('/asset/backend/js/common-documents.js') }}"></script>
@endpush
@section('page.scripts')
@if (isset($userId) && isset($user) && $userId != $user->id)
{{-- @php
var_dump($userId);
@endphp --}}
<script>
    const url = new URL(window.location.href);
    url.searchParams.set('user_id', '{{ $userId }}');
    history.replaceState(null, '', url.toString());
</script>
@endif

<script>
    var responseHandler = function(response) {
        location.reload();
    }
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

        // Function to check if the device is mobile
        function is_mobile() {
            return (
                /Mobi|Android/i.test(navigator.userAgent) || $(window).width() < 768
            );
        }

        if (is_mobile()) {
            $(document).on('click', '.user-card', function() {
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

        // Function to get URL parameters
        function getUrlParameter(name) {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

        // Check if URL parameters are present (user_id and tabname)
        function hasUrlParams() {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.has('user_id') && urlParams.has('tabname');
        }

        // Function to update the title dynamically
        function updateTitle(tabName, userId = null) {
            // Capitalize the first letter of the tab name for display
            var formattedTitle = tabName.charAt(0).toUpperCase() + tabName.slice(1);

            // Update the content of the title div
            $('.pv_main_title').text(formattedTitle + ' Detail');
        }

        // Handle Tab Clicks
        // Event listener for user cards (left side)
        $(document).on('click', '.user-card', function() {
            var userId = $(this).data('user-id');
            $('.user-card').removeClass('current');
            $(this).addClass('current');
            var tabName = $('.tab-link.active').data('tab-name');
            loadTabContent(userId, tabName);
        });

        // Event listener for tabs (right side)
        $(document).on('click', '.tab-link', function(e) {
            e.preventDefault();
            var tabName = $(this).data('tab-name');
            var userId = $('.user-card.current').data('user-id');
            $('.tab-link').removeClass('active');
            $(this).addClass('active');
            loadTabContent(userId, tabName); // Load content dynamically
        });

        // Check if URL parameters are present (user_id and tabname)
        function hasUrlParams() {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.has('user_id') && urlParams.has('tabname');
        }
        // Call the appropriate function based on URL parameters or default
        if (hasUrlParams()) {
            activateTabFromUrl(); // Handle tabs based on URL parameters
        } else {
            simulateTabClickAndUserCard(); // Default behavior
        }


        // Function to activate tab based on URL parameter
        function activateTabFromUrl() {
            var tabName = getUrlParameter('tabname'); // Get tabname from URL
            var userId = getUrlParameter('user_id'); // Get user_id from URL

            if (tabName && userId) {
                // Convert underscores back to spaces
                // var displayTabName = tabName.replace(/_/g, ' ');

                // Find the tab and user card with the matching data attributes
                var selectedTab = $('.tab-link[data-tab-name="' + tabName + '"]');
                var selectedUserCard = $('.user-card[data-user-id="' + userId + '"]');

                // Mark the selected tab and user card as active/current
                $('.tab-link').removeClass('active');
                $('.user-card').removeClass('current');
                selectedTab.addClass('active');
                selectedUserCard.addClass('current');

                // Load the content dynamically
                loadTabContent(userId, tabName);
            }
        }

        // Simulate the first tab and first user card selection on page load
        function simulateTabClickAndUserCard() {
            var firstUserCard = $('.user-card').first(); // Get the first user card
            var firstTab = $('.tab-link').first(); // Get the first tab

            // Get the userId and tabName from the first user card and tab
            var userId = firstUserCard.data('user-id');
            var tabName = firstTab.data('tab-name');
            console.log(userId);
            console.log(tabName);

            // Trigger the AJAX load
            if (userId && tabName) {
                loadTabContent(userId, tabName);
                firstUserCard.addClass('current'); // Add 'current' class to the first user card
                firstTab.addClass('active'); // Add 'active' class to the first tab
            }
        }

        // Call the simulateTabClickAndUserCard function on document ready only if URL parameters are NOT present
        // if (!hasUrlParams()) {
        //     simulateTabClickAndUserCard();
        // }

        // Function to load tab content dynamically via AJAX
        function loadTabContent(userId, tabName) {
            // Replace spaces with underscores for the URL
            // var formattedTabName = tabName.replace(/\s+/g, '_');
            // Correctly format the URL with query parameters instead of placeholders
            var url = '{{ route('admin.users.index') }}' + '?user_id=' + userId + '&tabname=' + tabName;

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('.pv_content_detail').html(response.content);
                    updateTitle(response.tabName, userId);
                    // Update URL (optional, for browser navigation)
                    window.history.pushState(null, null, url);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading tab content:', error);
                }
            });
        }

    });
</script>
<script>
$(function() {
    // Delegate the change event to document
    $(document).on('change', '#right_to_rent_check', function() {
      if (this.checked) {
        $('#rent-check-person-details').css('display', 'flex');
        $('#rent-check-message').show();
      } else {
        $('#rent-check-person-details, #rent-check-message').hide();
      }
    });

    // Trigger the handler once on load to initialize visibility
    $(document).find('#right_to_rent_check').trigger('change');
});

 // Open modal and load form via AJAX
$(document).on('click', '.editForm, .addForm', function() {
    let formType = $(this).data("form");
    let userId = $(this).data("id");
    let noteId     = $(this).data('note-id') || '';
    let bankId     = $(this).data('bank-detail-id') || '';
    let formTitles = {
        "user_detail": "Edit User Details",
        // "property_info": "Edit Property Information",
        // "property_features": "Edit Property Features",
        // "property_compliance": "Edit Compliance Details",
        // "property_media": "Edit Media Details",
        // "property_accessibility": "Edit Property Accessibility",
        // "property_services": "Edit Property Services",
        // "property_status": "Edit Property Status",
        // "notes": "Edit Important Note",
        notes_tab: noteId ? 'Edit Note' : 'Add Note',
        bank_detail: bankId ? 'Edit Bank Detail' : 'Add Bank Detail',
    };
    
    let modalTitle = formTitles[formType] || "Edit Details"; // Default title if form type is not found

    $("#extraLargeModal .modal-title").html(modalTitle); // Set dynamic title

    // Remove previous modal size classes
    $("#extraLargeModal .modal-dialog").removeClass("modal-sm modal-lg modal-xl");

    // Apply the appropriate modal size based on the formType
    // if (formType === "property_status" || formType === "notes" || formType === "property_services") {
    //     // Use small modal for "notes" or "notes_tab"
    //     $("#extraLargeModal .modal-dialog").addClass("modal-md");
    // } else {
        // Default size (medium size) for other forms
        $("#extraLargeModal .modal-dialog").addClass("modal-xl");
    // }

    $.ajax({
        url: "{{ route('admin.users.loadForm') }}", // Route to get form dynamically
        type: "GET",
        data: { form_type: formType, user_id: userId, note_id: noteId, bank_detail_id: bankId },
        success: function (response) {
            $("#extraLargeModal .modal-body").html(response.form_html);
            $("#extraLargeModal").modal("show");

            // **Trigger the function ONLY for a specific form**
            if (formType === "user_detail") {
                $('.select2').select2();
                AIZ.extra.addMore();
                AIZ.extra.removeParent();
            }
            // if (formType === "property_media") {
            //     AIZ.uploader.previewGenerate();
            // }
            // if (formType === "property_accessibility") {
            //     initDynamicTagify();
            //     // initPlaces('#places-wrapper', '#add-place-btn');
            //     AIZ.extra.addMore();
            //     AIZ.extra.removeParent();
            // }
            // if (formType === "availability_pricing") {
            //     $('.select2').select2();
            // }
            if (formType === "notes_tab") {
                AIZ.plugins.textEditor();
            }
            // if (formType === "property_info") {
            //     toggleDescriptions();
            // }
        },
        error: function (error) {
            console.error(error);
            let errorMessage = error.responseJSON?.message || 'An error occurred while saving the compliance record.';
            AIZ.plugins.notify('danger', errorMessage);
        }
    });
});
$(document).on("submit", "#extraLargeModal form", function (e) {
    e.preventDefault(); 

    let form = $(this);
    let formData = form.serialize();
    let formType = form.find('input[name="form_type"]').val(); // Get form type dynamically
    let userId = form.find('input[name="user_id"]').val(); // Get property ID

    $.ajax({
        url: "{{ route('admin.users.saveForm') }}",
        type: "POST",
        data: formData,
        success: function (response) {
            if (response.success) {
                // Dynamically update the relevant accordion section
                $("#section-" + formType + "-" + userId).html(response.updated_html);

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

$(function(){
  // Add new email field
  $(document).on('click', '#add-email', function(e){
    e.preventDefault();
    $('#emails-wrapper').append(`
      <div class="flex items-center mb-2">
        <input
          type="email"
          name="emails[]"
          class="form-input flex-1"
          placeholder="email@example.com"
        >
        <button type="button" class="ml-2 text-red-600 remove-email">&times;</button>
      </div>
    `);
  });

  // Remove an email field
  $(document).on('click', '.remove-email', function(e){
    e.preventDefault();
    $(this).closest('div').remove();
  });

  // Add new phone field
  $(document).on('click', '#add-phone', function(e){
    e.preventDefault();
    $('#phones-wrapper').append(`
      <div class="flex items-center mb-2">
        <input
          type="text"
          name="phones[]"
          class="form-input flex-1"
          placeholder="+44 7000 000000"
        >
        <button type="button" class="ml-2 text-red-600 remove-phone">&times;</button>
      </div>
    `);
  });

  // Remove a phone field
  $(document).on('click', '.remove-phone', function(e){
    e.preventDefault();
    $(this).closest('div').remove();
  });
});
// View Bank Details
$(document).on('click', '.viewBank', function() {
    const bankId = $(this).data('id');
    const bankUrl = $(this).data('url');
    const type   = $(this).data('type');

    $.ajax({
        url: bankUrl,
        method: 'GET',
        success: function(response) {
            $("#extraLargeModal .modal-title").text(type);
            $("#extraLargeModal .modal-body").html(response.content); // show as plain text
            $("#extraLargeModal").modal("show");
        },
        error: function() {
            alert("Failed to load bank details.");
        }
    });
});
// View Notes
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
</script>
@endsection
