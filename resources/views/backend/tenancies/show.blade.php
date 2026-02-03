{{-- resources/views/admin/tenancies/view.blade.php --}}
{{-- @extends('layouts.admin') --}}

{{-- @section('content') --}}
<div id="mainForm">
    
    <div class="mb-3">
        <strong>Property:</strong> {{ $tenancy->property->full_address ?? 'N/A' }}
    </div>

    <div class="mb-3">
        <strong>Tenants:</strong>
        <ul>
            @foreach($tenancy->tenantMembers as $member)
                <li>
                    {{ $member->user->name ?? 'N/A' }} ({{ $member->user->email ?? '' }})
                    @if($member->is_main_person)
                        <span class="badge bg-success ms-2">Main Person</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="row">
        <div class="col">
            <strong>Status:</strong> {{ $tenancy->status }}
        </div>
        <div class="col">
            <strong>Tenancy Type:</strong> {{ $tenancy->tenancyType->name ?? 'N/A' }}
        </div>
        <div class="col">
            <strong>Sub Status:</strong> {{ $tenancy->tenancySubStatus->name ?? 'N/A' }}
        </div>
    </div>

    <div class="mt-3">
        <strong>Periodic:</strong> {{ $tenancy->periodic ? 'Yes' : 'No' }}<br>
        <strong>Rolling Contract:</strong> {{ $tenancy->rolling_contract ? 'Yes' : 'No' }}<br>
        <strong>Renewal Exempt:</strong> {{ $tenancy->renewal_exempt ? 'Yes' : 'No' }}
    </div>

    <hr>

    <div class="row">
        <div class="col">
            <strong>Move In:</strong> {{ $tenancy->move_in }}
        </div>
        <div class="col">
            <strong>Term:</strong> {{ $tenancy->term_months ?? 0 }} months, {{ $tenancy->term_days ?? 0 }} days
        </div>
        <div class="col">
            <strong>Move Out:</strong> {{ $tenancy->move_out }}
        </div>
    </div>

    <div class="row mt-3">
        <div class="col">
            <strong>Renewal Confirm Date:</strong> {{ $tenancy->tenancy_renewal_confirm_date ?? 'N/A' }}
        </div>
        <div class="col">
            <strong>Extension Date:</strong> {{ $tenancy->extension_date ?? 'N/A' }}
        </div>
    </div>

    <hr>

    <div class="row">
        <div class="col">
            <strong>Rent:</strong> £{{ number_format($tenancy->rent, 2) }}
        </div>
        <div class="col">
            <strong>Deposit:</strong> £{{ number_format($tenancy->deposit, 2) }}
        </div>
    </div>

    <div class="row mt-3">
        <div class="col">
            <strong>Deposit Type:</strong> {{ $tenancy->deposit_type }}
        </div>
        <div class="col">
            <strong>Deposit Number:</strong> {{ $tenancy->deposit_number }}
        </div>
    </div>

    <div class="row mt-3">
        <div class="col">
            <strong>Deposit Held By:</strong> {{ $tenancy->deposit_held_by }}
        </div>
        <div class="col">
            <strong>Deposit Service:</strong> {{ $tenancy->deposit_service }}
        </div>
    </div>

    @if($tenancy->tds_dps_number)
    <div class="mt-2">
        <strong>TDS / DPS Reference Number:</strong> {{ $tenancy->tds_dps_number }}
    </div>
    @endif

    @if($tenancy->reference_number || $tenancy->deposit_scheme)
    <div class="mt-2">
        <strong>Reference Number:</strong> {{ $tenancy->reference_number ?? 'N/A' }}<br>
        <strong>Deposit Scheme:</strong> {{ $tenancy->deposit_scheme ?? 'N/A' }}
    </div>
    @endif

    <hr>

    <div class="mb-3">
        <strong>Property Managers:</strong>
        <ul>
            @foreach($tenancy->propertyManagers as $manager)
                <li>{{ $manager->name }}</li>
            @endforeach
        </ul>
    </div>

</div>
{{-- @endsection --}}
