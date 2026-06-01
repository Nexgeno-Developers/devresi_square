@php
    $details = $user->details ?? '';

    $rightToRent = is_object($details) && isset($details->right_to_rent_check)
    ? booleanToYesNo($details->right_to_rent_check)
    : '';
@endphp
@if(!isset($editMode) || !$editMode)

<!-- Display View Mode -->

    <div class="card-header">
        <strong>Compliance Information</strong>
    </div>
    <div class="card-body row g-3">
        {{-- Nationality --}}
        <div class="col-md-6">
            <strong>Nationality:</strong>
            <p>{{ $details->nationality->name ?? 'N/A' }}</p>
        </div>

        {{-- Visa Expiry Date --}}
        <div class="col-md-6">
            <strong>Visa Expiry Date:</strong>
            <p>
                {{ (is_object($details) && !empty($details->visa_expiry)) ? formatDate($details->visa_expiry) : 'N/A' }}
            </p>
        </div>

        {{-- Passport No --}}
        <div class="col-md-6">
            <strong>Passport No.:</strong>
            <p>{{ $details->passport_no ?? 'N/A' }}</p>
        </div>

        {{-- NRL Number --}}
        <div class="col-md-6">
            <strong>NRL Number:</strong>
            <p>{{ $details->nrl_number ?? 'N/A' }}</p>
        </div>

        {{-- Right to Rent Check --}}
        <div class="col-md-12">
            <strong>Right to Rent Check completed in person?</strong>
            @if($rightToRent === 'Yes')
            <p>{{ $rightToRent }}</p>
               <div class="mt-2">
                    <strong>Checked By (Internal Staff):</strong>
                    <p>{{ $details->user->name ?? 'N/A' }}</p>

                    <strong>Checked By (External Person):</strong>
                    <p>{{ $details->checked_by_external ?? 'N/A' }}</p>
                </div>
            @else
            <p>No</p>
            @endif
        </div>
    </div>


@else
    <form id="userComplianceForm">
        @csrf
        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <input type="hidden" name="form_type" value="compliance">
        <div class="card">
            <div class="card-header">
                <strong>Compliance Information</strong>
            </div>
            <div class="card-body row g-3">
                {{-- Nationality --}}
                <div class="col-md-6">
                <label for="nationality_id" class="form-label"><strong>Nationality</strong></label>
                <select name="nationality_id" id="nationality_id" class="form-select">
                    <option value="">-- Select Nationality --</option>
                    @foreach($nationalities as $id => $name)
                    <option value="{{ $id }}"
                        {{ old('nationality_id', $details->nationality_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                    @endforeach
                </select>
                </div>

                {{-- Visa Expiry Date --}}
                <div class="col-md-6">
                <label for="visa_expiry" class="form-label"><strong>Visa Expiry Date</strong></label>
                <input
                    type="date"
                    class="form-control"
                    name="visa_expiry"
                    id="visa_expiry"
                    value="{{ old('visa_expiry', $details->visa_expiry ?? '') }}"
                >
                </div>

                {{-- Passport No --}}
                <div class="col-md-6">
                <label for="passport_no" class="form-label"><strong>Passport No.</strong></label>
                <input
                    type="text"
                    class="form-control"
                    name="passport_no"
                    id="passport_no"
                    value="{{ old('passport_no', $details->passport_no ?? '') }}"
                >
                </div>

                {{-- NRL Number --}}
                <div class="col-md-6">
                <label for="nrl_number" class="form-label"><strong>NRL Number</strong></label>
                <input
                    type="text"
                    class="form-control"
                    name="nrl_number"
                    id="nrl_number"
                    value="{{ old('nrl_number', $details->nrl_number ?? '') }}"
                >
                </div>

                {{-- Right to Rent Check --}}
                <div class="col-12">
                <div class="form-check mb-2">
                    <input
                    class="form-check-input"
                    type="checkbox"
                    id="right_to_rent_check"
                    name="right_to_rent_check"
                    value="1"
                    {{ old('right_to_rent_check', $details->right_to_rent_check ?? false) ? 'checked' : '' }}
                    >
                    <label class="form-check-label" for="right_to_rent_check">
                    Right to Rent Check completed in person?
                    </label>
                </div>

                <div id="rent-check-person-details" class="row g-3 mt-2" style="display: none;">
                    {{-- Internal staff --}}
                    <div class="col-md-6">
                    <label for="checked_by_user" class="form-label"><strong>Select Staff</strong></label>
                    <select name="checked_by_user" id="checked_by_user" class="form-select">
                        <option value="">-- Select User --</option>
                        @foreach($users as $id => $name)
                        <option value="{{ $id }}"
                            {{ old('checked_by_user', $details->checked_by_user ?? '') == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                        @endforeach
                    </select>
                    </div>

                    {{-- External person --}}
                    <div class="col-md-6">
                    <label for="checked_by_external" class="form-label"><strong>Or External Person</strong></label>
                    <input
                        type="text"
                        class="form-control"
                        name="checked_by_external"
                        id="checked_by_external"
                        placeholder="Name of external checker"
                        value="{{ old('checked_by_external', $details->checked_by_external ?? '') }}"
                    >
                    </div>
                </div>

                <div
                    id="rent-check-message"
                    class="alert alert-info mt-3"
                    style="display: none;"
                >
                    Thank you for confirming the Right to Rent Check. You have personally checked and completed the formalities of this applicant.
                </div>
                </div>
            </div>
            </div>


        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>
@endif

