@php $currentStep = 8 ; @endphp
<!-- resources/views/backend/properties/quick_form_components/step8.blade.php -->
<div class="container-fluid mt-4 quick_add_property">
    <div class="row">
        <div class="col-md-6 col-12 left_col">
            <div class="left_content_wrapper">
                <div class="left_content_img">
                    <i class="bi bi-currency-pound"></i>
                </div>
                <div class="left_title">
                    Property <span class="secondary-color">Price</span> and <br/>
                    <span class="secondary-color">Availability</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-12 right_col">
            <form id="property-form-step-{{$currentStep}}" method="POST" action="{{ route('admin.properties.quick_store') }}">
                @csrf
                <!-- Hidden field for property ID with isset check -->
                <input type="hidden" id="property_id" class="property_id" name="property_id"
                    value="{{ (isset($property) ? $property->id : '') }}">
                <div data-step-name="Property Address" data-step-number="{{$currentStep}}"></div>
                <div class="right_content_wrapper">
                    <div class="">
                        <div class="rc_title">Price</div>
                        <x-backend.input-comp
                            class="prince_input"
                            inputOpt="input_price" 
                            inputType="number" 
                            rightIcon="Per Month"
                            inputName="price"
                            isLabel={{False}}
                            label="Price" 
                            isDate={{False}}
                         />
                    </div>
                    <div class="">
                        <div class="rc_title">Tenancy Start Date</div>
                        <x-backend.input-comp
                            class="tenancy_date"
                            inputOpt=""
                            inputType="date"
                            rightIcon=""
                            inputName="from_date"
                            isLabel={{False}}
                            label=""
                            isDate={{True}}
                            />
                    </div>
                    <div class="">
                        <div class="rc_title">Tenancy Length</div>
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="form-group">
                                    <label for="months">Months</label>
                                    @php
                                        $months = [];
                                        for ($i = 1; $i <= 36; $i++) {
                                            $months[] = "$i";
                                        }
                                        $selectedMonth = '1'; 
                                    @endphp
                                    <x-backend.dropdown
                                        :options="$months"
                                        :selected="$selectedMonth"
                                        isIcon={{false}}
                                        class=""
                                        />
                                    @error('months')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="form-group">
                                    <label for="days">Days</label>
                                    @php
                                        $days = [];
                                        for ($i = 1; $i <= 30; $i++) {
                                            $days[] = "$i";
                                        }
                                        $selectedDays = '1'; 
                                    @endphp
                                    <x-backend.dropdown
                                        :options="$days"
                                        :selected="$selectedDays"
                                        isIcon={{false}}
                                        class=""
                                        />
                                    @error('days')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="">
                        <div class="">
                            <input required type="radio" class="management-radio" name="management" id="management1"
                            value="management" {{ (isset($property) && $property->management == 'management') ? 'checked' : '' }} />
                            <label for="management1"> Management </label>
                        </div>
                        <div class="">
                            <input required type="radio" class="premium-management-radio" name="management" id="management2"
                                value="premium management" {{ (isset($property) && $property->management == 'premium management') ? 'checked' : '' }} />
                            <label for="management2"> Premium Management </label>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn_secondary margin-top-5 mt-5 w-100 last-step-submit" data-current-step="{{ $currentStep }}">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>