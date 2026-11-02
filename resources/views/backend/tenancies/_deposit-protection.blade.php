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
<section class="rs-section" data-deposit-protection="1">
    <div class="rs-section-head">
        <div>
            <h2>
                Deposit protection
                @if($complete)
                    <span class="badge bg-success ms-1">Complete</span>
                @else
                    <span class="badge bg-warning text-dark ms-1">Needs your attention</span>
                @endif
            </h2>
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

    <div class="rs-facts">
        <div>
            <span>Scheme</span>
            <strong>
                @if($schemeOk)<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-circle text-muted"></i>@endif
                {{ $tenancy->deposit_scheme ? strtoupper($tenancy->deposit_scheme) : 'Not set' }}
            </strong>
        </div>
        <div>
            <span>Reference</span>
            <strong>
                @if($refOk)<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-circle text-muted"></i>@endif
                {{ $tenancy->depositSchemeReference() ?? 'Not set' }}
            </strong>
        </div>
        <div>
            <span>Protected</span>
            <strong>
                @if($protectedOk)<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-circle text-muted"></i>@endif
                {{ $tenancy->deposit_protected_at?->timezone($tz)->format('d/m/Y H:i') ?? 'Not recorded' }}
            </strong>
        </div>
        <div>
            <span>Prescribed information sent</span>
            <strong>
                @if($prescribedSentOk)<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-circle text-muted"></i>@endif
                {{ $tenancy->prescribed_information_sent_at?->timezone($tz)->format('d/m/Y H:i') ?? 'Not recorded' }}
            </strong>
        </div>
        <div class="is-wide">
            <span>Document</span>
            <strong>
                @if($prescribedDocOk)<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-circle text-muted"></i>@endif
                Prescribed information document {{ $prescribedDocOk ? 'attached' : 'not attached' }}
            </strong>
        </div>
    </div>

    <p class="small text-muted mb-2">Attach the prescribed information PDF (and share with the tenant when ready).</p>
    <x-backend-documents-component
        :documentable-type="$tenancy->getMorphClass()"
        :documentable-id="$tenancy->id"
        :document-types="$documentTypes"
        :initial-documents="$tenancy->documents"
        :can-upload-documents="true"
    />
</section>
@endif
