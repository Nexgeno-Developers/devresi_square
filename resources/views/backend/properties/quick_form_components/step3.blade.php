<!-- resources/views/backend/properties/quick_form_components/step4.blade.php -->
@php $currentStep = 3 ; @endphp
<div class="container-fluid mt-4 quick_add_property">
    <div class="row">
        <div class="col-md-6 col-12 left_col">
            <div class="left_content_wrapper">
                <div class="left_content_img">
                    <img src="{{ asset('asset/images/svg/icons/bed.svg') }}" alt="Property Address">
                </div>
                <div class="left_title">
                    How many <span class="secondary-color">Bedrooms</span><br /> do you have?
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
                    <div class="qap_bedrooms">
                        <div class="radio_bts_square">
                            <input required type="radio" class="bedroom-radio" name="bedroom" id="bedrooms1" value="1"
                                {{ (isset($property) && $property->bedroom == '1') ? 'checked' : '' }} />
                            <label for="bedrooms1"> 1 </label>
                            <input required type="radio" class="bedroom-radio" name="bedroom" id="bedrooms2" value="2"
                                {{ (isset($property) && $property->bedroom == '2') ? 'checked' : '' }} />
                            <label for="bedrooms2"> 2 </label>
                            <input required type="radio" class="bedroom-radio" name="bedroom" id="bedrooms3" value="3"
                                {{ (isset($property) && $property->bedroom == '3') ? 'checked' : '' }} />
                            <label for="bedrooms3"> 3 </label>
                            <input required type="radio" class="bedroom-radio" name="bedroom" id="bedrooms4" value="4"
                                {{ (isset($property) && $property->bedroom == '4') ? 'checked' : '' }} />
                            <label for="bedrooms4"> 4 </label>
                            <input required type="radio" class="bedroom-radio" name="bedroom" id="bedrooms5" value="5"
                                {{ (isset($property) && $property->bedroom == '5') ? 'checked' : '' }} />
                            <label for="bedrooms5"> 5 </label>
                            <input required type="radio" class="bedroom-radio" name="bedroom" id="bedrooms6" value="6+"
                                {{ (isset($property) && $property->bedroom == '6+') ? 'checked' : '' }} />
                            <label for="bedrooms6">6+</label>
                        </div>
                        @error('reception')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="d-flex d-none gap-3">
                    <button type="button" class="btn btn-secondary previous-step mt-2 w-100" data-previous-step="{{$currentStep-1}}"
                        data-current-step="{{$currentStep}}">Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step mt-2 w-100" data-next-step="{{$currentStep+1}}"
                        data-current-step="{{$currentStep}}">Next</button>
                </div>
            </form>
        </div>
    </div>
</div>