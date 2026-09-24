@php
    $tz = current_account()?->timezone ?? 'Europe/London';
    $needsDeposit = $tenancy->hasDepositToProtect();
    $schemeOk = filled($tenancy->deposit_scheme);
    $refOk = filled($tenancy->depositSchemeReference());
    $protectedOk = (bool) $tenancy->deposit_protected_at;
    $prescribedSentOk = (bool) $tenancy->prescribed_information_sent_at;
    $prescribedDocOk = $tenancy->hasPrescribedInformationDocument();
    $deadline = $tenancy->depositProtectionDeadline();
    $complete = $tenancy->depositProtectionComplete();
    $documentTypes = $documentTypes ?? \App\Models\DocumentType::query()->orderBy('name')->get();
@endphp

@if($needsDeposit)
<hr>
<div class="mb-3" data-deposit-protection="1">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
        <div>
            <strong>Deposit protection</strong>
            @if($complete)
                <span class="badge bg-success ms-1">Complete</span>
            @else
                <span class="badge bg-warning text-dark ms-1">Needs you</span>
            @endif
            @if($deadline)
                <div class="small text-muted mt-1">
                    30-day deadline:
                    {{ $deadline->timezone($tz)->format('d/m/Y') }}
                    @if($deadline->isPast() && ! $complete)
                        <span class="text-danger">(overdue)</span>
                    @endif
                </div>
            @elseif(! $tenancy->deposit_received_at)
                <div class="small text-muted mt-1">Set “Deposit received” on edit to start the 30-day reminder clock.</div>
            @endif
        </div>
        <a href="{{ route('admin.tenancies.edit', $tenancy->id) }}" class="btn btn-sm btn-outline-dark">Edit deposit fields</a>
    </div>

    <ul class="list-unstyled mb-3 small">
        <li class="mb-1">
            @if($schemeOk)<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-circle text-muted"></i>@endif
            Scheme: {{ $tenancy->deposit_scheme ? strtoupper($tenancy->deposit_scheme) : 'Not set' }}
        </li>
        <li class="mb-1">
            @if($refOk)<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-circle text-muted"></i>@endif
            Reference: {{ $tenancy->depositSchemeReference() ?? 'Not set' }}
        </li>
        <li class="mb-1">
            @if($protectedOk)<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-circle text-muted"></i>@endif
            Protected:
            {{ $tenancy->deposit_protected_at?->timezone($tz)->format('d/m/Y H:i') ?? 'Not recorded' }}
        </li>
        <li class="mb-1">
            @if($prescribedSentOk)<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-circle text-muted"></i>@endif
            Prescribed information sent:
            {{ $tenancy->prescribed_information_sent_at?->timezone($tz)->format('d/m/Y H:i') ?? 'Not recorded' }}
        </li>
        <li class="mb-1">
            @if($prescribedDocOk)<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-circle text-muted"></i>@endif
            Prescribed information document attached
        </li>
    </ul>

    <p class="small text-muted mb-2">Attach the prescribed information PDF (and share with the tenant when ready).</p>
    <x-backend-documents-component
        :documentable-type="$tenancy->getMorphClass()"
        :documentable-id="$tenancy->id"
        :document-types="$documentTypes"
        :initial-documents="$tenancy->documents"
        :can-upload-documents="true"
    />
</div>
@endif
