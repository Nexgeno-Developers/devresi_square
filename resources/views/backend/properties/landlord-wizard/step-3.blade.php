@extends('backend.properties.landlord-wizard.layout')

@php
    $stepTitle = $steps[$step] ?? 'Review';
    $knownCount = collect($completeness)->where('status', 'known')->count();
    $totalCount = count($completeness);
    $confidence = $totalCount > 0 ? (int) round(($knownCount / $totalCount) * 100) : 0;
@endphp

@section('wizard_content')
    <form method="POST" action="{{ route('admin.properties.landlord_wizard.store') }}" class="lpw-form">
        @csrf
        <input type="hidden" name="step" value="3">
        <input type="hidden" name="property_id" value="{{ $property->id }}">

        <header class="lpw-step-header">
            <p class="lpw-step-label">Step 03</p>
            <h1 class="lpw-step-title">{{ $stepTitle }}</h1>
            <p class="lpw-step-lead">What's known — and what's still missing. You can complete gaps later from your property dashboard.</p>
        </header>

        <article class="lpw-passport-card" aria-label="Property summary">
            <div class="lpw-passport-card-header">
                <div>
                    <p class="lpw-passport-ref">{{ $property->prop_ref_no }}</p>
                    <h2 class="lpw-passport-address">{{ $property->line_1 }}</h2>
                    <p class="lpw-passport-location">{{ $property->city }}, {{ $property->postcode }}</p>
                </div>
                <div class="lpw-confidence" aria-label="Data completeness {{ $confidence }} percent">
                    <span class="lpw-confidence-value">{{ $confidence }}%</span>
                    <span class="lpw-confidence-label">Complete</span>
                </div>
            </div>

            <dl class="lpw-passport-stats">
                @if ($property->specific_property_type)
                    <div>
                        <dt>Type</dt>
                        <dd>{{ ucfirst($property->specific_property_type) }}</dd>
                    </div>
                @endif
                @if ($property->bedroom !== null)
                    <div>
                        <dt>Bedrooms</dt>
                        <dd>{{ $property->bedroom }}</dd>
                    </div>
                @endif
                @if ($property->bathroom !== null)
                    <div>
                        <dt>Bathrooms</dt>
                        <dd>{{ $property->bathroom }}</dd>
                    </div>
                @endif
                @if ($property->tenure)
                    <div>
                        <dt>Tenure</dt>
                        <dd>{{ $property->tenure }}</dd>
                    </div>
                @endif
                @if ($property->epc_rating)
                    <div>
                        <dt>EPC</dt>
                        <dd><span class="lpw-epc-badge">{{ $property->epc_rating }}</span></dd>
                    </div>
                @endif
            </dl>
        </article>

        <ul class="lpw-completeness-list" aria-label="Field completeness">
            @foreach ($completeness as $item)
                <li class="lpw-completeness-item is-{{ $item['status'] }}">
                    <span class="lpw-completeness-label">{{ $item['label'] }}</span>
                    @if ($item['status'] === 'known')
                        <span class="lpw-completeness-value">{{ $item['value'] }}</span>
                    @else
                        <span class="lpw-completeness-missing">Not provided yet</span>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="lpw-panel lpw-confirm-panel">
            <label class="lpw-checkbox">
                <input type="checkbox" name="confirm" value="1" required @checked(old('confirm'))>
                <span>I confirm these property details are correct. I understand I can update them later.</span>
            </label>
            @error('confirm')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </div>

        <div class="lpw-actions lpw-actions-split">
            <a href="{{ route('admin.properties.landlord_wizard.step', ['step' => 2, 'property_id' => $property->id]) }}" class="btn btn-outline-secondary">Back</a>
            <button type="submit" class="btn btn-primary btn-lg lpw-btn-primary">Create Property Passport</button>
        </div>
    </form>
@endsection
