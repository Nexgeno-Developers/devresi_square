@extends('backend.layout.app')

@push('styles')
<style>
    .contractor-job-page { max-width: 1080px; }
    .contractor-job-back {
        display: inline-block;
        margin-bottom: 0.7rem;
        font-size: 0.85rem;
        font-weight: 650;
        text-decoration: none;
    }
    .contractor-job-split.is-split {
        display: grid;
        grid-template-columns: minmax(0, 0.92fr) minmax(340px, 1.08fr);
        gap: 1rem;
        align-items: start;
    }
    .contractor-panel {
        background: #fff;
        border: 1px solid #e6e9ef;
        border-radius: 16px;
        padding: 1.15rem 1.25rem 1.2rem;
        margin-bottom: 1rem;
    }
    .contractor-job-kicker {
        margin: 0 0 0.35rem;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #ff5c1d;
    }
    .contractor-panel h1 {
        margin: 0;
        font-size: 1.35rem;
        letter-spacing: -0.03em;
        line-height: 1.25;
    }
    .contractor-facts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem 1.25rem;
        margin: 1rem 0 0;
    }
    .contractor-facts div span {
        display: block;
        margin-bottom: 0.15rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #8b95a3;
    }
    .contractor-facts div strong { font-weight: 650; }
    .contractor-job-lock { margin: 0.9rem 0 0; color: #5b6573; font-size: 0.85rem; }
    .job-price-types { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 1rem; }
    .job-price-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin-bottom: 0.9rem;
    }
    .job-price-grid > [hidden] { display: none !important; }
    .job-price-grid > [data-for="fixed"],
    .job-price-grid > [data-for="inspection"] { grid-column: 1 / -1; }
    .job-total {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 1rem;
        background: #f4f6f8;
        border-radius: 12px;
        padding: 0.85rem 1rem;
        margin-bottom: 0.9rem;
    }
    .job-total strong { font-size: 1.55rem; letter-spacing: -0.03em; }
    .job-offer-foot {
        display: grid;
        grid-template-columns: 160px minmax(0, 1fr);
        gap: 0.75rem;
        margin-bottom: 0.9rem;
    }
    .job-visit { margin-bottom: 0.9rem; }
    .job-visit-choices { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
    .job-decline { display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center; margin-top: 0.85rem; padding-top: 0.85rem; border-top: 1px solid #e6e9ef; }
    .contractor-history li { padding: 0.35rem 0; border-bottom: 1px solid #eef1f4; font-size: 0.88rem; color: #3d4654; }
    .contractor-history li:last-child { border-bottom: 0; padding-bottom: 0; }
    @media (max-width: 900px) {
        .contractor-job-split.is-split,
        .contractor-facts,
        .job-price-grid,
        .job-offer-foot { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
@php
    $district = \App\Support\ContractorCompliance::postcodeDistrict($property?->postcode);
    $location = $unlocked
        ? trim(collect([$property?->line_1, $property?->line_2, $property?->city, $property?->postcode])->filter()->implode(', '))
        : trim(collect([$property?->city, $district])->filter()->implode(', '));
    $urgency = $request?->urgency ?: 'routine';
    $tenantSlot = $repair->tenant_availability;
    $lostToAnother = \App\Models\RepairJobOffer::query()
        ->where('repair_issue_id', $repair->id)
        ->whereKeyNot($offer->id)
        ->whereIn('status', [\App\Models\RepairJobOffer::JOB_ACCEPTED, \App\Models\RepairJobOffer::INSPECTION_ACCEPTED])
        ->exists();
    $canQuote = $offer->canQuote() && ! $block && ! $repair->awarded_offer_id && ! $lostToAnother;
    $firmAfterInspection = $offer->status === 'inspection_accepted';
    $type = old('pricing_type', $firmAfterInspection ? 'fixed' : ($offer->pricing_type ?: 'fixed'));
    $vatRegistered = (bool) $profile?->vat_registered;
    $validDefault = \App\Support\ContractorSettings::offerValidHours($urgency);
    $photoIds = $repair->repairPhotos->flatMap(fn ($photo) => array_filter(array_map('trim', explode(',', (string) $photo->photos))))->values();
    $tenantName = trim(($repair->tenant?->first_name ?? '').' '.($repair->tenant?->last_name ?? '')) ?: ($repair->tenant?->name ?: null);
    $approvedVariations = $offer->variations->where('status', 'approved');
    $pendingVariation = $offer->variations->firstWhere('status', 'pending');
    $canVary = $offer->status === 'job_accepted' && ! $pendingVariation && ! $offer->disputed_at
        && ! in_array($offer->job_status, ['confirmed', 'invoiced'], true);
@endphp
<div class="container-fluid py-3 contractor-job-page">
    <a class="contractor-job-back" href="{{ route('contractor.jobs.index', ['tab' => $offer->tab()]) }}">← Jobs</a>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if($block && in_array($offer->status, \App\Models\RepairJobOffer::OPEN_STATUSES, true))
        <div class="alert alert-warning">{{ $block }} <a href="{{ route('contractor.profile.edit') }}">Fix it</a></div>
    @endif
    @if($offer->details_changed_at)
        <div class="alert alert-warning small">The landlord changed the details after your offer. Check them and update your price if needed.</div>
    @endif
    @if($offer->status === 'offer_expired' && $offer->reconfirm_requested_at)
        <div class="alert alert-primary d-flex justify-content-between align-items-center gap-2">
            <span>The landlord wants to accept your expired offer: {{ $summary }}.</span>
            <form method="POST" action="{{ route('contractor.jobs.reconfirm', $offer) }}">@csrf
                <button class="btn btn-primary btn-sm" type="submit">Reconfirm</button>
            </form>
        </div>
    @endif

    <div class="contractor-job-split {{ $canQuote ? 'is-split' : '' }}">
    <div class="contractor-job-col">
    <section class="contractor-panel">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
                <p class="contractor-job-kicker">{{ $offer->statusLabel() }}</p>
                <h1>{{ $repair->description }}</h1>
            </div>
            @if($urgency === 'emergency')<span class="badge text-bg-danger">Emergency</span>@elseif($urgency === 'urgent')<span class="badge text-bg-warning">Urgent</span>@endif
        </div>
        <div class="contractor-facts">
            <div>
                <span>Trade</span>
                <strong>{{ $repair->repairCategory?->name ?: 'General' }}</strong>
            </div>
            <div>
                <span>Where</span>
                <strong>{{ $location ?: 'Not given' }}</strong>
            </div>
            <div>
                <span>Tenant available</span>
                <strong>
                    {{ $repair->tenantAvailabilityLabel() ?: 'To be arranged' }}
                    @if($availabilityChanged)<span class="text-warning">(updated)</span>@endif
                </strong>
            </div>
            @if($offer->isAwaitingResponse() && $offer->invite_expires_at)
                <div>
                    <span>Respond by</span>
                    <strong>{{ $offer->invite_expires_at->format('D j M, H:i') }}</strong>
                    <span style="letter-spacing:0;text-transform:none;font-weight:500;color:#5b6573;">{{ $offer->invite_expires_at->diffForHumans() }}</span>
                </div>
            @endif
            @if($request?->show_budget && $request?->budget_ceiling)
                <div>
                    <span>Landlord budget</span>
                    <strong>Up to £{{ number_format((float) $request->budget_ceiling, 0) }}</strong>
                </div>
            @endif
            @if($unlocked)
                <div>
                    <span>Access</span>
                    <strong>{{ $request?->access_notes ?: ($repair->access_details ?: 'None recorded') }}</strong>
                </div>
                <div>
                    <span>Tenant</span>
                    <strong>{{ $tenantName ?: 'Not listed' }}
                        @if($repair->tenant?->phone) · <a href="tel:{{ $repair->tenant->phone }}">{{ $repair->tenant->phone }}</a> @endif
                        @if($repair->tenant?->email) · {{ $repair->tenant->email }} @endif
                    </strong>
                </div>
            @endif
        </div>
        @unless($unlocked)
            <p class="contractor-job-lock">Full address, access notes and tenant contact appear once the landlord accepts.</p>
        @endunless
        @if($photoIds->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mt-3">
                @foreach($photoIds as $photoId)
                    <a href="{{ route('contractor.jobs.photo', [$offer, $photoId]) }}" target="_blank" rel="noopener">
                        <img src="{{ route('contractor.jobs.photo', [$offer, $photoId]) }}" alt="Photo {{ $loop->iteration }}" style="width:96px;height:96px;object-fit:cover" class="rounded border">
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @if($offer->status === 'job_accepted')
        <div class="card mb-3 border-success"><div class="card-body">
            <p class="mb-2"><strong>You’re instructed.</strong> {{ $summary }}</p>
            @if($offer->disputed_at)
                <div class="alert alert-danger small">Disputed: {{ $offer->dispute_reason }}. Our team will contact you.</div>
            @endif
            @if($approvedVariations->isNotEmpty())
                <p class="small mb-2">Agreed extras bring the total to <strong>£{{ number_format($offer->finalGross(), 2) }}</strong>.</p>
            @endif
            @switch($offer->job_status)
                @case('completed')
                    <p class="small text-muted mb-0">Marked complete {{ $offer->completed_at?->diffForHumans() }}. Waiting for the landlord to confirm.</p>
                    @break
                @case('confirmed')
                    <p class="small text-success mb-2">The landlord confirmed the job is complete.</p>
                    @if($pendingVariation)
                        <p class="small text-muted mb-0">You can send your invoice once the landlord answers your extra work request.</p>
                    @else
                        <details class="border rounded p-2" open>
                            <summary class="fw-semibold small">Send your invoice</summary>
                            <form method="POST" action="{{ route('contractor.jobs.invoice.store', $offer) }}" class="mt-2">
                                @csrf
                                @if($offer->price_is_estimate)
                                    <label class="form-label small mb-1">Final price before VAT £</label>
                                    <input type="number" step="0.01" min="0.01" name="final_net" class="form-control form-control-sm mb-1" style="max-width:180px" inputmode="decimal"
                                        value="{{ old('final_net', number_format((float) $offer->net_amount, 2, '.', '')) }}">
                                    <div class="small text-muted mb-2">Your price was an estimate. @if($offer->price_cap)It can’t go over the cap you agreed.@endif Approved extras are added on top.</div>
                                @else
                                    <p class="small mb-2">Invoice total: <strong>£{{ number_format($offer->finalGross(), 2) }}</strong>@if($approvedVariations->isNotEmpty()) including agreed extras @endif.</p>
                                @endif
                                <textarea name="notes" rows="2" maxlength="2000" class="form-control form-control-sm mb-2" placeholder="Payment details or notes for the landlord (optional)">{{ old('notes') }}</textarea>
                                <button class="btn btn-primary btn-sm" type="submit">Issue invoice</button>
                            </form>
                        </details>
                    @endif
                    @break
                @case('invoiced')
                    <p class="small mb-0">
                        Invoice {{ $offer->invoice?->number }} sent ·
                        <span class="badge {{ $offer->invoice?->status === 'paid' ? 'text-bg-success' : ($offer->invoice?->isOverdue() ? 'text-bg-danger' : 'text-bg-warning') }}">{{ $offer->invoice?->statusLabel() }}</span>
                        @if($offer->invoice)<a href="{{ route('contractor.jobs.invoice', $offer) }}" class="ms-1">View invoice</a>@endif
                    </p>
                    @break
                @default
                    @if($offer->reschedule_requested_at)
                        <div class="alert alert-warning small">
                            <strong>{{ $offer->reschedule_requested_by === 'tenant' ? 'The tenant' : 'The landlord' }} can’t make the visit.</strong>
                            @if($offer->reschedule_note) “{{ $offer->reschedule_note }}” @endif
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach($offer->reschedule_slots ?? [] as $slot)
                                    <form method="POST" action="{{ route('contractor.jobs.reschedule', $offer) }}">@csrf
                                        <input type="hidden" name="slot" value="{{ $slot }}">
                                        <button class="btn btn-primary btn-sm" type="submit">{{ \Illuminate\Support\Carbon::parse($slot)->format('D j M, H:i') }}</button>
                                    </form>
                                @endforeach
                            </div>
                            <form method="POST" action="{{ route('contractor.jobs.reschedule', $offer) }}" class="d-flex flex-wrap gap-2 align-items-center mt-2">@csrf
                                <span>or suggest</span>
                                <input type="datetime-local" name="slot" required class="form-control form-control-sm w-auto" min="{{ now()->format('Y-m-d\TH:i') }}">
                                <button class="btn btn-outline-primary btn-sm" type="submit">Propose</button>
                            </form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('contractor.jobs.progress', $offer) }}" class="d-flex flex-wrap gap-2 align-items-end mb-2">
                            @csrf
                            <input type="hidden" name="step" value="schedule">
                            <div>
                                <label class="form-label small mb-1">Visit</label>
                                <input type="datetime-local" name="scheduled_for" required class="form-control form-control-sm" value="{{ old('scheduled_for', $offer->scheduled_for?->format('Y-m-d\TH:i')) }}">
                            </div>
                            <button class="btn btn-outline-primary btn-sm" type="submit">{{ $offer->scheduled_for ? 'Change visit' : 'Book visit' }}</button>
                            @if($offer->tenant_confirmed_at)<span class="small text-success">Tenant confirmed</span>@endif
                        </form>
                    @endif
                    <div class="d-flex flex-wrap gap-2">
                        @if($offer->job_status !== 'in_progress')
                            <form method="POST" action="{{ route('contractor.jobs.progress', $offer) }}">@csrf
                                <input type="hidden" name="step" value="start">
                                <button class="btn btn-outline-secondary btn-sm" type="submit">Start work</button>
                            </form>
                        @endif
                        <details>
                            <summary class="btn btn-success btn-sm">Mark complete</summary>
                            <form method="POST" action="{{ route('contractor.jobs.progress', $offer) }}" enctype="multipart/form-data" class="mt-2">@csrf
                                <input type="hidden" name="step" value="complete">
                                <textarea name="completion_note" rows="2" maxlength="2000" class="form-control form-control-sm mb-2" placeholder="What did you do? (optional)"></textarea>
                                <label class="form-label small mb-1">Photos of the finished work (at least one, up to {{ \App\Services\Contractors\RepairJobAftercareService::MAX_PHOTOS }})</label>
                                <input type="file" name="photos[]" accept="image/*" multiple required class="form-control form-control-sm mb-2">
                                <button class="btn btn-success btn-sm" type="submit">Confirm complete</button>
                            </form>
                        </details>
                    </div>
            @endswitch

            @if(! empty($offer->completion_photos))
                <div class="d-flex flex-wrap gap-2 mt-2">
                    @foreach($offer->completion_photos as $index => $path)
                        <a href="{{ route('contractor.jobs.completion_photo', [$offer, $index]) }}" target="_blank" rel="noopener">
                            <img src="{{ route('contractor.jobs.completion_photo', [$offer, $index]) }}" alt="Completion photo {{ $loop->iteration }}" style="width:72px;height:72px;object-fit:cover" class="rounded border">
                        </a>
                    @endforeach
                </div>
            @endif
            @if($offer->rating)
                <p class="small mt-2 mb-0">Landlord rating: <span class="text-warning">{{ str_repeat('★', (int) $offer->rating) }}</span><span class="text-muted">{{ str_repeat('☆', 5 - (int) $offer->rating) }}</span></p>
            @endif
        </div></div>

        @if($offer->variations->isNotEmpty() || $canVary)
            <div class="card mb-3"><div class="card-body">
                <h2 class="h6 mb-2">Extra work</h2>
                @foreach($offer->variations as $variation)
                    <div class="d-flex justify-content-between align-items-start gap-2 py-1 border-bottom small">
                        <span>
                            £{{ number_format((float) $variation->gross_amount, 2) }} · {{ $variation->reason }}
                            @if($variation->decision_note)<span class="d-block text-muted">Landlord: “{{ $variation->decision_note }}”</span>@endif
                        </span>
                        <span class="d-flex gap-2 align-items-center">
                            <span class="badge {{ ['approved' => 'text-bg-success', 'declined' => 'text-bg-danger', 'pending' => 'text-bg-warning'][$variation->status] ?? 'text-bg-light' }}">{{ $variation->statusLabel() }}</span>
                            @if($variation->status === 'pending')
                                <form method="POST" action="{{ route('contractor.jobs.variation.withdraw', [$offer, $variation]) }}">@csrf
                                    <button class="btn btn-link btn-sm p-0 text-muted" type="submit">Withdraw</button>
                                </form>
                            @endif
                        </span>
                    </div>
                @endforeach
                @if($canVary)
                    <details class="mt-2" @if($errors->has('net_amount') || $errors->has('reason')) open @endif>
                        <summary class="btn btn-outline-primary btn-sm">Ask for extra work</summary>
                        <form method="POST" action="{{ route('contractor.jobs.variation', $offer) }}" class="mt-2">@csrf
                            <div class="row g-2">
                                <div class="col-sm-4">
                                    <label class="form-label small mb-1">Extra £ before VAT</label>
                                    <input type="number" step="0.01" min="0.01" name="net_amount" required class="form-control form-control-sm" inputmode="decimal" value="{{ old('net_amount') }}">
                                </div>
                                <div class="col-sm-8">
                                    <label class="form-label small mb-1">What and why</label>
                                    <input name="reason" required maxlength="1000" class="form-control form-control-sm" value="{{ old('reason') }}" placeholder="E.g. corroded valve needs replacing">
                                </div>
                            </div>
                            <div class="small text-muted my-2">
                                @if($vatRegistered && (float) $offer->vat_rate > 0) VAT at {{ (int) $offer->vat_rate }}% is added. @endif
                                The landlord must approve before you do the extra work.
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit">Send for approval</button>
                        </form>
                    </details>
                @elseif($pendingVariation)
                    <p class="small text-muted mt-2 mb-0">Waiting for the landlord to answer your request.</p>
                @endif
            </div></div>
        @endif
    @endif

    @if($offer->status === 'inspection_accepted' && $offer->reschedule_requested_at)
        <div class="alert alert-warning small">
            <strong>{{ $offer->reschedule_requested_by === 'tenant' ? 'The tenant' : 'The landlord' }} can’t make the inspection.</strong>
            <div class="d-flex flex-wrap gap-2 mt-2">
                @foreach($offer->reschedule_slots ?? [] as $slot)
                    <form method="POST" action="{{ route('contractor.jobs.reschedule', $offer) }}">@csrf
                        <input type="hidden" name="slot" value="{{ $slot }}">
                        <button class="btn btn-primary btn-sm" type="submit">{{ \Illuminate\Support\Carbon::parse($slot)->format('D j M, H:i') }}</button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    @if($offer->messages->isNotEmpty() || ($offer->canMessage() && ! $offer->isFinished()))
        <section class="contractor-panel" id="messages">
            @include('backend.repair.partials.job-thread', [
                'offer' => $offer,
                'side' => \App\Models\RepairJobMessage::CONTRACTOR,
                'action' => route('contractor.jobs.message', $offer),
                'open' => $offer->messages->isNotEmpty(),
            ])
        </section>
    @endif

    @if($offer->status === 'inspection_accepted')
        <div class="alert alert-info small">The landlord accepted your inspection. Visit, then send your firm price below.</div>
    @endif

    <section class="contractor-panel contractor-history">
        <h2 class="h6 mb-2">History</h2>
        @if($offer->events->isNotEmpty())
            <ul class="list-unstyled mb-0">
                @foreach($offer->events->sortByDesc('id') as $event)
                    @continue($canQuote && in_array((string) $event->note, ['Another offer was accepted.', 'The landlord chose another contractor.'], true))
                    <li>{{ $event->created_at?->format('j M H:i') }} · {{ $event->note ?: str_replace('_', ' ', $event->to_status) }}</li>
                @endforeach
            </ul>
        @else
            <p class="small text-muted mb-0">Nothing has happened on this job yet.</p>
        @endif
    </section>

    </div>
    <div class="contractor-job-col">
    @if($lostToAnother)
        <div class="alert alert-info small">The landlord chose another contractor for this repair.</div>
    @endif
    @if($canQuote)
        <section class="contractor-panel">
            <h2 class="h6 mb-3">{{ $firmAfterInspection ? 'Your firm price' : ($offer->status === 'offer_sent' ? 'Update your offer' : 'Your offer') }}</h2>
            <form method="POST" action="{{ route('contractor.jobs.quote', $offer) }}" id="offer-form">
                @csrf
                <div class="job-price-types">
                    @foreach(\App\Models\RepairJobOffer::PRICING_TYPES as $key => $label)
                        @continue($firmAfterInspection && $key === 'inspection')
                        <label class="btn btn-sm {{ $type === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                            <input type="radio" class="d-none" name="pricing_type" value="{{ $key }}" @checked($type === $key)> {{ $label }}
                        </label>
                    @endforeach
                </div>

                <div class="job-price-grid">
                    <div data-for="fixed">
                        <label class="form-label small mb-1">Price £</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="amount" inputmode="decimal" value="{{ old('amount', $offer->pricing_type === 'fixed' ? (float) $offer->net_amount - (float) $offer->materials_estimate : '') }}">
                    </div>
                    <div data-for="callout_hourly">
                        <label class="form-label small mb-1">Call-out £</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="call_out_fee" inputmode="decimal" value="{{ old('call_out_fee', $offer->call_out_fee) }}">
                    </div>
                    <div data-for="hourly callout_hourly">
                        <label class="form-label small mb-1">Rate £/hr</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="hourly_rate" inputmode="decimal" value="{{ old('hourly_rate', $offer->hourly_rate) }}">
                    </div>
                    <div data-for="hourly callout_hourly">
                        <label class="form-label small mb-1">Est. hours</label>
                        <input class="form-control" type="number" step="0.25" min="0" name="estimated_hours" inputmode="decimal" value="{{ old('estimated_hours', $offer->estimated_hours) }}">
                    </div>
                    <div data-for="hourly callout_hourly">
                        <label class="form-label small mb-1">Cap £ (optional)</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="price_cap" inputmode="decimal" value="{{ old('price_cap', $offer->price_cap) }}">
                    </div>
                    <div data-for="inspection">
                        <label class="form-label small mb-1">Inspection fee £ (0 = free)</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="inspection_fee" inputmode="decimal" value="{{ old('inspection_fee', $offer->inspection_fee ?? 0) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <span class="small text-muted me-2">Materials</span>
                    @foreach(['included' => 'Included', 'excluded' => 'Extra', 'estimate' => 'Estimate'] as $key => $label)
                        <label class="me-2 small"><input type="radio" name="materials" value="{{ $key }}" @checked(old('materials', $offer->materials ?: 'included') === $key)> {{ $label }}</label>
                    @endforeach
                    <input class="form-control form-control-sm" style="width:120px" type="number" step="0.01" min="0" name="materials_estimate" placeholder="£ estimate" value="{{ old('materials_estimate', $offer->materials_estimate) }}" id="materials-estimate" @if(old('materials', $offer->materials ?: 'included') !== 'estimate') hidden @endif>
                </div>

                <div class="mb-3 small">
                    @if($vatRegistered)
                        VAT
                        <select name="vat_rate" class="form-select form-select-sm d-inline-block w-auto">
                            @foreach(['20' => '20%', '5' => '5% (e.g. energy saving)', '0' => '0%'] as $rate => $label)
                                <option value="{{ $rate }}" @selected((string) old('vat_rate', $offer->vat_rate !== null ? (int) $offer->vat_rate : 20) === (string) $rate)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <span class="text-muted">You’re not VAT registered, so no VAT is added.</span>
                    @endif
                </div>

                <div class="job-total">
                    <div>
                        <div class="small text-muted" id="total-label">Total the landlord sees</div>
                        <div class="small text-muted" id="total-detail"></div>
                    </div>
                    <strong id="total">£0.00</strong>
                </div>

                <div class="job-visit">
                    <div class="small text-muted mb-1">Visit</div>
                    <div class="job-visit-choices">
                        @if($tenantSlot)
                            <label class="btn btn-outline-primary btn-sm">
                                <input type="checkbox" name="visit_slots[]" value="{{ $tenantSlot->toIso8601String() }}" @checked(in_array($tenantSlot->toIso8601String(), (array) old('visit_slots', $offer->visit_slots ?? [$tenantSlot->toIso8601String()]), true))>
                                Tenant’s time: {{ $repair->tenantAvailabilityLabel() }}
                            </label>
                            <span class="small text-muted">or propose</span>
                        @endif
                        <input type="datetime-local" name="proposed_slot" class="form-control form-control-sm w-auto" value="{{ old('proposed_slot') }}">
                    </div>
                </div>

                <div class="job-offer-foot">
                    <div>
                        <label class="form-label small mb-1">Valid for</label>
                        <select name="valid_hours" class="form-select form-select-sm">
                            @foreach(['24' => '24 hours', '48' => '48 hours', '72' => '3 days', '168' => '7 days'] as $hours => $label)
                                <option value="{{ $hours }}" @selected((string) old('valid_hours', $validDefault) === (string) $hours)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small mb-1">Note (optional)</label>
                        <input name="offer_note" maxlength="2000" class="form-control form-control-sm" value="{{ old('offer_note', $offer->offer_note) }}" placeholder="E.g. parts in stock, can come today">
                    </div>
                </div>

                <button class="btn btn-primary w-100" type="submit">{{ $firmAfterInspection ? 'Send firm price' : ($offer->status === 'offer_sent' ? 'Update offer' : 'Send offer') }}</button>
            </form>
            @if($offer->isAwaitingResponse())
                <form method="POST" action="{{ route('contractor.jobs.decline', $offer) }}" class="job-decline">
                    @csrf
                    <span class="small text-muted me-1">Not for you?</span>
                    @foreach($declineReasons as $code => $label)
                        @continue($code === 'other')
                        <button class="btn btn-outline-secondary btn-sm" name="decline_code" value="{{ $code }}" type="submit">{{ $label }}</button>
                    @endforeach
                </form>
            @endif
        </section>
    @elseif($offer->isAwaitingResponse())
        <form method="POST" action="{{ route('contractor.jobs.decline', $offer) }}" class="job-decline">
            @csrf
            <span class="small text-muted me-1">Not for you?</span>
            @foreach($declineReasons as $code => $label)
                @continue($code === 'other')
                <button class="btn btn-outline-secondary btn-sm" name="decline_code" value="{{ $code }}" type="submit">{{ $label }}</button>
            @endforeach
        </form>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        @if(in_array($offer->status, ['offer_sent', 'inspection_accepted'], true))
            <details>
                <summary class="btn btn-outline-secondary btn-sm">Withdraw offer</summary>
                <form method="POST" action="{{ route('contractor.jobs.withdraw', $offer) }}" class="mt-2 d-flex gap-2">@csrf
                    <input name="withdraw_reason" required maxlength="500" class="form-control form-control-sm" placeholder="Reason">
                    <button class="btn btn-outline-danger btn-sm" type="submit">Withdraw</button>
                </form>
            </details>
        @endif
        @if(in_array($offer->status, ['job_accepted', 'inspection_accepted'], true) && ! $offer->isFinished())
            <details>
                <summary class="btn btn-outline-danger btn-sm">Cancel job</summary>
                <form method="POST" action="{{ route('contractor.jobs.cancel', $offer) }}" class="mt-2 d-flex gap-2">@csrf
                    <input name="reason" required maxlength="500" class="form-control form-control-sm" placeholder="Reason">
                    <button class="btn btn-danger btn-sm" type="submit">Cancel</button>
                </form>
                <div class="small text-muted mt-1">Cancellations show on your record.</div>
            </details>
        @endif
        @if($offer->status === 'job_accepted' && ! $offer->disputed_at)
            <details>
                <summary class="btn btn-link btn-sm text-muted">Report a problem</summary>
                <form method="POST" action="{{ route('contractor.jobs.dispute', $offer) }}" class="mt-2 d-flex gap-2">@csrf
                    <input name="reason" required maxlength="1000" class="form-control form-control-sm" placeholder="What’s wrong?">
                    <button class="btn btn-outline-danger btn-sm" type="submit">Send</button>
                </form>
            </details>
        @endif
    </div>
    </div>
    </div>
</div>

@if($canQuote)
<script>
(function () {
    const form = document.getElementById('offer-form');
    const num = name => parseFloat(form.querySelector('[name="' + name + '"]')?.value || 0) || 0;
    const money = v => '£' + v.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const vatSelect = form.querySelector('[name="vat_rate"]');

    function sync() {
        const type = form.querySelector('[name="pricing_type"]:checked').value;
        form.querySelectorAll('[name="pricing_type"]').forEach(r => {
            r.parentElement.classList.toggle('btn-primary', r.checked);
            r.parentElement.classList.toggle('btn-outline-secondary', !r.checked);
        });
        form.querySelectorAll('[data-for]').forEach(el => { el.hidden = !el.dataset.for.split(' ').includes(type); });
        const materials = form.querySelector('[name="materials"]:checked').value;
        document.getElementById('materials-estimate').hidden = materials !== 'estimate';

        let base = 0;
        if (type === 'fixed') base = num('amount');
        if (type === 'hourly') base = num('hourly_rate') * num('estimated_hours');
        if (type === 'callout_hourly') base = num('call_out_fee') + num('hourly_rate') * num('estimated_hours');
        if (type === 'inspection') base = num('inspection_fee');
        const net = base + (materials === 'estimate' ? num('materials_estimate') : 0);
        const rate = vatSelect ? parseFloat(vatSelect.value) : 0;
        const vat = Math.round(net * rate) / 100;
        const estimate = type === 'hourly' || type === 'callout_hourly' || materials === 'estimate';

        document.getElementById('total').textContent = money(net + vat) + (vatSelect ? ' inc VAT' : '');
        document.getElementById('total-label').textContent = type === 'inspection' ? 'Inspection fee the landlord sees' : (estimate ? 'Estimated total the landlord sees' : 'Total the landlord sees');
        const parts = [];
        if (vatSelect) parts.push(money(net) + ' + ' + money(vat) + ' VAT');
        if (num('price_cap') && (type === 'hourly' || type === 'callout_hourly')) parts.push('capped at ' + money(num('price_cap') * (1 + rate / 100)));
        if (materials === 'excluded') parts.push('materials extra');
        document.getElementById('total-detail').textContent = parts.join(' · ');
    }
    form.addEventListener('input', sync);
    form.addEventListener('change', sync);
    sync();
})();
</script>
@endif
@endsection
