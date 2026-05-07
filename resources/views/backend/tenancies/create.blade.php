<div id="mainForm">

    <form id="addTenancyForm" action="{{ route('admin.tenancies.store') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="property_id" class="form-control" value="{{ old('property_id', $propertyId ?? '') }}">

        <div class="form-group">
            <button type="button" class="btn btn-outline-primary btn-sm" id="addUserBtn">
                Quick Add New Tenant
            </button>
            <label for="tenant_id">Select Tenants <span class="text-danger">*</span></label>
            <select name="user_id[]" id="tenant_id" multiple class="form-control select2" required>
                @foreach ($tenants as $user)
                    <option value="{{ $user->id }}" {{ in_array($user->id, old('user_id', [])) ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
            @error('user_id')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div id="tenant-options" class="mt-3"></div>

        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancies-status required">
                        <label class="control-label" for="tenancies-status">Status</label>
                        <select required id="tenancies-status" class="form-control" name="status" aria-required="true">
                            <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Archive" {{ old('status') == 'Archive' ? 'selected' : '' }}>Archive</option>
                        </select>
                        @error('status')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancy-type required">
                        <label class="control-label" for="tenancy-type">Tenancy Type</label>
                        <select id="tenancy-type" class="form-control" aria-required="true" name="tenancy_type_id" required>
                            <option value="" disabled {{ old('tenancy_type_id') ? '' : 'selected' }}>Select Tenancy Type</option>
                            @foreach ($tenancyTypes as $tenancyType)
                                <option value="{{ $tenancyType->id }}" {{ old('tenancy_type_id') == $tenancyType->id ? 'selected' : '' }}>{{ $tenancyType->name }}</option>
                            @endforeach
                        </select>
                        @error('tenancy_type_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="mb-3">
                    <div class="form-group field-tenancies-sub_status required has-error">
                        <label class="control-label" for="tenancies-sub_status">Sub Status</label>
                        <select id="tenancies-sub_status" class="form-control" name="tenancy_sub_status_id" aria-required="true" required>
                            <option value="" disabled {{ old('tenancy_sub_status_id') ? '' : 'selected' }}>Select Sub Status</option>
                            @foreach ($tenancySubStatuses as $subStatus)
                                <option value="{{ $subStatus->id }}" {{ old('tenancy_sub_status_id') == $subStatus->id ? 'selected' : '' }}>{{ $subStatus->name }}</option>
                            @endforeach
                        </select>
                        @error('tenancy_sub_status_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <label>
                <input type="checkbox" name="periodic" {{ old('periodic') ? 'checked' : '' }}>
                Periodic
            </label><br>

            <label>
                <input type="checkbox" name="rolling_contract" {{ old('rolling_contract') ? 'checked' : '' }}>
                Rolling Contract
            </label><br>

            <label>
                <input type="checkbox" name="renewal_exempt" {{ old('renewal_exempt') ? 'checked' : '' }}>
                Renewal Exempt
            </label><br>
        </div>

        <div class="row mt-3">
            <div class="col">
                <div class="mb-3">
                    <label class="control-label" for="tenancies-move_in">Move In <span class="text-danger">*</span></label>
                    <input type="date" id="tenancies-move_in" class="form-control" name="move_in" required value="{{ old('move_in') }}">
                    @error('move_in')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <label class="control-label" for="tenancies-term_months">Term (Months)</label>
                    <input type="number" id="tenancies-term_months" class="form-control" name="term_months" min="0" pattern="^[0-9]+$" value="{{ old('term_months') }}">
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <label class="control-label" for="tenancies-term_days">Term (Days)</label>
                    <input type="number" id="tenancies-term_days" class="form-control" name="term_days" min="0" pattern="^[0-9]+$" value="{{ old('term_days') }}">
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <label class="control-label" for="tenancies-move_out">Move Out</label>
                    <input type="date" id="tenancies-move_out" class="form-control" name="move_out" value="{{ old('move_out') }}">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancies-tenancy_renewal_confirm_date">
                        <label class="control-label" for="tenancies-tenancy_renewal_confirm_date">Renewal Confirm Date</label>
                        <input type="date" id="tenancies-tenancy_renewal_confirm_date" class="form-control"
                            name="tenancy_renewal_confirm_date" min="{{ todayDate() }}" value="{{ old('tenancy_renewal_confirm_date') }}">
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancies-extension_date">
                        <label class="control-label" for="tenancies-extension_date">Extension Date</label>
                        <input type="date" id="tenancies-extension_date" class="form-control"
                            name="extension_date" min="{{ tomorrowDate() }}" value="{{ old('extension_date') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancies-rent">
                        <label class="control-label" for="tenancies-rent">Rent <span class="text-danger">*</span></label>
                        <input type="number" inputmode="numeric" pattern="[0-9]" id="tenancies-rent"
                            class="form-control" name="rent" required value="{{ old('rent') }}">
                        @error('rent')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancies-deposit">
                        <label class="control-label" for="tenancies-deposit">Deposit <span class="text-danger">*</span></label>
                        <input type="number" inputmode="numeric" pattern="[0-9]" id="tenancies-deposit"
                            class="form-control" name="deposit" required value="{{ old('deposit') }}" readonly>
                        @error('deposit')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="row my-4">
            <div class="col">
                <div class="mb-3">
                    <label for="depositType" class="form-label">Deposit Type</label>
                    <select class="form-select" id="depositType" name="deposit_type">
                        <option value="weeks_deposit">No of Weeks Deposit</option>
                        {{-- <option value="months_deposit">No of Months Deposit</option> --}}
                    </select>
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <label for="depositNumber" class="form-label">Number of deposit type (Weeks)</label>
                    <input type="number" class="form-control" id="depositNumber" name="deposit_number" min="1" placeholder="Enter number of weeks or months" required value="{{ old('deposit_number') }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <label for="deposit_held_by" class="form-label">Deposit Held By</label>
                    <select class="form-select" id="deposit_held_by" name="deposit_held_by">
                        <option value="landlord_holding">Landlord Holding</option>
                        <option value="deposit_protection_service">Deposit Protection Service</option>
                        <option value="deposit_replacement_scheme">Deposit Replacement Scheme</option>
                        <option value="agent_holding_as_stakeholder">Agent Holding As Stake Holder</option>
                    </select>
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <label for="depositService" class="form-label">Deposit Service</label>
                    <select class="form-select" id="depositService" name="deposit_service">
                        <option value="tds_dps_number">*TDS or DPS Number</option>
                        <option value="number_of_scheme_or_number_of_reference">Name of the scheme Reference | Number of Scheme</option>
                    </select>
                </div>

                <div class="mb-3 d-none" id="tds_dps_numberField">
                    <label for="tds_dps_number" class="form-label">TDS / DPS Reference Number</label>
                    <input type="text" class="form-control" id="tds_dps_number" name="tds_dps_number" />
                </div>

                <div class="mb-3 d-none" id="referenceNumberSchemeField">
                    <label for="referenceNumber" class="form-label">Reference Number</label>
                    <input type="text" class="form-control" id="referenceNumber" name="reference_number" />
                </div>
            
                <div class="mb-3 d-none" id="depositSchemeDropdown">
                    <label for="depositScheme" class="form-label">Deposit Scheme</label>
                    <select class="form-select" id="depositScheme" name="deposit_scheme">
                        <option value="">-- Select a scheme --</option>
                        <option value="dps">Deposit Protection Service</option>
                        <option value="tds">Tenancy Deposit Scheme</option>
                        <option value="drs">Deposit Replacement Scheme</option>
                    </select>
                </div>

            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-property_manager required has-success">
                        <label class="control-label" for="property_manager">Property Manager</label>
                        <select name="property_manager[]" id="property_manager" multiple class="form-control select2"
                            required>
                            @foreach ($property_managers as $property_manager)
                                <option value="{{ $property_manager->id }}" {{ in_array($property_manager->id, old('property_manager', [])) ? 'selected' : '' }}>{{ $property_manager->name }}</option>
                            @endforeach
                        </select>
                        @error('property_manager')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="float-end mt-3 btn btn_secondary">Save</button>
    </form>
</div>

<!-- Add User Form (Step 2) -->
<div id="addUserFormContainer" style="display: none;">
    <form id="addUserForm">
        @csrf
        {{-- <input type="hidden" class="form-control" id="category_id" name="category_id" value="3"> --}}
        <input type="hidden" name="role" value="Tenant">
        <div class="mb-3">
            <label for="user_name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="user_name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="user_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="user_email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="user_phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="user_phone" name="phone" required>
        </div>
        <button type="submit" class="btn btn-primary">Save User</button>
        <button type="button" class="btn btn-secondary" id="backToMainForm">Back</button>
    </form>
</div>
<script>

    initSelect3('.select2');

    // ── Deposit auto-calculation: Rent × 12 ÷ 52 × Weeks ──────────────────
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
    // ────────────────────────────────────────────────────────────────────────

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

        // Validate property_id is set (injected server-side via query param)        // Validate tenants selected
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
    // });
</script>
