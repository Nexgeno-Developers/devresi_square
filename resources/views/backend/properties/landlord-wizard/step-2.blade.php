@extends('backend.properties.landlord-wizard.layout')

@php
    $stepTitle = $steps[$step] ?? 'Property details';
    $selectedType = old('specific_property_type', $property->specific_property_type ?? '');
    $selectedListing = old('property_type', $property->property_type ?? 'lettings');
@endphp

@section('wizard_content')
    <form method="POST" action="{{ route('admin.properties.landlord_wizard.store') }}" class="lpw-form">
        @csrf
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="property_id" value="{{ $property->id }}">

        <header class="lpw-step-header">
            <p class="lpw-step-label">Step 02</p>
            <h1 class="lpw-step-title">{{ $stepTitle }}</h1>
            <p class="lpw-step-lead">Tell us what kind of property this is. You can add more detail later from your Property Passport.</p>
        </header>

        <div class="lpw-panel">
            <fieldset class="mb-4">
                <legend class="form-label fw-semibold">Property type</legend>
                <div class="lpw-type-grid">
                    @foreach (['house' => 'House', 'flat' => 'Flat', 'appartment' => 'Apartment', 'bunglow' => 'Bungalow'] as $value => $label)
                        <label class="lpw-type-card {{ $selectedType === $value ? 'is-selected' : '' }}">
                            <input type="radio" name="specific_property_type" value="{{ $value }}"
                                @checked($selectedType === $value) required>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('specific_property_type')<div class="text-danger small">{{ $message }}</div>@enderror
            </fieldset>

            <fieldset class="mb-4">
                <legend class="form-label fw-semibold">Listing purpose</legend>
                <input type="hidden" name="property_type" value="lettings">
                <p class="text-muted mb-0">This home is recorded as a letting.</p>
            </fieldset>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="bedroom" class="form-label">Bedrooms</label>
                    <select name="bedroom" id="bedroom" class="form-select" required>
                        @for ($i = 0; $i <= 10; $i++)
                            <option value="{{ $i }}" @selected(old('bedroom', $property->bedroom ?? '') == (string) $i)>{{ $i }}</option>
                        @endfor
                    </select>
                    @error('bedroom')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="bathroom" class="form-label">Bathrooms</label>
                    <select name="bathroom" id="bathroom" class="form-select" required>
                        @for ($i = 0; $i <= 10; $i++)
                            <option value="{{ $i }}" @selected(old('bathroom', $property->bathroom ?? '') == (string) $i)>{{ $i }}</option>
                        @endfor
                    </select>
                    @error('bathroom')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="tenure" class="form-label">Tenure <span class="text-muted">(optional)</span></label>
                    <select name="tenure" id="tenure" class="form-select">
                        <option value="">Not provided yet</option>
                        @foreach (['Freehold', 'Leasehold', 'Shared ownership', 'Commonhold'] as $tenure)
                            <option value="{{ $tenure }}" @selected(old('tenure', $property->tenure ?? '') === $tenure)>{{ $tenure }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="epc_rating" class="form-label">EPC rating <span class="text-muted">(optional)</span></label>
                    <select name="epc_rating" id="epc_rating" class="form-select">
                        <option value="">Not provided yet</option>
                        @foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $rating)
                            <option value="{{ $rating }}" @selected(old('epc_rating', $property->epc_rating ?? '') === $rating)>{{ $rating }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="lpw-actions lpw-actions-split">
            <a href="{{ route('admin.properties.landlord_wizard.step', ['step' => 1, 'property_id' => $property->id]) }}" class="btn btn-outline-secondary">Back</a>
            <button type="submit" class="btn btn-primary btn-lg lpw-btn-primary">Review Property Passport</button>
        </div>
    </form>
@endsection
