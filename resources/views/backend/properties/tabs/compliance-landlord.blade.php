@php
    $types = $complianceTypes->keyBy('alias');
    $recordsFor = function ($alias) use ($types, $complianceRecords) {
        $type = $types->get($alias);
        if (! $type) {
            return collect();
        }

        return $complianceRecords->get($type->id)
            ?? $complianceRecords->get((string) $type->id)
            ?? collect();
    };
    $latestFor = function ($alias) use ($recordsFor) {
        return $recordsFor($alias)->sortByDesc(function ($record) {
            return optional($record->expiry_date)->timestamp ?? 0;
        })->first();
    };
    $toneFor = function ($record) {
        if (! $record?->expiry_date) {
            return 'ok';
        }
        if ($record->expiry_date->isPast()) {
            return 'expired';
        }
        if ($record->expiry_date->lte(now()->addDays(60))) {
            return 'due';
        }

        return 'ok';
    };
    $epcRating = strtoupper(trim((string) ($property->epc_rating ?? '')));
    $epcFromRegister = $epcRating !== '';
    $sources = strtolower((string) ($property->useful_information ?? ''));
    $epcSourceLabel = str_contains($sources, 'epc.open-data') || str_contains($sources, 'epc')
        ? 'UK EPC register'
        : 'Property record';
    $noGas = in_array((string) $property->is_gas, ['0', 'false', 'no'], true);
    $epcType = $types->get('epc');
    $gasType = $types->get('gas');
    $eicrType = $types->get('eicr');
    $regType = $types->get('landlord_registration');
    $epcRecord = $latestFor('epc');
    $gasRecord = $latestFor('gas');
    $eicrRecord = $latestFor('eicr');
    $regRecords = $recordsFor('landlord_registration');
@endphp

<div id="hidden-property-id" class="d-none" data-property-id="{{ $propertyId }}"></div>

<div class="pcc-cert-board">
    <p class="pcc-cert-lead">
        Energy rating is filled from the UK register when we look up the address.
        Gas and electrical certificates still need a copy on file — they are not in that register.
    </p>

    <div class="pcc-cert-grid">
        <article class="pcc-cert-card">
            <div class="pcc-cert-card-head">
                <h3>EPC</h3>
                @if($epcFromRegister)
                    <span class="pcc-cert-pill is-ok">On the register</span>
                @elseif($epcRecord)
                    <span class="pcc-cert-pill is-{{ $toneFor($epcRecord) }}">On file</span>
                @else
                    <span class="pcc-cert-pill is-missing">Not found</span>
                @endif
            </div>
            <p class="pcc-cert-value">{{ $epcFromRegister ? 'Rating '.$epcRating : ($epcRecord ? 'Certificate uploaded' : 'No rating on file') }}</p>
            <p class="pcc-cert-meta">
                @if($epcFromRegister)
                    Pulled from the {{ $epcSourceLabel }}. Store a PDF in Documents if you need to share it with the tenant.
                @elseif($epcRecord?->expiry_date)
                    Expires {{ $epcRecord->expiry_date->format('d M Y') }}.
                @else
                    We did not match an EPC for this address. You can still upload a certificate.
                @endif
            </p>
            @if($epcType && ! $epcFromRegister)
                <button type="button" class="pcc-btn-ink" onclick="openComplianceModal({{ $epcType->id }}{{ $epcRecord ? ', '.$epcRecord->id : '' }})">
                    {{ $epcRecord ? 'Update certificate' : 'Upload certificate' }}
                </button>
            @endif
        </article>

        <article class="pcc-cert-card">
            <div class="pcc-cert-card-head">
                <h3>Gas Safe</h3>
                @if($noGas)
                    <span class="pcc-cert-pill is-ok">Not required</span>
                @elseif($gasRecord)
                    <span class="pcc-cert-pill is-{{ $toneFor($gasRecord) }}">{{ $toneFor($gasRecord) === 'ok' ? 'On file' : ($toneFor($gasRecord) === 'due' ? 'Due soon' : 'Expired') }}</span>
                @else
                    <span class="pcc-cert-pill is-missing">Upload needed</span>
                @endif
            </div>
            <p class="pcc-cert-value">
                @if($noGas)
                    No gas appliances recorded
                @elseif($gasRecord?->expiry_date)
                    Expires {{ $gasRecord->expiry_date->format('d M Y') }}
                @else
                    Annual certificate
                @endif
            </p>
            <p class="pcc-cert-meta">
                @if($noGas)
                    If the property has gas, upload the latest Gas Safe record here.
                @else
                    Required each year for lets with gas. Upload the engineer’s certificate and set the expiry.
                @endif
            </p>
            @if($gasType && ! $noGas)
                <button type="button" class="pcc-btn-ink" onclick="openComplianceModal({{ $gasType->id }}{{ $gasRecord ? ', '.$gasRecord->id : '' }})">
                    {{ $gasRecord ? 'Update certificate' : 'Upload certificate' }}
                </button>
            @elseif($gasType && $noGas)
                <button type="button" class="pcc-btn-ghost" onclick="openComplianceModal({{ $gasType->id }})">
                    Upload anyway
                </button>
            @endif
        </article>

        <article class="pcc-cert-card">
            <div class="pcc-cert-card-head">
                <h3>EICR</h3>
                @if($eicrRecord)
                    <span class="pcc-cert-pill is-{{ $toneFor($eicrRecord) }}">{{ $toneFor($eicrRecord) === 'ok' ? 'On file' : ($toneFor($eicrRecord) === 'due' ? 'Due soon' : 'Expired') }}</span>
                @else
                    <span class="pcc-cert-pill is-missing">Upload needed</span>
                @endif
            </div>
            <p class="pcc-cert-value">
                @if($eicrRecord?->expiry_date)
                    Expires {{ $eicrRecord->expiry_date->format('d M Y') }}
                @else
                    Electrical safety
                @endif
            </p>
            <p class="pcc-cert-meta">Needed every 5 years for a let. Upload the report and set when it next expires.</p>
            @if($eicrType)
                <button type="button" class="pcc-btn-ink" onclick="openComplianceModal({{ $eicrType->id }}{{ $eicrRecord ? ', '.$eicrRecord->id : '' }})">
                    {{ $eicrRecord ? 'Update certificate' : 'Upload certificate' }}
                </button>
            @endif
        </article>
    </div>

    @if($regType && $regRecords->isNotEmpty())
        <div class="pcc-cert-extra">
            <h3>Landlord registration</h3>
            @foreach($regRecords as $record)
                <p class="pcc-cert-meta mb-2">
                    {{ $record->expiry_date ? 'Expires '.$record->expiry_date->format('d M Y') : 'On file' }}
                    <button type="button" class="pcc-btn-ghost ms-2" onclick="openComplianceModal({{ $regType->id }}, {{ $record->id }})">Update</button>
                </p>
            @endforeach
        </div>
    @endif
</div>
