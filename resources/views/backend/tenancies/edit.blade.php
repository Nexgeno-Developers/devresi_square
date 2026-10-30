@php
    // echo'<pre>';
    // var_dump($tenancy);
    // echo'</pre>';

    // Check if the properties are set before storing them in variables
    $status = isset($tenancy->status) ? $tenancy->status : null;
    $move_in = isset($tenancy->move_in) ? $tenancy->move_in : null;
    $move_out = isset($tenancy->move_out) ? $tenancy->move_out : null;
    $tenancy_renewal_confirm_date = isset($tenancy->tenancy_renewal_confirm_date)
        ? $tenancy->tenancy_renewal_confirm_date
        : null;
    $extension_date = isset($tenancy->extension_date) ? $tenancy->extension_date : null;
    $rent = isset($tenancy->rent) ? $tenancy->rent : null;
    $deposit = isset($tenancy->deposit) ? $tenancy->deposit : null;
    $deposit_type = isset($tenancy->deposit_type) ? $tenancy->deposit_type : '';
    $deposit_number = isset($tenancy->deposit_number) ? $tenancy->deposit_number : '';
    $deposit_held_by = isset($tenancy->deposit_held_by) ? $tenancy->deposit_held_by : '';
    $deposit_service = isset($tenancy->deposit_service) ? $tenancy->deposit_service : '';

    $tds_dps_number = isset($tenancy->tds_dps_number) ? $tenancy->tds_dps_number : '';
    $reference_number = isset($tenancy->reference_number) ? $tenancy->reference_number : '';
    $deposit_scheme = isset($tenancy->deposit_scheme) ? $tenancy->deposit_scheme : '';

    $periodic = isset($tenancy->periodic) && $tenancy->periodic === 1 ? 1 : 0;
    $rolling_contract = isset($tenancy->rolling_contract) && $tenancy->rolling_contract === 1 ? 1 : 0;
    $renewal_exempt = isset($tenancy->renewal_exempt) && $tenancy->renewal_exempt === 1 ? 1 : 0;
    $term_months = isset($tenancy->term_months) ? $tenancy->term_months : '';
    $term_days = isset($tenancy->term_days) ? $tenancy->term_days : '';

@endphp

