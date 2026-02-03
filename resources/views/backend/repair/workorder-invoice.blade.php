@extends('backend.layout.app')

@section('content')
@php
    $propertyId = $repairIssue->property->id;
@endphp
    <div class="container">
        <h2>{{ !$workorder ? 'Create Work Order & Invoice' : 'Edit Work Order & Invoice' }}</h2>
        <hr>    

        <!-- Hidden field for selected property IDs -->
        <input type="hidden" id="property_id" name="property_id" value="{{ $propertyId }}">

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="workorder-invoice-tabs">
            <li class="nav-item">
                <a class="nav-link active" id="workorder-tab" data-bs-toggle="tab" href="#workorder">Work Order</a>
            </li>
            <li class="nav-item">
                @if(!$invoice)
                    <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="Generate Invoice First">
                        <a class="nav-link disabled" id="invoice-tab" style="pointer-events: none;">Invoice</a>
                    </span>
                @else
                    <a class="nav-link" id="invoice-tab" data-bs-toggle="tab" href="#invoice">Invoice</a>
                @endif
            </li>
        </ul>
        


        <!-- Tab Content -->
        <div class="tab-content mt-3">
            <!-- Work Order Tab -->
            <div class="tab-pane fade show active" id="workorder">
                <h4>Work Order Details</h4>
                <!-- Work Order Modal -->
                @include('backend.work_orders.work_order_form')
            </div>

            <!-- Invoice Tab -->
            <div class="tab-pane fade" id="invoice">
                @include('backend.repair.invoice_form', ['invoice' => $invoice, 'users' => $users, 'taxRates' => $taxRates])
                {{-- @include('backend.invoices.edit', ['invoice' => $invoice, 'users' => $users, 'taxRates' =>
                $taxRates]) --}}
            </div>
        </div>
    </div>

@endsection

