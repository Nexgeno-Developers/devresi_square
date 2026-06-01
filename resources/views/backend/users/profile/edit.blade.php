@extends('backend.layout.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Edit User Profile</h2>
                </div>
                <form action="{{ route('admin.users.profile.update') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    @csrf
                    <div class="card-body">
                        <div class="row g-3">
                          <div class="col-md-6">
                                <label for="profile_picture" class="form-label">Profile Picture</label>
                                
                                <div class="d-flex align-items-center gap-4">
                                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">

                                    @if ($user->profile_picture)
                                        <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="Profile Picture" class="img-thumbnail rounded-circle profile-img-small" />
                                    @else
                                        <div class="default-profile-icon-small bg-secondary rounded-circle d-flex justify-content-center align-items-center">
                                            <i class="fa-solid fa-user text-white"></i>
                                        </div>
                                    @endif
                                </div>
                                {{-- Checkbox to remove profile picture --}}
                                @if ($user->profile_picture)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_profile_picture" id="remove_profile_picture" value="1">
                                        <label class="form-check-label" for="remove_profile_picture">
                                            Remove current profile picture
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="title" class="form-label">Title</label>
                                <select class="form-select" id="title" name="title" required>
                                    <option value="">Select Title</option>
                                    <option value="Mr" {{ old('title', $user->title) == 'Mr' ? 'selected' : '' }}>Mr</option>
                                    <option value="Miss" {{ old('title', $user->title) == 'Miss' ? 'selected' : '' }}>Miss</option>
                                    <option value="Mrs" {{ old('title', $user->title) == 'Mrs' ? 'selected' : '' }}>Mrs</option>
                                    {{-- <option value="Dr" {{ old('title', $user->title) == 'Dr' ? 'selected' : '' }}>Dr</option> --}}
                                    <!-- Add more if needed -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                                <div class="invalid-feedback">Please enter your first name.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}">
                                <div class="invalid-feedback">Please enter your middle name.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                <div class="invalid-feedback">Please enter your last name.</div>
                            </div>
                            {{-- <div class="col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                <div class="invalid-feedback">Please enter your name.</div>
                            </div> --}}
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                <div class="invalid-feedback">Please enter a valid email.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
                                <div class="invalid-feedback">Please enter a valid phone number.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="address_line_1" class="form-label">Address Line 1</label>
                                <input type="text" class="form-control" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $user->address_line_1) }}" required>
                                <div class="invalid-feedback">Please enter your address line 1.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="address_line_2" class="form-label">Address Line 2</label>
                                <input type="text" class="form-control" id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $user->address_line_2) }}">
                                <div class="invalid-feedback">Please enter your address line 2.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="country_id" class="form-label">Country</label>
                                <select class="form-control select2" id="country_id" name="country_id" required>
                                    <option value="">Select a country</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}" 
                                            {{ old('country_id', $user->country_id) == $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Please select your country.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $user->city) }}" required>
                                <div class="invalid-feedback">Please enter your city.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="postcode" class="form-label">Postcode</label>
                                <input type="text" class="form-control" id="postcode" name="postcode" value="{{ old('postcode', $user->postcode) }}" required>
                                <div class="invalid-feedback">Please enter your postcode.</div>
                            </div>
                            {{-- <div class="col-md-6">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category_id" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $user->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div> --}}
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4">Update Profile</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card shadow-sm mt-5">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">Change Password</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.profile.password') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                                <div class="invalid-feedback">Please enter your current password.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" minlength="6" class="form-control" id="new_password" name="new_password" required autocomplete="new-password">
                                <div class="invalid-feedback">Please enter a new password.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                                <input type="password" minlength="6" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required autocomplete="new-password">
                                <div class="invalid-feedback">Passwords do not match.</div>
                            </div>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-warning px-4">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>


<script>
(() => {
    'use strict';

    // Bootstrap 5 form validation
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

    // Instant password match validation
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
    // Initialize Select2 for country selection
    initSelect2('.select2');
</script>
@endsection