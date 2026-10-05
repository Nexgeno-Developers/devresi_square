@extends('backend.layout.app')

@section('content')
@php
    $profileEmails = old('emails', array_values(array_unique(array_filter(array_merge([$user->email], $user->details?->emails ?? [])))) ?: ['']);
    $profilePhones = old('phones', array_values(array_unique(array_filter(array_merge([$user->phone], $user->details?->phones ?? [])))) ?: ['']);
    $primaryEmail = old('primary_email', $user->details?->primary_email ?: $user->email);
    $primaryPhone = old('primary_phone', $user->details?->primary_phone ?: $user->phone);
    $displayName = trim(implode(' ', array_filter([$user->first_name, $user->last_name]))) ?: ($user->name ?: 'Your profile');
@endphp

<div class="container-fluid lw-page">
    <div class="lw-hero">
        <h4>Profile</h4>
        <p>{{ $displayName }}</p>
    </div>

    <form action="{{ route('admin.users.profile.update') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        @csrf

        <div class="card lw-card mb-3">
            <div class="card-body">
                <div class="lw-section-title mt-0">You</div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    @if ($user->profile_picture)
                        <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="" class="lw-profile-photo">
                    @else
                        <div class="lw-profile-fallback" aria-hidden="true">
                            <i class="bi bi-person"></i>
                        </div>
                    @endif
                    <div class="flex-grow-1">
                        <label for="profile_picture" class="form-label">Photo</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                        @if ($user->profile_picture)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_profile_picture" id="remove_profile_picture" value="1">
                                <label class="form-check-label" for="remove_profile_picture">Remove current photo</label>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="title" class="form-label">Title</label>
                        <select class="form-select" id="title" name="title" required>
                            <option value="">Select title</option>
                            <option value="Mr" {{ old('title', $user->title) == 'Mr' ? 'selected' : '' }}>Mr</option>
                            <option value="Miss" {{ old('title', $user->title) == 'Miss' ? 'selected' : '' }}>Miss</option>
                            <option value="Mrs" {{ old('title', $user->title) == 'Mrs' ? 'selected' : '' }}>Mrs</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="first_name" class="form-label">First name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                        <div class="invalid-feedback">Please enter your first name.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="last_name" class="form-label">Last name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                        <div class="invalid-feedback">Please enter your last name.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="middle_name" class="form-label">Middle name</label>
                        <input type="text" class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card lw-card mb-3">
            <div class="card-body">
                <div class="lw-section-title mt-0">Contact</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Emails</label>
                        <div id="profile-emails">
                            @foreach($profileEmails as $email)
                                <div class="input-group mb-2 profile-contact-row">
                                    <input type="email" class="form-control profile-email-input" name="emails[]" value="{{ $email }}" required>
                                    @unless($loop->first)
                                        <button type="button" class="btn btn-outline-danger remove-row">Remove</button>
                                    @endunless
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="primary_email" id="primary_email" value="{{ $primaryEmail }}">
                        <div class="form-text">Primary: <span id="primary_email_label">{{ $primaryEmail ?: 'Not selected' }}</span></div>
                        @error('primary_email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <button type="button" class="btn btn-sm lw-btn-secondary add-profile-email">Add email</button>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone numbers</label>
                        <div id="profile-phones">
                            @foreach($profilePhones as $phone)
                                <div class="input-group mb-2 profile-contact-row">
                                    <input type="text" class="form-control profile-phone-input" name="phones[]" value="{{ $phone }}" required>
                                    @unless($loop->first)
                                        <button type="button" class="btn btn-outline-danger remove-row">Remove</button>
                                    @endunless
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="primary_phone" id="primary_phone" value="{{ $primaryPhone }}">
                        <div class="form-text">Primary: <span id="primary_phone_label">{{ $primaryPhone ?: 'Not selected' }}</span></div>
                        @error('primary_phone')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <button type="button" class="btn btn-sm lw-btn-secondary add-profile-phone">Add phone</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card lw-card mb-3">
            <div class="card-body">
                <div class="lw-section-title mt-0">Address</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="address_line_1" class="form-label">Address line 1</label>
                        <input type="text" class="form-control" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $user->address_line_1) }}" required>
                        <div class="invalid-feedback">Please enter your address line 1.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="address_line_2" class="form-label">Address line 2</label>
                        <input type="text" class="form-control" id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $user->address_line_2) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $user->city) }}" required>
                        <div class="invalid-feedback">Please enter your city.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="postcode" class="form-label">Postcode</label>
                        <input type="text" class="form-control" id="postcode" name="postcode" value="{{ old('postcode', $user->postcode) }}" required>
                        <div class="invalid-feedback">Please enter your postcode.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="country_id" class="form-label">Country</label>
                        <select class="form-control select2" id="country_id" name="country_id" required>
                            <option value="">Select a country</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" {{ old('country_id', $user->country_id) == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select your country.</div>
                    </div>
                </div>
            </div>
        </div>

        @if($company)
            @php
                $socialFields = ['facebook', 'instagram', 'linkedin', 'twitter', 'youtube', 'tiktok'];
                $companyEmails = old('company.emails', $company->emails ?: ['']);
                $companyPhones = old('company.phones', $company->phones ?: ['']);
                $canUseInvoiceBranding = $canUseInvoiceBranding ?? true;
            @endphp

            <div class="card lw-card mb-3">
                <div class="card-body">
                    <div class="lw-section-title mt-0">Company</div>
                    <p class="text-muted small">Fill this in if you trade as a company.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="company_name">Company name</label>
                            <input type="text" class="form-control" id="company_name" name="company[name]" value="{{ old('company.name', $company->name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="registration_number">Registration number</label>
                            <input type="text" class="form-control" id="registration_number" name="company[registration_number]" value="{{ old('company.registration_number', $company->registration_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="registered_address">Registered address</label>
                            <textarea class="form-control" id="registered_address" name="company[registered_address]" rows="3">{{ old('company.registered_address', $company->registered_address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="communication_address">Communication address</label>
                            <textarea class="form-control" id="communication_address" name="company[communication_address]" rows="3">{{ old('company.communication_address', $company->communication_address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="vat_number">VAT number</label>
                            <input type="text" class="form-control" id="vat_number" name="company[vat_number]" value="{{ old('company.vat_number', $company->vat_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="website">Website</label>
                            <input type="url" class="form-control" id="website" name="company[website]" value="{{ old('company.website', $company->website) }}">
                        </div>
                        @if($canUseInvoiceBranding)
                            <div class="col-md-6">
                                <label class="form-label" for="company_logo">Company logo</label>
                                <input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/*">
                                @if($company->logo_path)
                                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Company logo" class="img-thumbnail mt-2" style="max-height: 70px;">
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="company_stamp">Company stamp</label>
                                <input type="file" class="form-control" id="company_stamp" name="company_stamp" accept="image/*">
                                @if($company->stamp_path)
                                    <img src="{{ asset('storage/' . $company->stamp_path) }}" alt="Company stamp" class="img-thumbnail mt-2" style="max-height: 70px;">
                                @endif
                            </div>
                        @else
                            <div class="col-12">
                                <div class="alert alert-lw mb-0">Invoice branding is not part of your plan.</div>
                            </div>
                        @endif
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Company emails</label>
                            <div id="company-emails">
                                @foreach($companyEmails as $email)
                                    <div class="input-group mb-2">
                                        <input type="email" class="form-control" name="company[emails][]" value="{{ $email }}">
                                        @unless($loop->first)
                                            <button type="button" class="btn btn-outline-danger remove-row">Remove</button>
                                        @endunless
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm lw-btn-secondary add-company-email">Add email</button>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company phones</label>
                            <div id="company-phones">
                                @foreach($companyPhones as $phone)
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="company[phones][]" value="{{ $phone }}">
                                        @unless($loop->first)
                                            <button type="button" class="btn btn-outline-danger remove-row">Remove</button>
                                        @endunless
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm lw-btn-secondary add-company-phone">Add phone</button>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        @foreach($socialFields as $field)
                            <div class="col-md-4">
                                <label class="form-label" for="company_social_{{ $field }}">{{ Str::headline($field) }}</label>
                                <input type="text" class="form-control" id="company_social_{{ $field }}" name="company[social_media][{{ $field }}]" value="{{ old('company.social_media.' . $field, $company->social_media[$field] ?? '') }}">
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3">
                        <label class="form-label d-block">Services</label>
                        @foreach(['lettings' => 'Lettings', 'sales' => 'Sales', 'property_management' => 'Property Management'] as $serviceKey => $serviceLabel)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="service_{{ $serviceKey }}" name="company[services][]" value="{{ $serviceKey }}" @checked(in_array($serviceKey, old('company.services', $company->services ?: [])))>
                                <label class="form-check-label" for="service_{{ $serviceKey }}">{{ $serviceLabel }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <button type="submit" class="btn lw-btn-primary mb-4">Save profile</button>
    </form>

    <div class="modal fade" id="primaryContactModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Choose the main contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Main email</label>
                        <div id="primary-email-options" class="d-grid gap-2"></div>
                    </div>
                    <div>
                        <label class="form-label">Main phone</label>
                        <div id="primary-phone-options" class="d-grid gap-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn lw-btn-primary" id="save-primary-contact">Use these</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Password</div>
            <form action="{{ route('admin.users.profile.password') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="current_password" class="form-label">Current password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                        <div class="invalid-feedback">Please enter your current password.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="new_password" class="form-label">New password</label>
                        <input type="password" minlength="6" class="form-control" id="new_password" name="new_password" required autocomplete="new-password">
                        <div class="invalid-feedback">Please enter a new password.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="new_password_confirmation" class="form-label">Confirm new password</label>
                        <input type="password" minlength="6" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required autocomplete="new-password">
                        <div class="invalid-feedback">Passwords do not match.</div>
                    </div>
                </div>
                <button type="submit" class="btn lw-btn-secondary mt-3">Update password</button>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    'use strict';

    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('new_password_confirmation');

    function validatePasswordMatch() {
        if (confirmPassword.value.length > 0) {
            if (newPassword.value === confirmPassword.value) {
                confirmPassword.classList.remove('is-invalid');
                confirmPassword.classList.add('is-valid');
            } else {
                confirmPassword.classList.remove('is-valid');
                confirmPassword.classList.add('is-invalid');
            }
        } else {
            confirmPassword.classList.remove('is-valid', 'is-invalid');
        }
    }

    newPassword.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
})();
</script>
@endsection

@include('backend.partials.assets.select2');
@section('page.scripts')
<script>
    initSelect2('.select2');

    $(document).on('click', '.remove-row', function () {
        $(this).closest('.input-group').remove();
    });

    $(document).on('click', '.add-company-email', function () {
        $('#company-emails').append('<div class="input-group mb-2"><input type="email" class="form-control" name="company[emails][]"><button type="button" class="btn btn-outline-danger remove-row">Remove</button></div>');
    });

    $(document).on('click', '.add-company-phone', function () {
        $('#company-phones').append('<div class="input-group mb-2"><input type="text" class="form-control" name="company[phones][]"><button type="button" class="btn btn-outline-danger remove-row">Remove</button></div>');
    });

    $(document).on('click', '.add-profile-email', function () {
        $('#profile-emails').append('<div class="input-group mb-2 profile-contact-row"><input type="email" class="form-control profile-email-input" name="emails[]" required><button type="button" class="btn btn-outline-danger remove-row">Remove</button></div>');
    });

    $(document).on('click', '.add-profile-phone', function () {
        $('#profile-phones').append('<div class="input-group mb-2 profile-contact-row"><input type="text" class="form-control profile-phone-input" name="phones[]" required><button type="button" class="btn btn-outline-danger remove-row">Remove</button></div>');
    });

    const profileForm = $('form[action="{{ route('admin.users.profile.update') }}"]');
    let primaryModalApproved = false;

    function contactValues(selector) {
        return $(selector).map(function () {
            return $(this).val().trim();
        }).get().filter(Boolean).filter((value, index, values) => values.indexOf(value) === index);
    }

    function renderPrimaryOptions(values, name, target, current) {
        const wrapper = $(target).empty();
        values.forEach((value, index) => {
            const checked = current === value || (!current && index === 0) ? 'checked' : '';
            wrapper.append(`<label class="border rounded p-2 mb-0"><input class="form-check-input me-2" type="radio" name="${name}" value="${value}" ${checked}>${value}</label>`);
        });
    }

    profileForm.on('submit', function (event) {
        if (primaryModalApproved) {
            primaryModalApproved = false;
            return true;
        }

        const emails = contactValues('.profile-email-input');
        const phones = contactValues('.profile-phone-input');

        if (emails.length > 1 || phones.length > 1) {
            event.preventDefault();
            renderPrimaryOptions(emails, 'modal_primary_email', '#primary-email-options', $('#primary_email').val());
            renderPrimaryOptions(phones, 'modal_primary_phone', '#primary-phone-options', $('#primary_phone').val());
            bootstrap.Modal.getOrCreateInstance(document.getElementById('primaryContactModal')).show();
        } else {
            $('#primary_email').val(emails[0] || '');
            $('#primary_phone').val(phones[0] || '');
        }
    });

    $('#save-primary-contact').on('click', function () {
        const email = $('input[name="modal_primary_email"]:checked').val() || '';
        const phone = $('input[name="modal_primary_phone"]:checked').val() || '';
        $('#primary_email').val(email);
        $('#primary_phone').val(phone);
        $('#primary_email_label').text(email || 'Not selected');
        $('#primary_phone_label').text(phone || 'Not selected');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('primaryContactModal')).hide();
        primaryModalApproved = true;
        profileForm.trigger('submit');
    });
</script>
@endsection
