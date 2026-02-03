<!-- resources/views/backend/properties/quick_form_components/step3.blade.php -->
@php $currentStep = 2 ; @endphp
<div class="container-fluid mt-4 quick_add_user">
    <div class="row user_type_wrapper">
        <div class="col-md-6 col-12 left_col">
            <div class="left_content_wrapper">
                <div class="left_title">
                    Write<br /> <span class="secondary-color">Personal Information</span><br /> for this user
                </div>
            </div>
        </div>
        <div class="col-md-6 col-12 right_col">
            <form id="user-form-step-{{ $currentStep }}" method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <!-- Hidden field for user ID with isset check -->
                <input type="hidden" id="user_id" class="user_id" name="user_id" value="{{ isset($user) ? $user->id : '' }}">
                <div class="steps_wrapper">
                    <div class="row">
                        <div class="col-md-10 col-12">
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="first_name">First Name</label>
                                        <input required type="text" name="first_name" id="first_name"
                                            class="form-control"
                                            value="{{ isset($user) && $user->first_name ? $user->first_name : '' }}">
                                        @error('first_name')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="last_name">Last Name</label>
                                        <input required type="text" name="last_name" id="last_name"
                                            class="form-control"
                                            value="{{ isset($user) && $user->last_name ? $user->last_name : '' }}">
                                        @error('last_name')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="phone">Phone</label>
                                        <input required type="number" pattern="[0-9]*" inputmode="numeric"
                                            name="phone" id="phone" class="form-control"
                                            value="{{ isset($user) && $user->phone ? $user->phone : '' }}">
                                        @error('phone')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input required type="email" name="email" id="email"
                                            class="form-control"
                                            value="{{ isset($user) && $user->email ? $user->email : '' }}">
                                        @error('email')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="address_line_1">Address Line 1</label>
                                <input required type="text" name="address_line_1" id="address_line_1"
                                    class="form-control"
                                    value="{{ isset($user) && $user->address_line_1 ? $user->address_line_1 : '' }}">
                                @error('address_line_1')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-8 col-12">
                                    <div class="form-group">
                                        <label for="address_line_2">Address Line 2</label>
                                        <input type="text" name="address_line_2" id="address_line_2"
                                            class="form-control"
                                            value="{{ isset($user) && $user->address_line_2 ? $user->address_line_2 : '' }}">
                                        @error('address_line_2')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4 col-12">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input required type="text" name="city" id="city"
                                            class="form-control"
                                            value="{{ isset($user) && $user->city ? $user->city : '' }}">
                                        @error('city')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-lg-8 col-12">
                                    <label for="country_id">Country</label>
                                    <select name="country_id" id="country_id" class="form-control select2" required>
                                        <option value="">-- Select Country --</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}"
                                                {{ old('country_id', $user->country_id ?? '') == $country->id ? 'selected' : '' }}>
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('country_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                {{-- <div class="form-group col-lg-8 col-12">
                                    <label for="country">Country</label>
                                    <input required type="text" name="country" id="country" class="form-control"
                                        value="{{ isset($user) && $user->country ? $user->country : '' }}">
                                    @error('country')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div> --}}
                                <div class="form-group col-lg-4 col-12">
                                    <label for="postcode">Postcode</label>
                                    <input required type="text" name="postcode" id="postcode"
                                        class="form-control"
                                        value="{{ isset($user) && $user->postcode ? $user->postcode : '' }}">
                                    @error('postcode')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="footer_btn">
                                <div class="row mt-lg-4">
                                    <div class="col-6">
                                        <button type="button"
                                            class="btn btn_outline_secondary previous-step mt-2 w-100"
                                            data-previous-step="{{ $currentStep - 1 }}"
                                            data-current-step="{{ $currentStep }}">Previous</button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn_secondary btn-sm next-step mt-2 w-100"
                                            data-next-step="{{ $currentStep + 1 }}"
                                            data-current-step="{{ $currentStep }}">Next</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </form>
        </div>
    </div>
</div>
