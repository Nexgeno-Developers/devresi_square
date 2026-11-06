@extends('backend.layout.app')

@section('title', 'Edit property · Resisquare')

@section('content')
@php
    $types = [
        'house' => 'House',
        'flat' => 'Flat',
        'appartment' => 'Apartment',
        'bunglow' => 'Bungalow',
        'studio' => 'Studio',
        'maisonette' => 'Maisonette',
        'hmo' => 'HMO',
    ];
@endphp
<div class="container-fluid lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h4>Edit property</h4>
            <p>Update the address, room counts, occupancy and rent for this home.</p>
        </div>
        <a href="{{ route('admin.properties.index', ['property_id' => $property->id, 'tabname' => 'property']) }}" class="btn lw-btn-secondary">Back to property</a>
    </div>

    <form method="POST" action="{{ route('admin.properties.update', $property->id) }}" class="card lw-card">
        @csrf
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="line_1">Address line 1</label>
                    <input class="form-control" id="line_1" name="line_1" required maxlength="255" value="{{ old('line_1', $property->line_1) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="line_2">Address line 2</label>
                    <input class="form-control" id="line_2" name="line_2" maxlength="255" value="{{ old('line_2', $property->line_2) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="city">City / town</label>
                    <input class="form-control" id="city" name="city" required maxlength="255" value="{{ old('city', $property->city) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="county">State / region</label>
                    <input class="form-control" id="county" name="county" maxlength="155" value="{{ old('county', $property->county) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="postcode">Postcode</label>
                    <input class="form-control" id="postcode" name="postcode" required maxlength="20" value="{{ old('postcode', $property->postcode) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="country">Country</label>
                    <select class="form-select" id="country" name="country" required>
                        <option value="">Select country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country', $property->country) === (string) $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="specific_property_type">Property type</label>
                    <select class="form-select" id="specific_property_type" name="specific_property_type" required>
                        <option value="">Select property type</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('specific_property_type', $property->specific_property_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="prop_ref_no">Property reference</label>
                    <input class="form-control" id="prop_ref_no" name="prop_ref_no" maxlength="50" value="{{ old('prop_ref_no', $property->prop_ref_no) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="bedroom">Bedrooms</label>
                    <input class="form-control" id="bedroom" name="bedroom" type="number" min="0" max="50" required value="{{ old('bedroom', $property->bedroom) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="bathroom">Bathrooms</label>
                    <input class="form-control" id="bathroom" name="bathroom" type="number" min="0" max="50" required value="{{ old('bathroom', $property->bathroom) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="reception">Reception rooms</label>
                    <input class="form-control" id="reception" name="reception" type="number" min="0" max="50" required value="{{ old('reception', $property->reception ?? 0) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="square_meter">Floor area (m²)</label>
                    <input class="form-control" id="square_meter" name="square_meter" type="number" min="0" max="999999" step="0.01" value="{{ old('square_meter', $property->square_meter) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="letting_current_status">Occupancy</label>
                    <select class="form-select" id="letting_current_status" name="letting_current_status" required>
                        <option value="available" @selected(old('letting_current_status', $property->letting_current_status) === 'available')>Vacant</option>
                        <option value="let agreed" @selected(old('letting_current_status', $property->letting_current_status) === 'let agreed')>Occupied</option>
                        <option value="notice" @selected(old('letting_current_status', $property->letting_current_status) === 'notice')>Notice</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="letting_price">Monthly rent</label>
                    <input class="form-control" id="letting_price" name="letting_price" type="number" min="0.01" max="999999.99" step="0.01" required value="{{ old('letting_price', $property->letting_price) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="currency">Currency</label>
                    <select class="form-select" id="currency" name="currency" required>
                        @foreach (['GBP' => 'GBP (£)', 'EUR' => 'EUR (€)', 'USD' => 'USD ($)', 'AED' => 'AED'] as $code => $label)
                            <option value="{{ $code }}" @selected(old('currency', $property->currency ?: 'GBP') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="imp_notes">Notes</label>
                    <input class="form-control" id="imp_notes" name="imp_notes" maxlength="5000" value="{{ old('imp_notes', $property->imp_notes) }}">
                </div>
            </div>

            <button type="submit" class="btn lw-btn-primary mt-3">Save property</button>
        </div>
    </form>
</div>
@endsection
