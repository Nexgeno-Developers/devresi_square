@php
    $lettingCurrentStatus = $property->letting_current_status ?? '';
    $salesCurrentStatus = $property->sales_current_status ?? '';


    function getBadgeClass($status) {
        return match(strtolower($status)) {
            'for sale', 'available' => 'bg-success',
            'on hold', 'under offer', 'let agreed' => 'bg-warning',
            'sold', 'sold stc', 'sold by other', 'exchanged', 'let by other' => 'bg-danger',
            default => 'bg-secondary'
        };
    }
@endphp


@if(!isset($editMode) || !$editMode)

    <!-- Display View Mode -->
    {{-- <div class="accordion_inner">
        <div class="mt-md-4 mt-3"> --}}
            <div class="accordion_inner_heading mb-2">Status </div>

            <div class="row mb-3">
                <div class="col-12">
                    <div class="left_item">Sales Status:</div>
                    <div class="right_item">
                        <span class="badge {{ getBadgeClass($salesCurrentStatus) }}">{{ $salesCurrentStatus }}</span>
                    </div>
                </div>
            </div>
            
            @if(isset($property) && ($property->property_type == 'lettings' || $property->property_type == 'both'))
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="left_item">Letting Status:</div>
                        <div class="right_item">
                            <span class="badge {{ getBadgeClass($lettingCurrentStatus) }}">{{ $lettingCurrentStatus }}</span>
                        </div>
                    </div>
                </div>
            @endif
            
            


        {{-- </div>
    </div> --}}
    
    
@else
    <form id="propertyStatusForm">
        @csrf
        <input type="hidden" name="property_id" value="{{ $property->id }}">
        <input type="hidden" name="form_type" value="property_status">

        <div class="row">
            <div class="col-12">
                @if(isset($property) && ($property->property_type == 'sales' || $property->property_type == 'both'))
                    <div class="form-group">
                        <label for="sales_current_status">Sales Status</label>
                        <select name="sales_current_status" id="sales_current_status" class="form-control" required>
                            <option value="" disabled {{ (isset($property) && $property->sales_current_status == '') ? 'selected' : ''  }}>Select a Status</option>
                            <option value="for sale" {{ (isset($property) && $property->sales_current_status == 'for sale') ? 'selected' : '' }}>For Sale</option>
                            <option value="on hold" {{ (isset($property) && $property->sales_current_status == 'on hold') ? 'selected' : '' }}>On Hold</option>
                            <option value="under offer" {{ (isset($property) && $property->sales_current_status == 'under offer') ? 'selected' : '' }}>Under Offer</option>
                            <option value="sold" {{ (isset($property) && $property->sales_current_status == 'sold') ? 'selected' : '' }}>Sold</option>
                            <option value="sold STC" {{ (isset($property) && $property->sales_current_status == 'sold STC') ? 'selected' : '' }}>Sold STC</option>
                            <option value="sold by other" {{ (isset($property) && $property->sales_current_status == 'sold by other') ? 'selected' : '' }}>Sold By Other</option>
                            <option value="exchanged" {{ (isset($property) && $property->sales_current_status == 'exchanged') ? 'selected' : '' }}>Exchanged</option>
                            <option value="available" {{ (isset($property) && $property->sales_current_status == 'available') ? 'selected' : '' }}>Available</option>
                            <option value="let agreed" {{ (isset($property) && $property->sales_current_status == 'let agreed') ? 'selected' : '' }}>Let Agreed</option>
                        </select>
                        @error('sales_current_status')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                @if(isset($property) && ($property->property_type == 'lettings' || $property->property_type == 'both'))
                    <div class="form-group">
                        <label for="letting_current_status">Letting Status</label>
                        <select name="letting_current_status" id="letting_current_status" class="form-control" required>
                            <option value="" disabled {{ (isset($property) && $property->letting_current_status == '') ? 'selected' : ''  }}>Select a Status</option>
                            <option value="not available" {{ (isset($property) && $property->letting_current_status == 'not available') ? 'selected' : '' }}>Not Available</option>
                            <option value="available" {{ (isset($property) && $property->letting_current_status == 'available') ? 'selected' : '' }}>Available</option>
                            <option value="let agreed" {{ (isset($property) && $property->letting_current_status == 'let agreed') ? 'selected' : '' }}>Let Agreed</option>
                            <option value="let by other" {{ (isset($property) && $property->letting_current_status == 'let by other') ? 'selected' : '' }}>Let By Other</option>
                        </select>
                        @error('letting_current_status')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

            </div>
        </div>
        

        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>
@endif