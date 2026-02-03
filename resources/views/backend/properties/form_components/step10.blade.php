@php $currentStep = 10 ; @endphp
<!-- resources/views/backend/properties/form_components/step10.blade.php -->
<form id="property-form-step-{{ $currentStep }}" class="rs_steps" method="POST" action="{{ route('admin.properties.store') }}">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" id="property_id" class="property_id" name="property_id" value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">
    <input type="hidden" name="step" value="{{ $currentStep }}">
    <label class="main_title">Responsibility</label>
    <div class="row h_100_vh">
        <div class="col-12">
            <div class="steps_wrapper property-form-data-attribute" data-step-name="Responsibility" data-step-number="{{ $currentStep }}" data-step-title="Responsibility">
                <div id="commission-wrapper">
                    @if($PropertyResponsibility->isNotEmpty())
                        @foreach($PropertyResponsibility as $commission)
                        <div class="row commission-row mt-3 mt-md-5">
                            <input type="hidden" name="PropertyResponsibility_id[]" value="{{ $commission->id }}">
                            <div class="col-1">
                                <div class="fs-4 row-number">
                                    {{ $loop->iteration }} <!-- Displaying the row number -->
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label for="user">User</label>
                                            <select name="user_id[]" class="form-control" required>
                                                <option value="" disabled>Select a user</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}" {{ $commission->user_id == $user->id ? 'selected' : '' }}>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label for="designation">Designation</label>
                                            <select name="designation_id[]" class="form-control" required>
                                                <option value="" disabled>Select a designation</option>
                                                @foreach($designations as $designation)
                                                    <option value="{{ $designation->id }}" {{ $commission->designation_id == $designation->id ? 'selected' : '' }}>
                                                        {{ $designation->title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label for="branch">Branch</label>
                                            <select name="branch_id[]" class="form-control" required>
                                                <option value="" disabled>Select a branch</option>
                                                @foreach($branches as $branch)
                                                    <option value="{{ $branch->id }}" {{ $commission->branch_id == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="commission_percentage">Commission (%)</label>
                                            <input type="text" name="commission_percentage[]" class="form-control" value="{{ $commission->commission_percentage }}" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="commission_amount">Commission (£)</label>
                                            <div class="price_input_wrapper">
                                                <div class="pound_sign">£</div>
                                                <input type="text" name="commission_amount[]" class="form-control" value="{{ $commission->commission_amount }}" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3 d-flex justify-content-center align-items-center gap-3">
                                <button type="button" class="btn btn_secondary add-btn-commission">Add</button>
                                <button type="button" class="btn btn-danger remove-btn-commission" style="display:none;">Remove</button>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="row commission-row mt-3 mt-md-5">
                            <input type="hidden" name="PropertyResponsibility_id[]" value="">
                            <div class="col-1">
                                <div class="fs-4 row-number">1</div>
                            </div>
                            <div class="col-8">
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label for="user">User</label>
                                            <select name="user_id[]" class="form-control" required>
                                                <option value="" disabled selected>Select a user</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label for="designation">Designation</label>
                                            <select name="designation_id[]" class="form-control" required>
                                                <option value="" disabled selected>Select a designation</option>
                                                @foreach($designations as $designation)
                                                    <option value="{{ $designation->id }}">{{ $designation->title }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label for="branch">Branch</label>
                                            <select name="branch_id[]" class="form-control" required>
                                                <option value="" disabled selected>Select a branch</option>
                                                @foreach($branches as $branch)
                                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="commission_percentage">Commission (%)</label>
                                            <input type="text" name="commission_percentage[]" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="commission_amount">Commission (£)</label>
                                            <div class="price_input_wrapper">
                                                <div class="pound_sign">£</div>
                                                <input type="text" name="commission_amount[]" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3 d-flex justify-content-center align-items-center gap-3">
                                <button type="button" class="btn btn_secondary add-btn-commission">Add</button>
                                <button type="button" class="btn btn-danger remove-btn-commission" style="display:none;">Remove</button>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="footer_btn">
                    <div class="row ">
                        <div class="col-6">
                            <button type="button" class="btn btn_outline_secondary w-100 previous-step" data-previous-step="{{ $currentStep - 1 }}" data-current-step="{{ $currentStep }}">Previous</button>
                        </div>
                        <div class="col-6">
                            <button type="submit" class="btn btn_secondary w-100 last-step-submit" data-current-step="{{ $currentStep }}">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
