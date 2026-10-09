@extends('backend.layout.app')

@section('content')
@php
    $trades = \App\Support\ContractorCompliance::trades();
    $types = array_values(array_unique(array_merge($required, $optional, $profile->documents->pluck('type')->map(fn ($t) => str_replace('.renewal', '', $t))->all())));
    $status = $profile->approval_status;
    $pill = fn ($s) => match ($s) { 'approved' => 'ok', 'rejected' => 'bad', 'pending', 'more_info' => 'warn', default => 'idle' };
    $companyUrl = $profile->company_number ? 'https://find-and-update.company-information.service.gov.uk/company/'.urlencode($profile->company_number) : null;
@endphp
<div class="container-fluid lw-page" style="max-width: 1100px;">
    <a href="{{ route('admin.registrations.contractors.index') }}" class="small">← Review queue</a>
    <div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap mt-2">
        <div>
            <h4>{{ $profile->displayName() }}</h4>
            <p>{{ $profile->user?->email }} · {{ $profile->phone ?: $profile->user?->phone }} · signed up {{ $profile->created_at?->format('j M Y') }}</p>
        </div>
        <x-lw.pill :tone="$status === 'approved' ? 'ok' : (in_array($status, ['rejected', 'suspended', 'removed'], true) ? 'bad' : 'warn')">{{ $profile->statusLabel() }}</x-lw.pill>
    </div>

    @foreach(['success', 'error'] as $flash)
        @if(session($flash))<div class="alert alert-{{ $flash === 'success' ? 'success' : 'danger' }}">{{ session($flash) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if($duplicates->isNotEmpty())
        <div class="alert alert-warning small">Company number also used by: {{ $duplicates->map(fn ($d) => $d->displayName().' ('.$d->user?->email.')')->implode(', ') }}. One account per firm.</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card lw-card mb-3">
                <div class="card-body">
                    <div class="lw-section-title mt-0">Documents</div>
                    @foreach($types as $type)
                        @php
                            $spec = \App\Support\ContractorCompliance::documentSpec($type);
                            $rows = collect([$profile->document($type), $store->renewalFor($profile, $type)])->filter();
                        @endphp
                        @if($rows->isEmpty())
                            <div class="border rounded p-2 mb-2 d-flex justify-content-between">
                                <span>{{ $spec['label'] ?? $type }} @unless(in_array($type, $required, true))<span class="small text-muted">(optional)</span>@endunless</span>
                                <span class="small text-muted">{{ in_array($type, $required, true) ? 'Not provided' : '—' }}</span>
                            </div>
                            @continue
                        @endif
                        @foreach($rows as $doc)
                            @php $isRenewal = str_ends_with($doc->type, '.renewal'); @endphp
                            <div class="border rounded p-3 mb-2">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <strong>{{ $spec['label'] ?? $type }}</strong>
                                        @if($isRenewal)<span class="badge text-bg-info">Renewal</span>@endif
                                        <div class="small text-muted">
                                            Submitted {{ $doc->submitted_at?->format('j M Y H:i') ?: '—' }}
                                            @if($doc->path)
                                                · <a href="{{ route('admin.registrations.contractors.document', [$profile, $doc]) }}" target="_blank" rel="noopener">Open file</a>
                                            @elseif(($spec['file'] ?? true) === false)
                                                · number only
                                            @endif
                                            @if(! empty($spec['lookup'])) · <a href="{{ $spec['lookup'] }}" target="_blank" rel="noopener">Check the register</a>@endif
                                        </div>
                                        @if($doc->rejected_reason && $doc->status === 'rejected')<div class="small text-danger">Rejected: {{ $doc->rejected_reason }}</div>@endif
                                    </div>
                                    <x-lw.pill :tone="$pill($doc->status)">{{ ucfirst($doc->status) }}@if($doc->isExpired()) · expired @endif</x-lw.pill>
                                </div>
                                <form method="POST" action="{{ route('admin.registrations.contractors.document.decide', [$profile, $doc]) }}" class="row g-2 align-items-end">
                                    @csrf
                                    @if(! empty($spec['reference']) || $doc->reference)
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small mb-1">{{ $spec['reference'] ?? 'Reference' }}</label>
                                            <input name="reference" class="form-control form-control-sm" value="{{ $doc->reference }}">
                                        </div>
                                    @endif
                                    @if(! empty($spec['issuers']))
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small mb-1">Scheme</label>
                                            <input name="issuer" class="form-control form-control-sm" value="{{ $doc->issuer }}">
                                        </div>
                                    @endif
                                    @if(! empty($spec['cover']))
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small mb-1">Cover £</label>
                                            <input name="cover_amount" type="number" step="1" class="form-control form-control-sm" value="{{ $doc->cover_amount !== null ? (int) $doc->cover_amount : '' }}">
                                        </div>
                                    @endif
                                    @if(! empty($spec['expires']))
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small mb-1">Expires</label>
                                            <input name="expires_at" type="date" class="form-control form-control-sm" value="{{ $doc->expires_at?->format('Y-m-d') }}">
                                        </div>
                                    @endif
                                    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                        <button class="btn btn-success btn-sm" name="decision" value="approve" type="submit">Approve</button>
                                        <select name="reason_code" class="form-select form-select-sm w-auto">
                                            <option value="">Reject reason…</option>
                                            @foreach($rejectReasons as $code => $label)
                                                <option value="{{ $code }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input name="reason" class="form-control form-control-sm w-auto flex-grow-1" placeholder="Or type a reason">
                                        <button class="btn btn-outline-danger btn-sm" name="decision" value="reject" type="submit">Reject</button>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <div class="card lw-card mb-3">
                <div class="card-body">
                    <div class="lw-section-title mt-0">Trades</div>
                    <p class="small text-muted">Approval is per trade. Gas needs Gas Safe; electrical needs a scheme registration.</p>
                    @forelse($profile->tradeApprovals as $approval)
                        @php $docs = \App\Support\ContractorCompliance::documentsForTrade($approval->trade); @endphp
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span>
                                    <strong>{{ $trades[$approval->trade] ?? $approval->trade }}</strong>
                                    <x-lw.pill :tone="$pill($approval->status)">{{ $approval->label() }}</x-lw.pill>
                                    @foreach($docs as $docType)
                                        @php $problem = \App\Support\ContractorCompliance::documentProblem($profile, $docType); @endphp
                                        <span class="small {{ $problem ? 'text-danger' : 'text-success' }}">· {{ $problem ?: (\App\Support\ContractorCompliance::documentSpec($docType)['label'] ?? $docType).' ok' }}</span>
                                    @endforeach
                                    @if($approval->reason)<span class="small text-muted">· {{ $approval->reason }}</span>@endif
                                </span>
                                <form method="POST" action="{{ route('admin.registrations.contractors.trade.decide', [$profile, $approval->trade]) }}" class="d-flex gap-1 flex-wrap">
                                    @csrf
                                    <input name="reason" class="form-control form-control-sm" style="width:180px" placeholder="Reason (if not approving)">
                                    <button class="btn btn-success btn-sm" name="decision" value="approved" type="submit">Approve</button>
                                    <button class="btn btn-outline-secondary btn-sm" name="decision" value="more_info" type="submit">More info</button>
                                    <button class="btn btn-outline-danger btn-sm" name="decision" value="rejected" type="submit">Reject</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">No trades chosen yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card lw-card mb-3">
                <div class="card-body small">
                    <div class="lw-section-title mt-0">Business</div>
                    <p class="mb-1">{{ $profile->trading_type === 'limited_company' ? 'Limited company' : 'Sole trader' }}@if($profile->legal_name) · {{ $profile->legal_name }}@endif</p>
                    @if($profile->company_number)
                        <p class="mb-1">Company {{ $profile->company_number }} · <a href="{{ $companyUrl }}" target="_blank" rel="noopener">Companies House</a></p>
                    @endif
                    <p class="mb-1">VAT: {{ $profile->vat_registered ? ($profile->vat_number ?: 'number missing') : 'Not registered' }}</p>
                    <p class="mb-1">Covers {{ $profile->coverage_radius_miles }} miles from {{ $profile->postcode ?: '—' }}@if($profile->latitude === null && $profile->postcode) <span class="text-warning">(not geocoded)</span>@endif</p>
                    <p class="mb-1">Employs staff: {{ $profile->employs_staff ? 'Yes' : 'No' }}</p>
                    <p class="mb-1">UTR: {{ $profile->utr ?: '—' }} · CIS: {{ $profile->cis_status ?: 'unverified' }}</p>
                    <p class="mb-0 text-muted">Terms {{ $profile->terms_accepted_at ? 'accepted '.$profile->terms_accepted_at->format('j M Y').' (v'.$profile->privacy_version.')' : 'not accepted' }}</p>
                </div>
            </div>

            <div class="card lw-card mb-3">
                <div class="card-body">
                    <div class="lw-section-title mt-0">Decision</div>
                    @if($ready)
                        <p class="small text-warning">Not ready: {{ $ready }}</p>
                    @else
                        <p class="small text-success">Ready to approve for: {{ collect(\App\Support\ContractorCompliance::approvedTrades($profile))->map(fn ($t) => $trades[$t] ?? $t)->implode(', ') }}</p>
                    @endif

                    @if(in_array($status, ['submitted', 'changes_requested', 'draft', 'rejected'], true))
                        <form method="POST" action="{{ route('admin.registrations.contractors.approve', $profile) }}" class="mb-2">
                            @csrf
                            <select class="form-select form-select-sm mb-1" name="cis_status">
                                @foreach(['unverified' => 'CIS: unverified', 'gross' => 'CIS: gross (0%)', 'net_20' => 'CIS: net (20%)', 'higher_30' => 'CIS: higher (30%)'] as $value => $label)
                                    <option value="{{ $value }}" @selected($profile->cis_status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-primary btn-sm w-100" type="submit" @disabled($ready)>Approve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.registrations.contractors.changes', $profile) }}" class="mb-2">
                            @csrf
                            <textarea class="form-control form-control-sm mb-1" name="review_note" rows="2" placeholder="Note (rejected items are listed for them)"></textarea>
                            <button class="btn btn-outline-secondary btn-sm w-100" type="submit">More info needed</button>
                        </form>
                        <form method="POST" action="{{ route('admin.registrations.contractors.reject', $profile) }}">
                            @csrf
                            <textarea class="form-control form-control-sm mb-1" name="review_note" rows="2" required placeholder="Why the application is rejected"></textarea>
                            <button class="btn btn-outline-danger btn-sm w-100" type="submit">Reject application</button>
                        </form>
                    @elseif($status === 'approved')
                        <form method="POST" action="{{ route('admin.registrations.contractors.suspend', $profile) }}" class="mb-2">
                            @csrf
                            <textarea class="form-control form-control-sm mb-1" name="review_note" rows="2" required placeholder="Reason for suspending"></textarea>
                            <button class="btn btn-outline-danger btn-sm w-100" type="submit">Suspend</button>
                        </form>
                    @elseif(in_array($status, ['suspended', 'removed'], true))
                        <form method="POST" action="{{ route('admin.registrations.contractors.reinstate', $profile) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-outline-primary btn-sm w-100" type="submit">Reinstate</button>
                        </form>
                    @endif
                    @if($status !== 'removed')
                        <details class="mt-2">
                            <summary class="small text-danger">Remove contractor</summary>
                            <form method="POST" action="{{ route('admin.registrations.contractors.remove', $profile) }}" class="mt-2">
                                @csrf
                                <textarea class="form-control form-control-sm mb-1" name="review_note" rows="2" required placeholder="Reason"></textarea>
                                <button class="btn btn-danger btn-sm w-100" type="submit">Remove</button>
                            </form>
                        </details>
                    @endif
                </div>
            </div>

            <div class="card lw-card mb-3">
                <div class="card-body small">
                    <div class="lw-section-title mt-0">Performance</div>
                    <p class="mb-0">
                        {{ $performance['invites'] ?? 0 }} invites · responds {{ ($performance['rate'] ?? null) !== null ? $performance['rate'].'%' : '—' }}
                        · {{ $performance['awarded'] ?? 0 }} awarded · {{ $performance['cancellations'] ?? 0 }} cancelled
                        @if(($performance['rating'] ?? null) !== null) · rated {{ $performance['rating'] }}★ from {{ $performance['ratings'] }} {{ \Illuminate\Support\Str::plural('job', $performance['ratings']) }} @endif
                    </p>
                </div>
            </div>

            <div class="card lw-card mb-3">
                <div class="card-body small">
                    <div class="lw-section-title mt-0">History</div>
                    <ul class="list-unstyled mb-0" style="max-height: 320px; overflow:auto">
                        @forelse($profile->reviewEvents as $event)
                            <li class="mb-1">
                                <span class="text-muted">{{ $event->created_at?->format('j M y H:i') }}</span>
                                {{ str_replace('_', ' ', $event->action) }}@if($event->subject) · {{ str_replace(['.renewal', '_'], [' renewal', ' '], $event->subject) }}@endif
                                @if($event->note)<span class="text-muted">· {{ \Illuminate\Support\Str::limit($event->note, 80) }}</span>@endif
                                @if($event->actor && $event->actor->id !== $profile->user_id)<span class="text-muted">· {{ $event->actor->name }}</span>@endif
                            </li>
                        @empty
                            <li class="text-muted">Nothing yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
