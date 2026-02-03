@php $currentStep = 6 ; @endphp
<!-- resources/views/backend/properties/quick_form_components/step6.blade.php -->
<div class="container-fluid mt-4 quick_add_property">
    <div class="row">
        <div class="col-md-6 col-12 left_col">
            <div class="left_content_wrapper">
                <div class="left_content_img">
                    <i class="bi bi-cup-hot-fill"></i>
                </div>
                <div class="left_title">
                    Is your property <br/><span class="secondary-color">Furnished</span>?
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
                    <div class="form-group">
                        <div class="radio_bts_square_icon rs_furnishing">
                            <input type="radio" class="furnish-radio" name="frunishing_type"
                                id="frunishing_type_furnished" value="furnished" {{ (isset($property) && $property->frunishing_type == 'furnished') ? 'checked' : '' }} required>
                            <label for="frunishing_type_furnished">
                                {{-- <img src="{{ asset('asset/images/svg/apartment_600.svg') }}" alt="apartment"> --}}
                                Furnished
                            </label>
                            <input type="radio" class="furnish-radio" name="frunishing_type"
                                id="frunishing_type_unfurnished" value="unfurnished" {{ (isset($property) && $property->frunishing_type == 'unfurnished') ? 'checked' : '' }} required>
                            <label for="frunishing_type_unfurnished">
                                {{-- <img src="{{ asset('asset/images/svg/flat_600.svg') }}" alt="flat"> --}}
                                Unfurnished
                            </label>
                            <input type="radio" class="furnish-radio" name="frunishing_type"
                                id="frunishing_type_semi_furnished" value="semi_furnished" {{ (isset($property) && $property->frunishing_type == 'semi_furnished') ? 'checked' : '' }} required>
                            <label for="frunishing_type_semi_furnished">
                                {{-- <img src="{{ asset('asset/images/svg/bungalow_600.svg') }}" alt="bungalow"> --}}
                                Semi Furnished
                            </label>
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