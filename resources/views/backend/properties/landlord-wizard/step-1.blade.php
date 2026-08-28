@extends('backend.properties.landlord-wizard.layout')

@php
    $stepTitle = $steps[$step] ?? 'Add property';
@endphp

@section('wizard_content')
    <form method="POST" action="{{ route('admin.properties.landlord_wizard.store') }}" class="lpw-form">
        @csrf
        <input type="hidden" name="step" value="1">
        <input type="hidden" name="property_id" value="{{ old('property_id', $property->id ?? '') }}">

        <header class="lpw-step-header">
            <p class="lpw-step-label">Step 01</p>
            <h1 class="lpw-step-title">{{ $stepTitle }}</h1>
            <p class="lpw-step-lead">Search by UK postcode to find your property address. Official address data helps build your Property Passport.</p>
        </header>

        <div class="lpw-panel">
            @include('backend.properties.partials.address-search', ['property' => $property ?? null])

            <div class="address-manual-fields {{ (isset($property) && $property->line_1) || old('line_1') ? '' : 'd-none' }}">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="line_1" class="form-label">Address line 1</label>
                        <input required type="text" name="line_1" id="line_1" class="form-control"
                            value="{{ old('line_1', $property->line_1 ?? '') }}">
                        @error('line_1')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="line_2" class="form-label">Address line 2 <span class="text-muted">(optional)</span></label>
                        <input type="text" name="line_2" id="line_2" class="form-control"
                            value="{{ old('line_2', $property->line_2 ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="city" class="form-label">City / town</label>
                        <input required type="text" name="city" id="city" class="form-control"
                            value="{{ old('city', $property->city ?? '') }}">
                        @error('city')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="postcode" class="form-label">Postcode</label>
                        <input required type="text" name="postcode" id="postcode" class="form-control"
                            value="{{ old('postcode', $property->postcode ?? '') }}">
                        @error('postcode')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="county" class="form-label">County <span class="text-muted">(optional)</span></label>
                        <input type="text" name="county" id="county" class="form-control"
                            value="{{ old('county', $property->county ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="country" class="form-label">Country</label>
                        <select required name="country" id="country" class="form-select">
                            <option value="">Select country</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}"
                                    @selected(old('country', $property->country ?? '') == $country->id || ((! isset($property) || empty($property->country)) && $country->code === 'GB'))>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('country')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="lpw-actions">
            <button type="submit" class="btn btn-primary btn-lg lpw-btn-primary">Continue to property details</button>
        </div>
    </form>
@endsection
