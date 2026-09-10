<script>
(function () {
    var select2Src = @json(asset('asset/js/select2.min.js'));

    function whenReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function loadSelect2($, done) {
        if (typeof $.fn.select2 === 'function') {
            done();
            return;
        }

        var existing = document.querySelector('script[data-tenancy-select2]');
        if (existing) {
            existing.addEventListener('load', done);
            existing.addEventListener('error', done);
            return;
        }

        var script = document.createElement('script');
        script.src = select2Src;
        script.setAttribute('data-tenancy-select2', '1');
        script.onload = done;
        script.onerror = done;
        document.head.appendChild(script);
    }

    function initTenancySelects($) {
        if (typeof $.fn.select2 !== 'function') {
            return;
        }

        $('#addTenancyForm .select2').each(function () {
            var $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }
            $el.select2({
                width: '100%',
                placeholder: 'Start typing at least 3 characters',
                minimumInputLength: 0,
                minimumResultsForSearch: 0
            });
        });
    }

    function waitForJQuery(attempt, done) {
        var $ = window.jQuery;
        if ($) {
            done($);
            return;
        }
        if (attempt >= 40) {
            return;
        }
        setTimeout(function () {
            waitForJQuery(attempt + 1, done);
        }, 50);
    }

    whenReady(function () {
        waitForJQuery(0, function ($) {
            if (!$('#addTenancyForm').length || window.__tenancyCreateFormBooted) {
                return;
            }
            window.__tenancyCreateFormBooted = true;

            loadSelect2($, function () {
                initTenancySelects($);
            });

            bootTenancyCreateForm($);
        });
    });

    function bootTenancyCreateForm($) {


    // â”€â”€ Deposit auto-calculation: Rent Ã— 12 Ã· 52 Ã— Weeks â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function calcDeposit() {
        const rent    = parseFloat($('#tenancies-rent').val()) || 0;
        const weeks   = parseFloat($('#depositNumber').val()) || 0;
        if (rent > 0 && weeks > 0) {
            const deposit = (rent * 12 / 52 * weeks).toFixed(2);
            $('#tenancies-deposit').val(deposit);
        } else {
            $('#tenancies-deposit').val('');
        }
    }
    $('#tenancies-rent, #depositNumber').on('input change', calcDeposit);
    calcDeposit();
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    // Render main person radio buttons when tenants are selected
    function renderMainPersonOptions() {
        const userSelect = $('#tenant_id');
        const container = $('#tenant-options');
        const selectedUsers = userSelect.val() || [];

        container.empty();
        $('#main-person-error').remove();

        if (selectedUsers.length > 0) {
            let html = '<div class="mb-3"><label class="form-label fw-semibold">Select Main Tenant <span class="text-danger">*</span></label>';
            selectedUsers.forEach(function(userId) {
                const userName = userSelect.find('option[value="' + userId + '"]').text();
                html += '<div class="form-check">' +
                    '<input type="radio" name="is_main_person" value="' + userId + '" id="is_main_' + userId + '" class="form-check-input">' +
                    '<label for="is_main_' + userId + '" class="form-check-label">' + userName + '</label>' +
                    '</div>';
            });
            html += '</div>';
            container.html(html);

            // Auto-select if only one tenant
            if (selectedUsers.length === 1) {
                container.find('input[type="radio"]').prop('checked', true);
            }
        }
    }

    $('#tenant_id').on('change', function() {
        renderMainPersonOptions();
    });

    // Form submission validation
    $('#addTenancyForm').on('submit', function(e) {
        e.preventDefault();
        let errors = [];

        // Clear previous errors
        $('#form-error-summary').remove();
        $('#main-person-error').remove();
        $('.is-invalid').removeClass('is-invalid');

        var propertyVal = $('#property_id').val() || $('input[name="property_id"]').val();
        if (!propertyVal) {
            errors.push('Please select a property.');
            $('#property_id').addClass('is-invalid');
        }

        // Validate tenants selected
        if ($('#tenant_id').val() === null || $('#tenant_id').val().length === 0) {
            errors.push('Please select at least one tenant.');
            // Highlight Select2 container border since Select2 replaces the native select
            $('#tenant_id').next('.select2-container').find('.select2-selection').addClass('is-invalid').css('border-color', '#dc3545');
        } else {
            $('#tenant_id').next('.select2-container').find('.select2-selection').removeClass('is-invalid').css('border-color', '');
        }

        // Validate main person selected
        if ($('input[name="is_main_person"]:checked').length === 0) {
            errors.push('Please select a main tenant.');
            $('#tenant-options').append('<div id="main-person-error" class="text-danger small mt-1">Please select a main tenant.</div>');
        }

        // Validate rent
        if (!$('#tenancies-rent').val()) {
            errors.push('Rent is required.');
            $('#tenancies-rent').addClass('is-invalid');
        }

        // Validate deposit weeks
        if (!$('#depositNumber').val()) {
            errors.push('Number of deposit weeks is required.');
            $('#depositNumber').addClass('is-invalid');
        }

        // Validate move in date
        if (!$('#tenancies-move_in').val()) {
            errors.push('Move In date is required.');
            $('#tenancies-move_in').addClass('is-invalid');
        }

        // Validate tenancy type
        if (!$('#tenancy-type').val()) {
            errors.push('Please select a Tenancy Type.');
            $('#tenancy-type').addClass('is-invalid');
        }

        // Validate sub status
        if (!$('#tenancies-sub_status').val()) {
            errors.push('Please select a Sub Status.');
            $('#tenancies-sub_status').addClass('is-invalid');
        }

        // Show frontend error banner and stop
        if (errors.length > 0) {
            let html = '<div id="form-error-summary" class="alert alert-danger mt-2 mb-3"><ul class="mb-0">';
            errors.forEach(function(err) { html += '<li>' + err + '</li>'; });
            html += '</ul></div>';
            // Insert banner at top of form, before first child
            $('#addTenancyForm').children().first().before(html);
            // Scroll modal body to top
            const $modalBody = $('#smallModal .modal-body');
            if ($modalBody.length) {
                $modalBody.animate({ scrollTop: 0 }, 200);
            }
            return;
        }

        // Submit via AJAX to preserve modal state on backend errors
        let form = $(this);
        let formData = new FormData(this);
        let submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#smallModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).text('Save');
                $('#form-error-summary').remove();
                let html = '<div id="form-error-summary" class="alert alert-danger mt-2 mb-3"><ul class="mb-0">';

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(field, messages) {
                        messages.forEach(function(msg) { html += '<li>' + msg + '</li>'; });
                        // Highlight the corresponding field
                        const fieldMap = {
                            'user_id': '#tenant_id',
                            'rent': '#tenancies-rent',
                            'deposit': '#tenancies-deposit',
                            'move_in': '#tenancies-move_in',
                            'tenancy_type_id': '#tenancy-type',
                            'tenancy_sub_status_id': '#tenancies-sub_status',
                            'deposit_number': '#depositNumber',
                        };
                        if (fieldMap[field]) {
                            $(fieldMap[field]).addClass('is-invalid');
                        }
                    });
                } else {
                    html += '<li>Something went wrong. Please try again.</li>';
                }

                html += '</ul></div>';
                $('#addTenancyForm').children().first().before(html);
                const $modalBody = $('#smallModal .modal-body');
                if ($modalBody.length) {
                    $modalBody.animate({ scrollTop: 0 }, 200);
                }
            }
        });
    });
    $(document).on('change', '#depositService', function () {
        const selected = $(this).val();

        if (selected === 'number_of_scheme_or_number_of_reference') {
            $('#referenceNumberSchemeField').removeClass('d-none');
            $('#referenceNumber').attr('required', true);

            $('#depositSchemeDropdown').removeClass('d-none');
            $('#depositScheme').attr('required', true);

            $('#tds_dps_numberField').addClass('d-none');
            $('#tds_dps_number').removeAttr('required');
        } else if (selected === 'tds_dps_number') {
            // Hide and un-require the other fields
            $('#referenceNumberSchemeField').addClass('d-none');
            $('#referenceNumber').removeAttr('required');

            $('#depositSchemeDropdown').addClass('d-none');
            $('#depositScheme').removeAttr('required');

            // Show and require the TDS/DPS reference number
            $('#tds_dps_numberField').removeClass('d-none');
            $('#tds_dps_number').attr('required', true);
        }
    });
    // document.addEventListener('DOMContentLoaded', function () {
    // Function to initialize the popup form logic
    // function initializeForm() {
    //     const moveInInput = document.getElementById('tenancies-move_in');
    //     const termInput = document.getElementById('tenancies-term');
    //     const termUnitSelect = document.getElementById('tenancies-term_unit');
    //     const moveOutInput = document.getElementById('tenancies-move_out');

    //     // Only initialize if the elements exist
    //     if (moveInInput && termInput && termUnitSelect && moveOutInput) {
    //         // Function to update the move-out date based on move-in date, term, and term unit
    //         function updateMoveOutDate() {
    //             const moveInDate = new Date(moveInInput.value);
    //             const term = parseInt(termInput.value);
    //             const termUnit = termUnitSelect.value;

    //             if (moveInDate instanceof Date && !isNaN(moveInDate) && term && termUnit) {
    //                 let moveOutDate;

    //                 if (termUnit === 'months') {
    //                     // Add months
    //                     moveOutDate = new Date(moveInDate.setMonth(moveInDate.getMonth() + term));
    //                 } else if (termUnit === 'days') {
    //                     // Add days
    //                     moveOutDate = new Date(moveInDate.setDate(moveInDate.getDate() + term));
    //                 }

    //                 // Set the move-out date to the calculated date
    //                 moveOutInput.value = moveOutDate.toISOString().split('T')[0];
    //             }
    //         }

    //         // Remove existing event listeners if any (this helps avoid re-binding the same listeners)
    //         moveInInput.removeEventListener('change', updateMoveOutDate);
    //         termInput.removeEventListener('input', updateMoveOutDate);
    //         termUnitSelect.removeEventListener('change', updateMoveOutDate);

    //         // Add event listeners for changes in move-in date, term, or term unit
    //         moveInInput.addEventListener('change', updateMoveOutDate);
    //         termInput.addEventListener('input', updateMoveOutDate);
    //         termUnitSelect.addEventListener('change', updateMoveOutDate);

    //         // Initial calculation if values are already present
    //         updateMoveOutDate();
    //     }
    // }

    function initializeForm() {
        const moveInInput = document.getElementById('tenancies-move_in');
        const termMonthsInput = document.getElementById('tenancies-term_months');
        const termDaysInput = document.getElementById('tenancies-term_days');
        const moveOutInput = document.getElementById('tenancies-move_out');

        if (!moveInInput || !termMonthsInput || !termDaysInput || !moveOutInput) return;

        // Function to calculate the "Move Out" date
        function calculateMoveOutDate() {
            const moveInDate = new Date(moveInInput.value);
            const termMonths = parseInt(termMonthsInput.value, 10) || 0; // Default to 0 if empty
            const termDays = parseInt(termDaysInput.value, 10) || 0; // Default to 0 if empty

            if (isNaN(moveInDate.getTime())) {
                moveOutInput.value = '';
                return;
            }

            // Add months and days to the "Move In" date
            const resultDate = new Date(moveInDate);
            if (termMonths > 0) {
                resultDate.setMonth(resultDate.getMonth() + termMonths);
                // Subtract 1 day for tenancy default behavior
                resultDate.setDate(resultDate.getDate() - 1);
            }
            if (termDays > 0) resultDate.setDate(resultDate.getDate() + termDays);

            // Set the calculated "Move Out" date
            moveOutInput.value = resultDate.toISOString().split('T')[0];
        }
        // Function to calculate the term in months and days based on Move In and Move Out dates
        function recalculateTerm() {
            const moveInDate = new Date(moveInInput.value);
            const moveOutDate = new Date(moveOutInput.value);

            if (isNaN(moveInDate.getTime()) || isNaN(moveOutDate.getTime())) return;

            const timeDifference = moveOutDate - moveInDate; // Difference in milliseconds
            const daysDifference = timeDifference / (1000 * 3600 * 24); // Convert to days

            const months = Math.floor(daysDifference / 30); // Approximate months
            const remainingDays = daysDifference % 30; // Remainder days

            // Update the term input fields
            termMonthsInput.value = months;
            termDaysInput.value = remainingDays;
        }

        // Function to validate the "Move Out" date and ensure it's after the "Move In" date
        function validateMoveOutDate() {
            const moveInDate = new Date(moveInInput.value);
            const moveOutDate = new Date(moveOutInput.value);

            if (isNaN(moveInDate.getTime()) || isNaN(moveOutDate.getTime())) return;

            if (moveOutDate <= moveInDate) {
                // Show error message or reset the Move Out date if it's invalid
                alert("Move Out Date must be greater than Move In Date.");
                moveOutInput.value = ''; // Reset Move Out Date
            }
        }

        // Function to ensure that the Term fields cannot have negative values
        function validateTermInputs() {
            const termMonths = parseInt(termMonthsInput.value, 10);
            const termDays = parseInt(termDaysInput.value, 10);

            if (termMonths < 0) termMonthsInput.value = 0;
            if (termDays < 0) termDaysInput.value = 0;
        }

        // Add event listeners to update the "Move Out" date on input change
        moveInInput.addEventListener('change', () => {
            // Only calculate Move Out if either termMonths or termDays is set
            if (termMonthsInput.value > 0 || termDaysInput.value > 0) {
                calculateMoveOutDate();
            }
        });

        // moveInInput.addEventListener('change', calculateMoveOutDate);
        termMonthsInput.addEventListener('input', (e)=> {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            validateTermInputs();
            calculateMoveOutDate();
        });
        termDaysInput.addEventListener('input', (e)=> {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            validateTermInputs();
            calculateMoveOutDate();
        });
        moveOutInput.addEventListener('change', () => {
            calculateMoveOutDate();
            recalculateTerm();
            validateMoveOutDate(); // Validate if Move Out Date is after Move In Date
        });

        // Initial calculation if values are already filled
        calculateMoveOutDate();
            
        // Trigger change event on page load to auto-populate based on current selection
        $('#depositService').trigger('change');
    }

    // Initialize the form only once the content is fully loaded
    initializeForm();

    $(document).off('click.tenancyQuickAdd', '#addUserBtn').on('click.tenancyQuickAdd', '#addUserBtn', function () {
        $('#mainForm').hide();
        $('#addUserFormContainer').show();
    });

    $(document).off('click.tenancyQuickAdd', '#backToMainForm').on('click.tenancyQuickAdd', '#backToMainForm', function () {
        $('#addUserFormContainer').hide();
        $('#mainForm').show();
    });

    $(document).off('submit.tenancyQuickAdd', '#addUserForm').on('submit.tenancyQuickAdd', '#addUserForm', function (event) {
        event.preventDefault();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        var form = $(this);

        $.ajax({
            url: '{{ route('admin.users.quick_user_store') }}',
            method: 'POST',
            data: form.serialize(),
            success: function (response) {
                if (!response.success || !response.user) {
                    alert('Failed to add tenant.');
                    return;
                }

                var option = new Option(response.user.name, response.user.id, true, true);
                $('#tenant_id').append(option).trigger('change');
                $('#addUserFormContainer').hide();
                $('#mainForm').show();
                form[0].reset();
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        var input = $('#user_' + field);
                        if (input.length) {
                            input.addClass('is-invalid');
                            if (input.next('.invalid-feedback').length === 0) {
                                input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                            }
                        }
                    });
                    return;
                }
                alert('Could not add that tenant. Try again.');
            }
        });
    });
    }
})();
</script>
