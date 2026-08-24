{{-- resources/views/admin/tenancies/view.blade.php --}}
{{-- @extends('layouts.admin') --}}

{{-- @section('content') --}}
<div id="mainForm">
    
    <div class="mb-3">
        <strong>Property:</strong> {{ $tenancy->property->full_address ?? 'N/A' }}
    </div>

    <div class="mb-3">
        <strong>Right to rent follow-up register</strong>
        @foreach($tenancy->tenantMembers as $member)
            <form method="POST" action="{{ route('admin.tenancies.right-to-rent.update', [$tenancy, $member]) }}" class="row g-2 align-items-end border rounded p-2 mt-2">
                @csrf @method('PUT')
                <div class="col-md-3"><span>{{ $member->user?->name ?? 'Tenant' }}</span></div>
                <div class="col-md-2"><div class="form-check"><input type="hidden" name="right_to_rent_required" value="0"><input class="form-check-input" type="checkbox" name="right_to_rent_required" value="1" @checked($member->right_to_rent_required)><label class="form-check-label">Follow-up required</label></div></div>
                <div class="col-md-3"><label class="form-label">Checked at</label><input type="datetime-local" name="right_to_rent_checked_at" class="form-control" value="{{ $member->right_to_rent_checked_at?->format('Y-m-d\\TH:i') }}"></div>
                <div class="col-md-3"><label class="form-label">Follow-up due</label><input type="datetime-local" name="right_to_rent_follow_up_due_at" class="form-control" value="{{ $member->right_to_rent_follow_up_due_at?->format('Y-m-d\\TH:i') }}"></div>
                <div class="col-md-1"><button class="btn btn-sm btn-outline-primary">Save</button></div>
            </form>
        @endforeach
        <small class="text-muted">Reminder text never includes immigration details.</small>
    </div>

    <div class="mb-3">
        <strong>Tenants:</strong>
        <ul>
            @foreach($tenancy->tenantMembers as $member)
                <li>
                    {{ $member->user->name ?? 'N/A' }} ({{ $member->user->email ?? '' }})
                    @if($member->is_main_person)
                        <span class="badge bg-success ms-2">Main Tenant</span>
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
            <strong>Deposit Type:</strong> {{ $tenancy->deposit_type ? beautify_string($tenancy->deposit_type) : 'N/A' }}
        </div>
        <div class="col">
            <strong>Deposit Number:</strong> {{ $tenancy->deposit_number }}
        </div>
    </div>

    <div class="row mt-3">
        <div class="col">
            <strong>Deposit Held By:</strong> {{ $tenancy->deposit_held_by ? beautify_string($tenancy->deposit_held_by) : 'N/A' }}
        </div>
        <div class="col">
            <strong>Deposit Service:</strong> {{ $tenancy->deposit_service ? beautify_string($tenancy->deposit_service) : 'N/A' }}
        </div>
    </div>

    @if($tenancy->deposit_service === 'tds_dps_number' || $tenancy->tds_dps_number)
    <div class="mt-2">
        <strong>TDS / DPS Reference Number:</strong> {{ $tenancy->tds_dps_number ?? 'N/A' }}
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

    <hr>
    <div class="mb-3">
        <strong>Notice register</strong>
        <table class="table table-sm mt-2">
            <thead><tr><th>Type</th><th>Served</th><th>Effective</th><th>Recipient</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($tenancy->notices()->with('recipient')->latest('served_at')->get() as $notice)
                <tr>
                    <td>{{ beautify_string($notice->notice_type) }}</td>
                    <td>{{ $notice->served_at?->timezone(current_account()?->timezone ?? 'Europe/London')->format('d/m/Y H:i') }}</td>
                    <td>{{ $notice->effective_at?->timezone(current_account()?->timezone ?? 'Europe/London')->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $notice->recipient?->name ?? 'All tenants' }}</td>
                    <td>{{ ucfirst($notice->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No notices recorded.</td></tr>
            @endforelse
            </tbody>
        </table>

        <form method="POST" action="{{ route('admin.tenancies.notices.store', $tenancy) }}" class="row g-2">
            @csrf
            <div class="col-md-3">
                <select name="notice_type" class="form-select" required>
                    <option value="">Notice type</option>
                    <option value="written_information">Written information</option>
                    <option value="rent_increase">Rent increase</option>
                    <option value="tenant_notice">Tenant notice</option>
                    <option value="possession_notice">Possession notice</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="recipient_user_id" class="form-select">
                    <option value="">All tenants</option>
                    @foreach($tenancy->tenantMembers as $member)
                        <option value="{{ $member->user_id }}">{{ $member->user?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><input type="datetime-local" name="served_at" class="form-control" required></div>
            <div class="col-md-2"><input type="date" name="effective_at" class="form-control"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Record &amp; notify</button></div>
            <div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Evidence or internal notes"></textarea></div>
        </form>
    </div>

</div>
{{-- @endsection --}}