@section('page.scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

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

            // $("button[id='work_order_save_btn']").on('click', function(e) {
            //     e.preventDefault();
            //     initValidate('#workOrderForm');
            // });

            // Initialize jQuery validation on form load
            // initValidate('#workOrderForm');

            $('#workOrderForm').submit(function (e) {
                e.preventDefault();

                // Initialize validation only on submit
                initValidate('#workOrderForm');

                // Disable default HTML5 validation (optional, but useful for safety)
                $(this).attr('novalidate', 'novalidate');

                // Check if form is valid before submitting
                if (!$(this).valid()) {
                    return;
                }

                var formData = new FormData(this);
                $.ajax({
                    url: "{{ route('admin.work_orders.store') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        AIZ.plugins.notify('success', response.message);
                        // $('#workOrderModal').modal('hide');
                        location.reload(); // Refresh the page to show new work orders
                    },
                    error: function (error) {
                        console.error(error);
                        let errorMessage = error.responseJSON?.message || 'Error Creating Work Order';
                        AIZ.plugins.notify('danger', errorMessage);
                    }
                    // error: function (xhr) {
                    //     alert("Error Creating Work Order: " + xhr.responseJSON.message);
                    // }
                });
            });

            $(document).on("click", "#generateInvoiceBtn", function () {
                let $btn = $(this); // Store $(this) to avoid repeated lookups
                let workOrderId = $("#work_order_id").val() || $btn.data('workorder-id');

                if (!workOrderId) {
                    AIZ.plugins.notify('warning', 'No Work Order found!');
                    // alert("No Work Order found!");
                    return;
                }

                // Build the URL using the named route and replace the placeholder with the work order ID
                var url = "{{ route('admin.invoices.generate', ['workOrderId' => 'id']) }}".replace('id', workOrderId);

                $.ajax({
                    url: url,
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    beforeSend: function () {
                        $('#generateInvoiceBtn').text('Generating...').prop('disabled', true);
                    },
                    success: function (response) {
                        $('#invoice-message').html(`<div class="alert alert-success">${response.message}</div>`);
                        AIZ.plugins.notify('success', response.message);
                        $btn.text('Invoice Generated').prop('disabled', true); // Disable after success
                        setTimeout(function () {
                            location.reload();
                        }, 3000);
                    },
                    error: function (error) {
                        // console.error(error);
                        let errorMessage = error.responseJSON?.message || 'Error Generating Invoice';
                        AIZ.plugins.notify('danger', errorMessage);
                        $('#invoice-message').html(`<div class="alert alert-danger">${errorMessage}</div>`);
                        $('#generateInvoiceBtn').text('Generate Invoice').prop('disabled', false);
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
                        dropdown += '<select name="user_id" id="invoiceToSelect" class="form-control">';
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
                var name = selectedOption.data('name');
                var address = selectedOption.data('address');
                var phone = selectedOption.data('phone');
                var email = selectedOption.data('email');

                if (address) {
                    $('#userName').text(name);
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

            // var propertyAddress = $('.set-property-address').data('address');
            // $('.set-property-address').text(propertyAddress);

            // Show tab based on URL hash
            let hash = window.location.hash;
            if (hash) {
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            }

            // Change URL on tab switch
            $('.nav-tabs a').on('shown.bs.tab', function (e) {
                window.location.hash = e.target.hash;
            });

            // Function to recalculate tax, subtotal, and grand total
            function calculateworkorderTotals() {
                let subtotal = 0;
                let taxTotal = 0;
                let grandTotal = 0;

                $('#workorder-items tr').each(function () {
                    let unitPrice = parseFloat($(this).find('.unit-price').val()) || 0;
                    let quantity = parseInt($(this).find('.quantity').val()) || 1;
                    let taxRate = parseFloat($(this).find('.tax-rate').val()) || 0;

                    let rowSubtotal = unitPrice * quantity;
                    let taxAmount = (rowSubtotal * taxRate) / 100;
                    let rowTotal = rowSubtotal + taxAmount;

                    $(this).find('.tax-amount').val(taxAmount.toFixed(2));
                    $(this).find('.total-price').val(rowSubtotal.toFixed(2));

                    subtotal += rowSubtotal;
                    taxTotal += taxAmount;
                    grandTotal += rowTotal;
                });
                
                $('#subtotal').val(subtotal.toFixed(2));
                $('#tax-total').val(taxTotal.toFixed(2));
                $('#grand-total').val(grandTotal.toFixed(2));
            }

            // Event Listener for Calculations
            $(document).on('input change', '.unit-price, .quantity, .tax-rate', function () {
                calculateworkorderTotals();
            });

            $(document).on('change', '.tax-name', function () {
                let row = $(this).closest('tr');
                let selectedTaxRate = $(this).find(':selected').data('rate');

                // Set tax rate input only if it's not already pre-filled
                let taxRateInput = row.find('.tax-rate');
                if (!taxRateInput.val() || taxRateInput.val() == 0) {
                    taxRateInput.val(selectedTaxRate);
                }

                calculateworkorderTotals();
            });

            // Initial calculation on page load
            $('.unit-price, .quantity, .tax-name').trigger('change');
            // Add New Item Row
            $(document).on('click', '.add-workorder-item', function () {
                let index = $('#workorder-items tr').length;
                let taxOptions = `{!! $taxRates->map(fn($rate) => "<option value='$rate->id' data-rate='$rate->rate'>$rate->name</option>")->join('') !!}`;

                let newRow = `
                    <tr>
                        <td><input type="text" name="items[${index}][title]" class="form-control" required></td>
                        <td><input type="text" name="items[${index}][description]" class="form-control" required></td>
                        <td><input type="number" name="items[${index}][unit_price]" class="form-control unit-price" required></td>
                        <td><input type="number" name="items[${index}][quantity]" class="form-control quantity" min="1" value="1" required></td>
                        <td>
                            <select name="items[${index}][tax_name]" class="form-control tax-name">
                                ${taxOptions}
                            </select>
                        </td> 
                        <td><input type="number" name="items[${index}][tax_rate]" class="form-control tax-rate" required></td>             
                        <td><input type="text" class="form-control tax-amount" readonly></td>                   
                        <td><input type="text" class="form-control total-price" readonly></td>
                        <td>
                            <button type="button" class="btn btn_secondary add-workorder-item"><i class="fa-solid fa-plus"></i></button>
                        </td>
                    </tr>
                `;

                $('#workorder-items').append(newRow);

                // Set default tax rate of first option
                let lastRow = $('#workorder-items tr').last();
                let firstTaxRate = lastRow.find('.tax-name option:first').data('rate') || 0;
                lastRow.find('.tax-rate').val(firstTaxRate);

                calculateworkorderTotals(); // Recalculate totals

                // Change the previous row's Add button to Remove
                $('#workorder-items tr').eq(index - 1).find('.add-workorder-item').removeClass('btn_secondary add-workorder-item').addClass('btn-danger remove-item').html('<i class="fa-solid fa-minus"></i>');
            });

            // Remove Item Row
            $(document).on('click', '.remove-item', function () {
                $(this).closest('tr').remove();
                calculateworkorderTotals();

                // If only one row left, make sure it has "Add More" instead of "Remove"
                if ($('#workorder-items tr').length === 1) {
                    $('#workorder-items tr').eq(0).find('.remove-item').removeClass('btn-danger remove-item').addClass('btn_secondary add-workorder-item').html('<i class="fa-solid fa-plus"></i>');
                } else {
                    // Ensure last row always has Add More button
                    $('#workorder-items tr').last().find('td:last').html('<button type="button" class="btn btn_secondary add-workorder-item"><i class="fa-solid fa-plus"></i></button>');
                }
            });

            // Initial Calculation on Load
            calculateworkorderTotals();


            /*invoice*/
            function calculateInvoiceTotals() {
                let subtotal_invoice = 0;
                let taxTotal_invoice = 0;

                $('#invoice-items tr').each(function () {
                    let row = $(this);
                    let unitPrice = parseFloat(row.find('.unit-price_invoice').val()) || 0;
                    let quantity = parseInt(row.find('.quantity_invoice').val()) || 1;
                    let taxRate = parseFloat(row.find('.tax-rate_invoice').val()) || 0;

                    let itemTotal = unitPrice * quantity;
                    let taxAmount = (itemTotal * taxRate) / 100;
                    
                    row.find('.tax-amount_invoice').val(taxAmount.toFixed(2));
                    row.find('.total-price_invoice').val(itemTotal.toFixed(2));

                    subtotal_invoice += itemTotal;
                    taxTotal_invoice += taxAmount;
                });

                let grandTotal = subtotal_invoice + taxTotal_invoice;

                $('#subtotal_invoice').val(subtotal_invoice.toFixed(2));
                $('#tax_total_invoice').val(taxTotal_invoice.toFixed(2));
                $('#grand-total_invoice').val(grandTotal.toFixed(2));
            }

            $(document).on('input change', '.unit-price_invoice, .quantity_invoice, .tax-rate_invoice', function () {
                calculateInvoiceTotals();
            });

            $(document).on('change', '.name_invoice', function () {
                let row = $(this).closest('tr');
                let selectedTaxRate = $(this).find(':selected').data('rate');

                // Set tax rate input only if it's not already pre-filled
                let taxRateInput = row.find('.tax-rate_invoice');
                if (!taxRateInput.val()) {
                    taxRateInput.val(selectedTaxRate);
                }

                calculateInvoiceTotals();
            });

            $(document).on('click', '.add-invoice-item', function () {
                let index = $('#invoice-items tr').length;
                
                let newRow_invoice = `
                    <tr>
                        <td><input type="text" name="items[${index}][title]" class="form-control" required></td>
                        <td><input type="text" name="items[${index}][description]" class="form-control" required></td>
                        <td><input type="number" name="items[${index}][unit_price]" class="form-control unit-price_invoice" required></td>
                        <td><input type="number" name="items[${index}][quantity]" class="form-control quantity_invoice" required></td>
                        <td><input type="number" name="items[${index}][tax_rate]" class="form-control tax-rate_invoice" value="0.00" required></td>
                        <td><input type="text" class="form-control tax-amount_invoice" readonly></td>
                        <td><input type="text" class="form-control total-price_invoice" readonly></td>
                        <td>
                            <button type="button" class="btn btn_secondary add-invoice-item"><i class="fa-solid fa-plus"></i></button>
                        </td>
                    </tr>
                `;
                $('#invoice-items').append(newRow_invoice);

                // Set default tax rate of first option
                let lastRow = $('#invoice-items tr').last();
                let firstTaxRate = lastRow.find('.tax-name option:first').data('rate') || 0;
                lastRow.find('.tax-rate').val(firstTaxRate);

                calculateInvoiceTotals(); // Recalculate totals

                
                // Change the previous row's Add button to Remove
                $('#invoice-items tr').eq(index - 1).find('.add-invoice-item').removeClass('btn_secondary add-invoice-item').addClass('btn-danger remove-item').html('<i class="fa-solid fa-minus"></i>');

            });

            // Remove Item Row
            $(document).on('click', '.remove-invoice-item', function () {
                $(this).closest('tr').remove();
                calculateInvoiceTotals();

                // If only one row left, make sure it has "Add More" instead of "Remove"
                if ($('#invoice-items tr').length === 1) {
                    $('#invoice-items tr').eq(0).find('.remove-invoice-item').removeClass('btn-danger remove-invoice-item').addClass('btn_secondary add-invoice-item').html('<i class="fa-solid fa-plus"></i>');
                } else {
                    // Ensure last row always has Add More button
                    $('#invoice-items tr').last().find('td:last').html('<button type="button" class="btn btn_secondary add-invoice-item"><i class="fa-solid fa-plus"></i></button>');
                }
            });

            calculateInvoiceTotals();
        });
    </script>
@endsection