<!-- resources/views/backend/users/user_form/step1.blade.php -->
@php $currentStep = 1 ; @endphp
<div class="container-fluid mt-4 quick_add_form">
    <div class="row">
        <div class="col-md-6 col-12 left_col">
            <div class="left_content_wrapper">
                <div class="left_title">
                    What is<br /> <span class="secondary-color">Category </span>for</br>this user?
                </div>
                {{-- <div class="left_title">
                    What is<br /> <span class="secondary-color">Category </span>for</br>this user?
                </div> --}}
            </div>
        </div>
        <div class="col-md-6 col-12 right_col">
            <form id="user-form-step-{{$currentStep}}" method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                <input type="hidden" id="user_id" class="user_id" name="user_id" value="{{ (isset($user) ? $user->id : '') }}">
                <div class="right_content_wrapper w-100">
                    <div class="row">
                        {{-- <div class="form-group col-12">
                            <select name="category_id" class="form-select" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ $user->category_id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div> --}}
                        {{-- <div class="form-group col-12">
                            <select name="role_id" class="form-select" required>
                                <option value="">Select Role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @if(isset($user) && $user->roles->pluck('id')->contains($role->id)) selected @endif >{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div> --}}
                        <div class="form-group col-12">
                            <select name="role_ids[]" class="form-select select2" multiple required>
                                @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @if(isset($user) && $user->roles->pluck('id')->contains($role->id)) selected @endif >
                                    {{ $role->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('role_ids')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                            @error('role_ids.*')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="footer_btn">
                        <div class="row mt-lg-4">
                            <div class="col-md-6 col-12">
                                <button type="button" class="btn btn_secondary btn-sm next-step mt-lg-4 w-100" data-next-step="{{$currentStep+1}}" data-current-step="{{$currentStep}}">Next</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
