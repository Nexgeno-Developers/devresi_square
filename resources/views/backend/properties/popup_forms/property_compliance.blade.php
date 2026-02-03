@php
    if (isset($property)) {
        $epcRequired = booleanToYesNo($property->epc_required) ?? '';
        $epcRating = $property->epc_rating ?? '';
        $isGas = booleanToYesNo($property->is_gas) ?? '';
        $gas_safe_acknowledged = $property->gas_safe_acknowledged ?? 0;
    } else {
        $epcRequired = '';
        $epcRating = '';
        $isGas = '';
        $gas_safe_acknowledged = 0;
    }
@endphp
@if(!isset($editMode) || !$editMode)
    <!-- Display View Mode -->
    {{-- <strong>Valid EPC</strong><br> --}}
    <div class="d-flex gap-3">
        @if($epcRequired === 'Yes')
        <p><strong>EPC Required:</strong> {{ $epcRequired }}</p>
        <p><strong>EPC Rating:</strong> {{ $epcRating }}</p>
        @else
        <p><strong>EPC Required:</strong> {{ $epcRequired }}</p>
        @endif
        <p><strong>Gas:</strong> {{ $isGas }}</p>
    </div>


    @if ($property->market_on)
        <strong>Market On:</strong><br>
        <p>{{ implode(', ', $property->market_on) }}</p>
    @else
        <p><strong>Market On: </strong>N/A</p>
    @endif
    
@else
    <form id="propertyDetailsForm">
        @csrf
        <input type="hidden" name="property_id" value="{{ $property->id }}">
        <input type="hidden" name="form_type" value="property_compliance">
        <div class="form-group">
            <label>EPC Required</label>
            <div class="radio_bts_square">
                <input type="radio" name="epc_required" id="epc_required1" value="1" {{ (isset($property) && $property->epc_required == '1') ? 'checked' : '' }} required />
                <label for="epc_required1"> Yes </label>
                <input type="radio" name="epc_required" id="epc_required0" value="0" {{ (isset($property) && $property->epc_required == '0') ? 'checked' : '' }} required />
                <label for="epc_required0"> No </label>
            </div>
        </div>
        <div class="form-group" id="epc_rating_container">
            <div class="form-group">
                <label for="epc_rating">EPC Rating</label>
                <select name="epc_rating" id="epc_rating" class="form-control">
                    <option value="A" {{ $epcRating == 'A' ? 'selected' : '' }}>A</option>
                    <option value="B" {{ $epcRating == 'B' ? 'selected' : '' }}>B</option>
                    <option value="C" {{ $epcRating == 'C' ? 'selected' : '' }}>C</option>
                    <option value="D" {{ $epcRating == 'D' ? 'selected' : '' }}>D</option>
                </select>
                @error('epc_rating')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            </div>

        <div class="form-group" id="gas_section">
            <input type="hidden" id="gas_safe_acknowledged" name="gas_safe_acknowledged"
                value="{{ $gas_safe_acknowledged }}">

            <label>Does the property have gas?</label>
            <div class="radio_bts_square">
                <input type="radio" name="is_gas" id="is_gas_yes" value="1" {{ (isset($property) && $property->is_gas == '1') ? 'checked' : '' }} required />
                <label for="is_gas_yes"> Yes </label>
                <input type="radio" name="is_gas" id="is_gas_no" value="0" {{ (isset($property) && $property->is_gas == '0') ? 'checked' : '' }} required />
                <label for="is_gas_no"> No </label>
            </div>            
        </div>

        <div class="form-group">
            <label for="market_on">Market On</label>
            <select name="market_on[]" id="market_on" class="form-control select2" multiple>
                <option value="resisquare" {{ (isset($property) && is_array($property->market_on) && in_array('resisquare', $property->market_on)) || (is_array(old('market_on')) && in_array('resisquare', old('market_on'))) ? 'selected' : '' }}>Resisquare</option>
                <option value="rightmove" {{ (isset($property) && is_array($property->market_on) && in_array('rightmove', $property->market_on)) || (is_array(old('market_on')) && in_array('rightmove', old('market_on'))) ? 'selected' : '' }}>Rightmove</option>
                <option value="zoopla" {{ (isset($property) && is_array($property->market_on) && in_array('zoopla', $property->market_on)) || (is_array(old('market_on')) && in_array('zoopla', old('market_on'))) ? 'selected' : '' }}>Zoopla</option>
                <option value="onthemarket" {{ (isset($property) && is_array($property->market_on) && in_array('onthemarket', $property->market_on)) || (is_array(old('market_on')) && in_array('onthemarket', old('market_on'))) ? 'selected' : '' }}>OnTheMarket</option>
            </select>
            @error('market_on')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>


        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>
@endif