@include('backend.tenancies._record-form-styles')
<div id="mainForm" class="rs-sheet">
    <form id="editTenancyForm" class="rs-form" action="{{ route('admin.tenancies.update', $tenancy->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Please fix the highlighted problems</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @php $isLandlord = is_landlord_plan_user(); @endphp
        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>Household</h2>
                    <p>The home and who lives there.</p>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addUserBtn">Quick add tenant</button>
            </div>
            <div class="rs-grid">
                @if($isLandlord || empty($tenancy->property_id) || ($properties ?? collect())->isNotEmpty())
                    <label class="rs-field rs-span-4" for="property_id">
                        Property
                        <select name="property_id" id="property_id" class="form-select" required>
                            <option value="">Select a property</option>
                            @foreach(($properties ?? collect()) as $property)
                                <option value="{{ $property->id }}" @selected((string) old('property_id', $tenancy->property_id) === (string) $property->id)>
                                    {{ $property->full_address ?: ($property->line_1 ?: 'Property #'.$property->id) }}
                                </option>
                            @endforeach
                        </select>
                        @error('property_id')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                        @if(empty($tenancy->property_id))
                            <span class="form-text">This tenancy is not linked to a property. Choose one so you can invite the tenant and issue rent.</span>
                        @endif
                    </label>
                @else
                    <input type="hidden" name="property_id" id="property_id" value="{{ $tenancy->property_id }}" data-property-label="{{ $tenancy->property->full_address ?? ($tenancy->property->line_1 ?? '') }}">
                @endif
                <label class="rs-field rs-span-2" for="tenancies-status">
                    Status
                    <select id="tenancies-status" class="form-select" name="status">
                        <option value="Active" @selected($status === 'Active')>Active</option>
                        <option value="Archived" @selected(in_array($status, ['Archived', 'Archive'], true))>Archived</option>
                    </select>
                </label>
                <label class="rs-field rs-span-6" for="tenant_id">
                    Tenants
                    <select name="user_id[]" id="tenant_id" multiple class="form-control select2 @error('user_id') is-invalid @enderror">
                        @foreach ($tenants as $user)
                            <option value="{{ $user->id }}" @selected(in_array($user->id, old('user_id', $tenancy->tenantMembers->pluck('user_id')->toArray())))>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                    @error('is_main_person')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </label>
                <div id="tenant-options" class="rs-span-6" data-current-main="{{ $tenancy->tenantMembers->where('is_main_person', 1)->first()?->user_id ?? '' }}"></div>
            </div>
        </section>

        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>Dates</h2>
                    <p>When the let starts, how long it runs, and when it ends.</p>
                </div>
            </div>
            <div class="rs-grid">
                <label class="rs-field rs-span-2" for="tenancies-move_in">
                    Move in
                    <input type="date" id="tenancies-move_in" class="form-control @error('move_in') is-invalid @enderror" name="move_in" value="{{ old('move_in', $move_in ? \Illuminate\Support\Carbon::parse($move_in)->format('Y-m-d') : '') }}" required>
                    @error('move_in')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </label>
                <label class="rs-field rs-span-2" for="tenancies-term_months">
                    Term (months)
                    <input type="number" id="tenancies-term_months" class="form-control" name="term_months" min="1" max="36" value="{{ old('term_months', $term_months ?: 12) }}">
                </label>
                <label class="rs-field rs-span-2" for="tenancies-move_out">
                    Move out
                    <input type="date" id="tenancies-move_out" class="form-control" name="move_out" value="{{ old('move_out', $move_out ? \Illuminate\Support\Carbon::parse($move_out)->format('Y-m-d') : '') }}">
                </label>
            </div>
        </section>

        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>Rent</h2>
                    <p>The amount, how often it is paid, and the deposit held for this let.</p>
                </div>
            </div>
            <div class="rs-grid">
                <label class="rs-field rs-span-3" for="tenancies-rent">
                    Rent
                    <input type="number" inputmode="decimal" step="0.01" id="tenancies-rent" class="form-control @error('rent') is-invalid @enderror" name="rent" value="{{ old('rent', $rent) }}" required>
                    @error('rent')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </label>
                <label class="rs-field rs-span-3" for="tenancies-frequency">
                    How often
                    <select class="form-select" id="tenancies-frequency" name="frequency">
                        <option value="Monthly" @selected(old('frequency', $tenancy->frequency ?: 'Monthly') === 'Monthly')>Monthly</option>
                        <option value="Weekly" @selected(old('frequency', $tenancy->frequency) === 'Weekly')>Weekly</option>
                    </select>
                </label>
                <label class="rs-field rs-span-3" for="tenancies-rent-due-day">
                    Rent due day
                    <input type="number" min="1" max="28" class="form-control" id="tenancies-rent-due-day" name="rent_due_day" value="{{ old('rent_due_day', $tenancy->rent_due_day ?: 1) }}">
                    <span class="form-text">Day of the month. Weekly rent is due on the first day of each week.</span>
                </label>
                <label class="rs-field rs-span-3" for="tenancies-deposit">
                    Deposit
                    <input type="number" inputmode="decimal" step="0.01" min="0" id="tenancies-deposit" class="form-control @error('deposit') is-invalid @enderror" name="deposit" required value="{{ old('deposit', $deposit) }}">
                    @error('deposit')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </label>
            </div>
        </section>

        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>Deposit protection</h2>
                    <p>Scheme and the dates that start the 30-day protection clock.</p>
                </div>
            </div>
            <div class="rs-grid">
                <label class="rs-field rs-span-3" for="depositScheme">
                    Scheme
                    <select class="form-select" id="depositScheme" name="deposit_scheme">
                        <option value="">Not set</option>
                        <option value="dps" @selected(old('deposit_scheme', $deposit_scheme) === 'dps')>Deposit Protection Service</option>
                        <option value="tds" @selected(old('deposit_scheme', $deposit_scheme) === 'tds')>Tenancy Deposit Scheme</option>
                        <option value="drs" @selected(old('deposit_scheme', $deposit_scheme) === 'drs')>Deposit Replacement Scheme</option>
                    </select>
                </label>
                <label class="rs-field rs-span-3" for="referenceNumber">
                    Scheme reference
                    <input type="text" class="form-control" id="referenceNumber" name="reference_number" value="{{ old('reference_number', $reference_number ?: $tds_dps_number) }}">
                </label>
                <label class="rs-field rs-span-3">
                    Deposit received
                    <input type="datetime-local" class="form-control" name="deposit_received_at" value="{{ old('deposit_received_at', $tenancy->deposit_received_at?->format('Y-m-d\TH:i')) }}">
                </label>
                <label class="rs-field rs-span-3">
                    Deposit protected
                    <input type="datetime-local" class="form-control" name="deposit_protected_at" value="{{ old('deposit_protected_at', $tenancy->deposit_protected_at?->format('Y-m-d\TH:i')) }}">
                </label>
                <label class="rs-field rs-span-3">
                    Prescribed information sent
                    <input type="datetime-local" class="form-control" name="prescribed_information_sent_at" value="{{ old('prescribed_information_sent_at', $tenancy->prescribed_information_sent_at?->format('Y-m-d\TH:i')) }}">
                </label>
                <label class="rs-field rs-span-3">
                    Written terms sent
                    <input type="datetime-local" class="form-control" name="written_terms_sent_at" value="{{ old('written_terms_sent_at', $tenancy->written_terms_sent_at?->format('Y-m-d\TH:i')) }}">
                </label>
            </div>
        </section>

        @if(! is_landlord_plan_user())
        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>Assignment</h2>
                    <p>Who looks after this let inside the agency.</p>
                </div>
            </div>
            <div class="rs-grid">
                <label class="rs-field rs-span-4" for="property_manager">
                    Property Manager
                    <select name="property_manager[]" id="property_manager" multiple class="form-control select2">
                        @foreach ($property_managers as $property_manager)
                            <option value="{{ $property_manager->id }}" @selected(in_array($property_manager->id, $currentPropertyManagers))>{{ $property_manager->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>
        @endif

        <div class="rs-actions">
            <button type="submit" class="btn lw-btn-primary">Save tenancy</button>
        </div>
    </form>
</div>

<div id="addUserFormContainer" class="rs-sheet" style="display: none;">
    <form id="addUserForm">
        @csrf
        <input type="hidden" name="role" value="Tenant">
        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>New tenant</h2>
                    <p>Name and email are enough. Phone can wait.</p>
                </div>
            </div>
            <div class="rs-grid">
                <label class="rs-field rs-span-2" for="user_name">Full name<input type="text" class="form-control" id="user_name" name="name" required></label>
                <label class="rs-field rs-span-2" for="user_email">Email<input type="email" class="form-control" id="user_email" name="email" required></label>
                <label class="rs-field rs-span-2" for="user_phone">Phone<input type="text" class="form-control" id="user_phone" name="phone" maxlength="40"></label>
            </div>
            <div class="rs-actions">
                <button type="button" class="btn btn-light" id="backToMainForm">Back</button>
                <button type="submit" class="btn lw-btn-primary">Save tenant</button>
            </div>
        </section>
    </form>
</div>

<script>
(function () {
    var select2Src = @json(asset('asset/js/select2.min.js'));

    function bootTenancyEdit() {
    var $ = window.jQuery;
    if (! $ || typeof $.fn.select2 !== 'function') {
        return;
    }

    if (typeof initSelect3 === 'function') {
        initSelect3('.select2');
    }


    if ($('#tenant_id').hasClass('select2-hidden-accessible')) {
        $('#tenant_id').select2('destroy');
    }
    $('#tenant_id').select2({
        placeholder: 'Choose tenants',
        minimumInputLength: 0,
        language: {
            inputTooShort: function () { return 'Please enter 3 or more characters'; }
        }
    });

    // ── Deposit auto-calculation: Rent × 12 ÷ 52 × Weeks ──────────────────
    // ────────────────────────────────────────────────────────────────────────

    // Render main person radio buttons when tenants are selected/changed
    function renderMainPersonOptions() {
        const userSelect    = $('#tenant_id');
        const container     = $('#tenant-options');
        const selectedUsers = userSelect.val() || [];
        const defaultMain   = container.data('current-main') || null;
        const currentMain   = $('input[name="is_main_person"]:checked').val() || defaultMain;

        container.empty();
        $('#main-person-error').remove();

        if (selectedUsers.length > 0) {
            let html = '<div class="rs-lead"><span>Lead tenant</span>';
            selectedUsers.forEach(function(userId) {
                const userName = userSelect.find('option[value="' + userId + '"]').text();
                const isChecked = (String(currentMain) === String(userId)) ? 'checked' : '';
                html += '<label class="form-check" for="is_main_' + userId + '">' +
                    '<input type="radio" name="is_main_person" value="' + userId + '" id="is_main_' + userId + '" class="form-check-input" ' + isChecked + '>' +
                    '<span>' + userName + '</span>' +
                    '</label>';
            });
            html += '</div>';
            container.html(html);

            if (selectedUsers.length === 1) {
                container.find('input[type="radio"]').prop('checked', true);
            }
        }
    }

    // Re-render radio buttons whenever tenant selection changes
    $('#tenant_id').on('change', function() {
        renderMainPersonOptions();
    });

    // Run on load — reads only the currently selected tenants (not all options)
    renderMainPersonOptions();

    // Form submission validation
    $('#editTenancyForm').on('submit', function(e) {
        if ($('input[name="is_main_person"]:checked').length === 0) {
            e.preventDefault();
            $('#main-person-error').remove();
            $('#tenant-options').append('<div id="main-person-error" class="text-danger small mt-1">Please select a main tenant.</div>');
            $('#smallModal .modal-body').scrollTop(0);
        }
    });

    $(document).on('change', '#depositService', function () {
        const selected = $(this).val();

        if (selected === 'number_of_scheme_or_number_of_reference') {
            $('#referenceNumberSchemeField').removeClass('d-none');
            $('#referenceNumber').removeAttr('required');

            $('#depositSchemeDropdown').removeClass('d-none');
            $('#depositScheme').removeAttr('required');

            $('#tds_dps_numberField').addClass('d-none');
            $('#tds_dps_number').removeAttr('required');
        } else if (selected === 'tds_dps_number') {
            $('#tds_dps_numberField').removeClass('d-none');
            $('#tds_dps_number').removeAttr('required');

            $('#referenceNumberSchemeField').addClass('d-none');
            $('#referenceNumber').removeAttr('required');

            $('#depositSchemeDropdown').addClass('d-none');
            $('#depositScheme').removeAttr('required');
        }
    });

    function initializeForm() {
        const moveInInput = document.getElementById('tenancies-move_in');
        const termMonthsInput = document.getElementById('tenancies-term_months');
        const termDaysInput = document.getElementById('tenancies-term_days');
        const moveOutInput = document.getElementById('tenancies-move_out');

        if (!moveInInput || !termMonthsInput || !moveOutInput) return;

        // Function to calculate the "Move Out" date
        function parseDateInput(value) {
            const parts = String(value || '').split('-').map(function (part) { return parseInt(part, 10); });
            if (parts.length !== 3 || parts.some(function (part) { return ! part; })) {
                return null;
            }
            return new Date(parts[0], parts[1] - 1, parts[2]);
        }

        function formatDateInput(date) {
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return date.getFullYear() + '-' + month + '-' + day;
        }

        function calculateMoveOutDate() {
            const moveInDate = parseDateInput(moveInInput.value);
            const termMonths = parseInt(termMonthsInput.value, 10) || 0;
            const termDays = termDaysInput ? (parseInt(termDaysInput.value, 10) || 0) : 0;

            if (! moveInDate || (termMonths === 0 && termDays === 0)) {
                return;
            }

            const resultDate = new Date(moveInDate.getTime());
            if (termMonths > 0) {
                resultDate.setMonth(resultDate.getMonth() + termMonths);
                resultDate.setDate(resultDate.getDate() - 1);
            }
            if (termDays > 0) resultDate.setDate(resultDate.getDate() + termDays);

            moveOutInput.value = formatDateInput(resultDate);
        }
        function recalculateTerm() {
            const moveInDate = parseDateInput(moveInInput.value);
            const moveOutDate = parseDateInput(moveOutInput.value);

            if (! moveInDate || ! moveOutDate) return;

            const timeDifference = moveOutDate - moveInDate; // Difference in milliseconds
            const daysDifference = timeDifference / (1000 * 3600 * 24); // Convert to days

            const months = Math.floor(daysDifference / 30); // Approximate months
            const remainingDays = daysDifference % 30; // Remainder days

            // Update the term input fields
            termMonthsInput.value = months;
            if (termDaysInput) {
                termDaysInput.value = remainingDays;
            }
        }

        // Function to validate the "Move Out" date and ensure it's after the "Move In" date
        function validateMoveOutDate() {
            const moveInDate = parseDateInput(moveInInput.value);
            const moveOutDate = parseDateInput(moveOutInput.value);

            if (! moveInDate || ! moveOutDate) return;

            if (moveOutDate <= moveInDate) {
                // Show error message or reset the Move Out date if it's invalid
                alert("Move Out Date must be greater than Move In Date.");
                moveOutInput.value = ''; // Reset Move Out Date
            }
        }

        // Function to ensure that the Term fields cannot have negative values
        function validateTermInputs() {
            const termMonths = parseInt(termMonthsInput.value, 10);
            const termDays = termDaysInput ? parseInt(termDaysInput.value, 10) : 0;

            if (termMonths < 0) termMonthsInput.value = 0;
            if (termDaysInput && termDays < 0) termDaysInput.value = 0;
        }

        // Add event listeners to update the "Move Out" date on input change
        moveInInput.addEventListener('change', () => {
            // Only calculate Move Out if either termMonths or termDays is set
            if (termMonthsInput.value > 0 || (termDaysInput && termDaysInput.value > 0)) {
                calculateMoveOutDate();
            }
        });

        // moveInInput.addEventListener('change', calculateMoveOutDate);
        termMonthsInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            validateTermInputs();
            calculateMoveOutDate();
        });
        if (termDaysInput) {
            termDaysInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                validateTermInputs();
                calculateMoveOutDate();
            });
        }
        moveOutInput.addEventListener('change', () => {
            recalculateTerm();
            validateMoveOutDate();
        });

        if ((parseInt(termMonthsInput.value, 10) || 0) > 0 || (termDaysInput && (parseInt(termDaysInput.value, 10) || 0) > 0)) {
            calculateMoveOutDate();
        }

        $('#depositService').trigger('change');
    }

    initializeForm();
    }

    function startTenancyEdit() {
        var $ = window.jQuery;
        if (! $) {
            return;
        }
        if (typeof $.fn.select2 === 'function') {
            bootTenancyEdit();
            return;
        }
        var existing = document.querySelector('script[data-tenancy-edit-select2]');
        if (existing) {
            existing.addEventListener('load', bootTenancyEdit);
            return;
        }
        var script = document.createElement('script');
        script.src = select2Src;
        script.setAttribute('data-tenancy-edit-select2', '1');
        script.onload = bootTenancyEdit;
        document.head.appendChild(script);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startTenancyEdit);
    } else {
        startTenancyEdit();
    }
})();
</script>
