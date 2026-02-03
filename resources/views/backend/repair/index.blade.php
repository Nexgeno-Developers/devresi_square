@extends('backend.layout.app')

@section('content')
    <style>
        #detail-pane,
        #list-pane {
            transition: all 0.5s ease;
        }

        .hidden-pane {
            opacity: 0;
            visibility: hidden;
            width: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }

        .expanded-list {
            width: 100% !important;
        }

        .toggle-btn {
            position: sticky;
            bottom: 15px;
            left: 0;
            z-index: 10;
        }

        .spinner-overlay {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .repair-row>td:hover {
            color: #d83434;
        }

        thead>tr>th {
            background-color: #e9eef5 !important;
            color: #000 !important;
        }
        .repair-row.selected>td {
            background-color: #6c6c6c;
            color: #fff;
        }

        .repair-row>td {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
    </style>

    <div class="row" id="master-detail-wrapper">
        <!-- Left: List + Filters -->
        <div class="col-md-5" id="list-pane">
            <!-- Toggle Button -->
            <button id="toggle-detail-pane" class="btn btn-outline-secondary float-end toggle-btn mt-2">
                <i class="fas fa-chevron-left"></i> Hide Detail
            </button>
            @include('backend.repair.list.filter')
            @include('backend.repair.list.cards', [
                'repairIssues' => $repairIssues,
                'selectedRepairId' => isset($firstRepairIssue) ? $firstRepairIssue->id : null
            ])

        </div>
        <!-- Right: Detail -->
        <div class="col-md-7" id="detail-pane">
            @if(isset($firstRepairIssue))
                @include('backend.repair.detail.show', ['repairIssue' => $firstRepairIssue])
            @else
                <div class="alert alert-info">Select a repair item to view details.</div>
            @endif
        </div>
        
    </div>
    <!-- Include the Modal Component -->
    @include('backend.components.modal')
@endsection
{{-- Include the partial to push Select2 assets into the stacks --}}
@include('backend.partials.assets.select2')
@section('page.scripts')

    <script>
        let lastLoadedUrl = null; // Track the last detail URL
        // Open modal and load form via AJAX
        /*$(document).on("click", ".editForm", function () {
            let formType = $(this).data("form");
            let repairId = $(this).data("id");
            let formTitles = {
                "availability_pricing": "Edit Availability & Pricing",
                "property_info": "Edit Property Information",
                "property_features": "Edit Property Features",
                "property_details": "Edit Property Details",
                "property_accessibility": "Edit Property Accessibility",
                "property_services": "Edit Property Services",
                "property_status": "Edit Property Status",
                "notes": "Edit Important Note",
            };
            
            let modalTitle = formTitles[formType] || "Edit Details"; // Default title if form type is not found

            $("#largeModalScrollable .modal-title").html(modalTitle); // Set dynamic title
            $.ajax({
                url: "{{ route('admin.property_repairs.loadForm') }}", // Route to get form dynamically
                type: "GET",
                data: { form_type: formType, repair_id: repairId },
                success: function (response) {
                    $("#largeModalScrollable .modal-body").html(response.form_html);
                    $("#largeModalScrollable").modal("show");
                    PropertySelector.init();
                    RepairForm.init();
                    // **Trigger the function ONLY for a specific form**
                    if (formType === "property_issue_details") {
                        AIZ.uploader.previewGenerate();
                    }
                    if (formType === "property_accessibility") {
                        initDynamicTagify();
                    }
                    if (formType === "property_details") {
                        $('.select2').select2();
                    }
                },
                error: function (error) {
                    console.error(error);
                    let errorMessage = error.responseJSON?.message || 'An error occurred while saving the compliance record.';
                    AIZ.plugins.notify('danger', errorMessage);
                }
            });
        });
        $(document).on("submit", "#largeModalScrollable form", function (e) {
            e.preventDefault(); 

            let form = $(this);
            let formData = form.serialize();
            let formType = form.find('input[name="form_type"]').val(); // Get form type dynamically
            let repairId = form.find('input[name="repair_id"]').val(); // Get property ID

            $.ajax({
                url: "{{ route('admin.property_repairs.saveForm') }}", 
                type: "POST",
                data: formData,
                success: function (response) {
                    if (response.success) {
                        // Dynamically update the relevant accordion section
                        $("#section-" + formType + "-" + repairId).html(response.updated_html);

                        // Close the modal
                        $("#largeModalScrollable").modal("hide");
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
        });*/
        // $(document).ready(function () {
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
            
            const $toggleBtn = $('#toggle-detail-pane');

            function showDetailPane() {
                const $detailPane = $('#detail-pane');
                const $listPane = $('#list-pane');
                const $icon = $toggleBtn.find('i');

                if ($detailPane.hasClass('hidden-pane')) {
                    $detailPane.removeClass('hidden-pane col-md-0').addClass('col-md-7');
                    $listPane.removeClass('col-md-12').addClass('col-md-5');
                    $icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');
                    $toggleBtn.contents().last().replaceWith(' Hide Detail');
                }
            }

            // Toggle button click
            $toggleBtn.click(function () {
                const $detailPane = $('#detail-pane');
                const $listPane = $('#list-pane');
                const $icon = $(this).find('i');
                const isHidden = $detailPane.hasClass('hidden-pane');

                if (isHidden) {
                    // Show
                    $detailPane.removeClass('hidden-pane col-md-0').addClass('col-md-7');
                    $listPane.removeClass('col-md-12').addClass('col-md-5');
                    $icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');
                    $(this).contents().last().replaceWith(' Hide Detail');
                } else {
                    // Hide
                    $detailPane.addClass('hidden-pane col-md-0').removeClass('col-md-7');
                    $listPane.removeClass('col-md-5').addClass('col-md-12');
                    $icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
                    $(this).contents().last().replaceWith(' Show Detail');
                }
            });

            // AJAX Search form
            $('#filter-form').on('submit', function (e) {
                e.preventDefault();
                $.get(`{{ route('admin.property_repairs.index') }}`, $(this).serialize(), function (res) {
                    $('#card-list').html(res);
                    lastLoadedUrl = null; // Reset last detail URL
                });
            });

            // Load detail via AJAX + auto-show detail pane if hidden
            window.loadRepairDetailByUrl = function (el) {
                const url = $(el).data('url');

                showDetailPane(); // Ensure detail pane is visible

                // Prevent reloading same content
                if (url === lastLoadedUrl) return;
                lastLoadedUrl = url;

                const $detailPane = $('#detail-pane');
                // Spinner while loading
                $detailPane.html(`
                        <div class="spinner-overlay">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    `);

                $('.repair-row').removeClass('selected'); // Remove from all rows
                $(el).closest('.repair-row').addClass('selected'); // Add to clicked row

                $.get(url, function (response) {
                    // $('#detail-pane').html(response);
                    $detailPane.html('<div class="fade-in">' + response + '</div>');
                }).fail(function (xhr) {
                    // console.error(xhr);
                    $detailPane.html(`<div class="alert alert-danger fade-in">Failed to load detail.</div>`);
                });
            };

        // });

        
    </script>



@stack('extra.scripts')
@endsection