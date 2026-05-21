<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Property Brochure</title>
    <style>
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.42; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { border-bottom: 1px solid #d0d5dd; font-size: 14px; margin: 0 0 7px; padding-bottom: 5px; }
        .header { border-bottom: 2px solid #172033; margin-bottom: 16px; padding-bottom: 12px; }
        .brand { font-size: 21px; font-weight: 700; }
        .muted { color: #667085; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { vertical-align: top; padding: 5px; }
        .photo { border: 1px solid #e5e7eb; height: 145px; object-fit: cover; width: 100%; }
        .section { margin-top: 15px; }
        .facts { width: 100%; border-collapse: collapse; }
        .facts td { border-bottom: 1px solid #eef1f5; padding: 5px 4px; vertical-align: top; }
        .label { color: #667085; font-weight: 700; width: 34%; }
        .logo { max-height: 70px; max-width: 180px; }
        .note { border: 1px solid #f2c94c; background: #fff9e6; padding: 9px; }
    </style>
</head>
<body>
    @php
        $address = implode(', ', array_filter([$property->prop_name, $property->line_1, $property->line_2, $property->city, $property->country, $property->postcode]));
        $companyName = $company?->name ?: config('app.name');
        $companyEmail = collect($company?->emails ?? [])->first();
        $companyPhone = collect($company?->phones ?? [])->first();
        $companyWebsite = $company?->website ?: '';
        $branchAddress = $branch ? implode(', ', array_filter([$branch->address_line_1 ?: $branch->address, $branch->address_line_2, $branch->city, $branch->county, $branch->postcode])) : '';
        $contactEmail = $companyEmail ?: ($branch?->user_email ?: '');
        $contactPhone = $companyPhone ?: ($branch?->user_phone ?: '');

        $stringify = function ($value) use (&$stringify) {
            if ($value === null || $value === '') {
                return '';
            }
            if (is_array($value)) {
                $items = [];
                foreach ($value as $key => $item) {
                    $text = $stringify($item);
                    if ($text !== '') {
                        $items[] = is_string($key) ? $key . ': ' . $text : $text;
                    }
                }
                return implode(', ', $items);
            }
            if (is_object($value)) {
                return method_exists($value, '__toString') ? (string) $value : json_encode($value);
            }
            return (string) $value;
        };
        $display = fn($value) => ($text = $stringify($value)) !== '' ? $text : 'N/A';
        $yesNo = function ($value) {
            if ($value === null || $value === '') {
                return 'N/A';
            }
            return (string) $value === '1' ? 'Yes' : ((string) $value === '0' ? 'No' : $value);
        };
        $money = fn($value) => filled($value) ? 'GBP ' . number_format((float) $value, 2) : 'N/A';
        $date = fn($value) => filled($value) ? formatDate($value) : 'N/A';
        $list = function ($value) use ($display) {
            if ($value === null || $value === '' || $value === []) {
                return 'N/A';
            }
            if (is_array($value)) {
                return $display($value);
            }
            $decoded = is_string($value) ? json_decode($value, true) : null;
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $display($decoded);
            }
            return $value;
        };
        $nearestPlaces = function ($value) {
            if (blank($value)) {
                return 'N/A';
            }
            $decoded = is_string($value) ? json_decode($value, true) : $value;
            if (! is_array($decoded)) {
                return $value;
            }
            $places = [];
            foreach ($decoded as $place) {
                if (is_array($place)) {
                    $name = $place['name'] ?? $place['place'] ?? $place['title'] ?? null;
                    $distance = $place['distance'] ?? $place['miles'] ?? null;
                    $places[] = trim($name . ($distance ? ' (' . $distance . ')' : ''));
                }
            }
            return implode(', ', array_filter($places)) ?: 'N/A';
        };

        $descriptionRows = array_filter([
            'Sales Description' => $property->sales_status_description ?? null,
            'Lettings Description' => $property->letting_status_description ?? null,
        ]);
        $infoRows = [
            'Property Ref' => $property->prop_ref_no,
            'Furnishing Type' => $property->frunishing_type,
            'Property Type' => $property->property_type,
            'Transaction Type' => $property->transaction_type,
            'Specific Property Type' => $property->specific_property_type,
            'Current Status' => $property->current_status,
            'Sales Status' => $property->sales_current_status,
            'Letting Status' => $property->letting_current_status,
        ];
        $pricingRows = [
            'Move-in Date' => $date($property->available_from),
            'Market On' => $display($property->market_on),
            'Local Authority' => $property->localAuthority?->display_name,
            'Tenure' => $property->tenure,
            'Length of Lease' => $property->length_of_lease,
            'Sales Price' => $money($property->price),
            'Letting Price' => $money($property->letting_price),
            'Ground Rent' => $money($property->ground_rent),
            'Service Charge' => $money($property->service_charge),
            'Estate Charge' => $money($property->estate_charge),
            'Miscellaneous Charge' => $money($property->miscellaneous_charge),
            'Annual Council Tax' => $money($property->annual_council_tax),
            'Council Tax Band' => $property->council_tax_band,
        ];
        $featureRows = [
            'Bedrooms' => $property->bedroom,
            'Bathrooms' => $property->bathroom,
            'Reception Rooms' => $property->reception,
            'Floor' => $property->floor,
            'Square Feet' => filled($property->square_feet) ? number_format((float) $property->square_feet, 2) . ' sqft' : null,
            'Square Meter' => filled($property->square_meter) ? number_format((float) $property->square_meter, 2) . ' sqm' : null,
            'Aspects' => $property->aspects,
            'Furniture' => $list($property->furniture),
            'Kitchen' => $list($property->kitchen),
            'Heating and Cooling' => $list($property->heating_cooling),
            'Safety' => $list($property->safety),
            'Other Features' => $list($property->other),
        ];
        $serviceRows = [
            'Parking' => $yesNo($property->parking),
            'Parking Location' => $property->parking_location,
            'Balcony' => $yesNo($property->balcony),
            'Garden' => $yesNo($property->garden),
            'Pets Allowed' => $yesNo($property->pets_allow),
            'Service' => $property->service,
            'Collecting Rent' => $yesNo($property->collecting_rent),
        ];
        $locationRows = [
            'Nearest Station' => implode(', ', $stations ?? []),
            'Nearest School' => implode(', ', $schools ?? []),
            'Nearest Places' => $nearestPlaces($property->nearest_places ?? null),
            'Key Highlights' => $property->key_highlights ?? null,
            'Useful Information' => $property->useful_information ?? null,
            'Access Arrangement' => $property->access_arrangement ?? null,
        ];
        $complianceRows = [
            'EPC Required' => $yesNo($property->epc_required),
            'EPC Rating' => $property->epc_rating,
            'Gas' => $yesNo($property->is_gas),
            'Gas Safe Acknowledged' => $yesNo($property->gas_safe_acknowledged),
            'Video URL' => $property->video_url,
            'View 360' => $property->view_360,
        ];
    @endphp

    <div class="header">
        <table class="grid">
            <tr>
                <td style="width: 65%;">
                    <div class="brand">{{ $companyName }}</div>
                    <div class="muted">{{ $branchAddress }}</div>
                    <div class="muted">{{ trim($contactPhone . ' ' . $contactEmail) }}</div>
                </td>
                <td style="text-align: right;">
                    @if($company?->logo_path)
                        <img class="logo" src="{{ public_path('storage/' . $company->logo_path) }}" alt="Company Logo">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <h1>{{ $address ?: 'Property Brochure' }}</h1>
    <div class="muted">Property Ref: {{ $property->prop_ref_no ?: 'N/A' }}</div>

    <table class="grid section">
        <tr>
            @forelse($photoPaths as $photoPath)
                <td style="width: 50%;">
                    <img class="photo" src="{{ $photoPath }}" alt="Property photo">
                </td>
                @if($loop->iteration % 2 === 0)</tr><tr>@endif
            @empty
                <td><div class="photo" style="text-align:center; padding-top:65px;">No property photos available</div></td>
            @endforelse
        </tr>
    </table>

    @if(filled($property->imp_notes))
        <div class="section note">
            <strong>Important Note</strong><br>
            {!! nl2br(e($property->imp_notes)) !!}
        </div>
    @endif

    @foreach([
        'Property Information' => $infoRows,
        'Availability & Pricing' => $pricingRows,
        'Property Features' => $featureRows,
        'Service' => $serviceRows,
        'Location & Access' => $locationRows,
        'Compliance & Media' => $complianceRows,
    ] as $title => $rows)
        <div class="section">
            <h2>{{ $title }}</h2>
            <table class="facts">
                @foreach($rows as $label => $value)
                    <tr>
                        <td class="label">{{ $label }}</td>
                        <td>{!! nl2br(e($display($value))) !!}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach

    @if(count($descriptionRows))
        <div class="section">
            <h2>Description</h2>
            <table class="facts">
                @foreach($descriptionRows as $label => $value)
                    <tr>
                        <td class="label">{{ $label }}</td>
                        <td>{!! nl2br(e($value)) !!}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="section">
        <h2>Contact</h2>
        <p>
            {{ $companyName }}<br>
            {{ $contactEmail }}<br>
            {{ $contactPhone }}<br>
            {{ $companyWebsite }}
        </p>
    </div>
</body>
</html>
