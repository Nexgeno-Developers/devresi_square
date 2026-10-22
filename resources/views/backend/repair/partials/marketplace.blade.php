@php
    $marketplace = app(\App\Services\Contractors\RepairMarketplacePanel::class)->for($repairIssue, request('offer_sort', 'price'));
    $pricing = app(\App\Services\Contractors\RepairJobOfferService::class);
    $contractorRequest = $marketplace['request'];
    $awarded = $marketplace['awarded'];
    $compare = $marketplace['compare'];
    $waiting = $marketplace['waiting'];
    $past = $marketplace['past'];
    $performance = $marketplace['performance'];
    $closed = in_array($repairIssue->status, ['Closed', 'Invoice Paid', 'Cancelled', 'Rejected'], true);
    $open = $contractorRequest && in_array($contractorRequest->status, ['sent', 'awaiting_contractor'], true);
    $awardedProfile = $awarded?->contractor?->contractorProfile;
    $awardedBlock = $awardedProfile ? \App\Support\ContractorCompliance::blockReason($awardedProfile) : null;
    $aftercare = app(\App\Services\Contractors\RepairJobAftercareService::class);
    foreach ($compare->concat(array_filter([$awarded])) as $threadOffer) {
        $aftercare->markRead($threadOffer, \App\Models\RepairJobMessage::LANDLORD);
    }
    $pendingVariations = $awarded ? $awarded->variations->where('status', 'pending') : collect();
    $approvedVariations = $awarded ? $awarded->variations->where('status', 'approved') : collect();
    $visitBooked = $awarded && $awarded->scheduled_for && ! $awarded->isFinished();
@endphp
<style>
    .job-stars{display:inline-flex;flex-direction:row-reverse;gap:.1rem}
    .job-stars input{position:absolute;opacity:0;pointer-events:none}
    .job-stars label{font-size:1.4rem;line-height:1;color:#cfd4dc;cursor:pointer;transition:color .15s,transform .15s}
    .job-stars label:hover,.job-stars label:hover~label,.job-stars input:checked~label{color:#f5a623}
    .job-stars label:hover{transform:scale(1.15)}
    .job-stars input:focus-visible+label{outline:2px solid #0d6efd;outline-offset:2px;border-radius:3px}
</style>
<div class="card lw-card mb-3" id="contractors">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div class="lw-section-title m-0">Contractor</div>
            @if($contractorRequest)
                <span class="d-flex gap-1 flex-wrap">
                    <x-lw.pill :tone="$contractorRequest->isEmergency() ? 'bad' : 'idle'">{{ \App\Models\RepairContractorRequest::URGENCIES[$contractorRequest->urgency] ?? 'Routine' }}</x-lw.pill>
                    <x-lw.pill :tone="$awarded ? 'ok' : 'warn'">{{ $contractorRequest->statusLabel() }}</x-lw.pill>
                </span>
            @endif
        </div>

        @if($awarded)
            <p class="mb-1"><strong>{{ $awardedProfile?->displayName() ?: 'Contractor' }}</strong> · {{ $awarded->statusLabel() }}</p>
            <p class="mb-1 small">{{ $pricing->priceSummary($awarded) }}</p>
            @if($approvedVariations->isNotEmpty())
                <p class="mb-1 small">
                    Agreed extras: @foreach($approvedVariations as $variation)£{{ number_format((float) $variation->gross_amount, 2) }} ({{ \Illuminate\Support\Str::limit($variation->reason, 60) }})@if(! $loop->last), @endif @endforeach
                    · <strong>total £{{ number_format($awarded->finalGross(), 2) }}</strong>
                </p>
            @endif
            @if($awarded->scheduled_for)
                <p class="mb-1 small">Visit {{ $awarded->scheduled_for->format('D j M, H:i') }} @if($awarded->tenant_confirmed_at)· tenant confirmed @endif</p>
            @endif
            @if($awarded->reschedule_requested_at)
                <div class="alert alert-info small my-2">
                    {{ $awarded->reschedule_requested_by === 'tenant' ? 'The tenant' : 'You' }} asked to move the visit to
                    {{ collect($awarded->reschedule_slots ?? [])->map(fn ($s) => \Illuminate\Support\Carbon::parse($s)->format('D j M, H:i'))->implode(' or ') }}.
                    Waiting for the contractor to pick a time.
                </div>
            @endif

            @foreach($pendingVariations as $variation)
                <div class="border rounded p-2 my-2 bg-warning-subtle">
                    <div class="small"><strong>Extra work requested:</strong> £{{ number_format((float) $variation->gross_amount, 2) }}
                        @if((float) $variation->vat_amount > 0)<span class="text-muted">(£{{ number_format((float) $variation->net_amount, 2) }} + £{{ number_format((float) $variation->vat_amount, 2) }} VAT)</span>@endif
                    </div>
                    <div class="small mb-2">“{{ $variation->reason }}”</div>
                    <form method="POST" action="{{ route('admin.property_repairs.job_offers.variation', [$awarded, $variation]) }}" class="d-flex flex-wrap gap-2 align-items-center">
                        @csrf
                        <input name="note" maxlength="500" class="form-control form-control-sm" style="max-width:260px" placeholder="Note to contractor (optional)">
                        <button class="btn btn-primary btn-sm" name="decision" value="approve" type="submit">Approve £{{ number_format((float) $variation->gross_amount, 2) }}</button>
                        <button class="btn btn-outline-secondary btn-sm" name="decision" value="decline" type="submit">Decline</button>
                    </form>
                </div>
            @endforeach

            @if($awarded->completion_note)
                <p class="mb-1 small text-muted">Contractor note: {{ $awarded->completion_note }}</p>
            @endif
            @if(! empty($awarded->completion_photos))
                <div class="d-flex flex-wrap gap-2 my-2">
                    @foreach($awarded->completion_photos as $index => $path)
                        <a href="{{ route('admin.property_repairs.job_offers.completion_photo', [$awarded, $index]) }}" target="_blank" rel="noopener">
                            <img src="{{ route('admin.property_repairs.job_offers.completion_photo', [$awarded, $index]) }}" alt="Completion photo {{ $loop->iteration }}" style="width:80px;height:80px;object-fit:cover" class="rounded border">
                        </a>
                    @endforeach
                </div>
            @endif

            @if($awarded->invoice)
                <p class="mb-1 small">
                    Invoice {{ $awarded->invoice->number }} · £{{ number_format((float) $awarded->invoice->gross_amount, 2) }} ·
                    <x-lw.pill :tone="$awarded->invoice->status === 'paid' ? 'ok' : ($awarded->invoice->isOverdue() ? 'bad' : 'warn')">{{ $awarded->invoice->statusLabel() }}</x-lw.pill>
                    <a href="{{ route('admin.property_repairs.job_offers.invoice', $awarded) }}" class="ms-1">View invoice</a>
                </p>
            @endif

            @if($awarded->rating)
                <p class="mb-1 small">You rated this job {{ str_repeat('★', (int) $awarded->rating) }}{{ str_repeat('☆', 5 - (int) $awarded->rating) }}@if($awarded->rating_comment) · “{{ $awarded->rating_comment }}”@endif</p>
            @elseif(in_array($awarded->job_status, ['confirmed', 'invoiced'], true))
                <form method="POST" action="{{ route('admin.property_repairs.job_offers.rate', $awarded) }}" class="border rounded p-2 my-2">
                    @csrf
                    <div class="small mb-1">How did {{ $awardedProfile?->displayName() ?: 'the contractor' }} do?</div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="job-stars">
                            @for($star = 5; $star >= 1; $star--)
                                <input type="radio" name="rating" value="{{ $star }}" id="rate-{{ $star }}" required><label for="rate-{{ $star }}" title="{{ $star }} {{ \Illuminate\Support\Str::plural('star', $star) }}">★</label>
                            @endfor
                        </span>
                        <input name="rating_comment" maxlength="1000" class="form-control form-control-sm" style="max-width:320px" placeholder="Comment (optional, only our team sees it)">
                        <button class="btn btn-primary btn-sm" type="submit">Rate</button>
                    </div>
                </form>
            @endif
            @if($awardedBlock)
                <div class="alert alert-warning small my-2">{{ $awardedBlock }} The job is not cancelled automatically.</div>
            @endif
            @if($awarded->disputed_at)
                <div class="alert alert-danger small my-2">Disputed: {{ $awarded->dispute_reason }}. Our team is looking at it.</div>
            @endif
            <div class="d-flex flex-wrap gap-2 mt-2">
                @if($awarded->job_status === 'completed')
                    <form method="POST" action="{{ route('admin.property_repairs.job_offers.complete', $awarded) }}">@csrf
                        <button class="btn btn-primary btn-sm" type="submit">Confirm it’s done</button>
                    </form>
                @endif
                @if($visitBooked && ! $awarded->reschedule_requested_at)
                    <details>
                        <summary class="btn btn-outline-secondary btn-sm">Move the visit</summary>
                        <form method="POST" action="{{ route('admin.property_repairs.job_offers.reschedule', $awarded) }}" class="mt-2">@csrf
                            <div class="small text-muted mb-1">Offer up to three times. The contractor picks one.</div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                @for($i = 0; $i < 3; $i++)
                                    <input type="datetime-local" name="slots[]" class="form-control form-control-sm w-auto" min="{{ now()->format('Y-m-d\TH:i') }}" @if($i === 0) required @endif>
                                @endfor
                            </div>
                            <div class="d-flex gap-2">
                                <input name="note" maxlength="500" class="form-control form-control-sm" placeholder="Note (optional)">
                                <button class="btn btn-primary btn-sm" type="submit">Send</button>
                            </div>
                        </form>
                    </details>
                @endif
                @unless($awarded->isFinished())
                    <details>
                        <summary class="btn btn-outline-danger btn-sm">Cancel job</summary>
                        <form method="POST" action="{{ route('admin.property_repairs.job_offers.cancel', $awarded) }}" class="mt-2 d-flex gap-2">@csrf
                            <input name="reason" required maxlength="500" class="form-control form-control-sm" placeholder="Reason">
                            <button class="btn btn-danger btn-sm" type="submit">Cancel</button>
                        </form>
                        <div class="small text-muted mt-1">If the contractor has already attended, a call-out charge may apply.</div>
                    </details>
                @endunless
                @unless($awarded->disputed_at)
                    <details>
                        <summary class="btn lw-btn-ghost btn-sm">Report a problem</summary>
                        <form method="POST" action="{{ route('admin.property_repairs.job_offers.dispute', $awarded) }}" class="mt-2 d-flex gap-2">@csrf
                            <input name="reason" required maxlength="1000" class="form-control form-control-sm" placeholder="What went wrong?">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Send</button>
                        </form>
                    </details>
                @endunless
            </div>
            @include('backend.repair.partials.job-thread', [
                'offer' => $awarded,
                'side' => \App\Models\RepairJobMessage::LANDLORD,
                'action' => route('admin.property_repairs.job_offers.message', $awarded),
                'class' => 'mt-3 pt-2 border-top',
            ])
        @elseif($closed)
            <p class="mb-0 text-muted">This repair is closed.</p>
        @else
            @if($contractorRequest?->status === 'awaiting_contractor')
                <div class="alert alert-warning small">The contractor cancelled. Send the repair again.</div>
            @endif
            @if($contractorRequest && $open)
                <p class="small text-muted mb-2">
                    Sent {{ $contractorRequest->first_sent_at?->diffForHumans() }} · replies due {{ $contractorRequest->respond_by?->format('D j M, H:i') }}
                    @if($contractorRequest->availability_to_arrange) · visit time to be arranged @endif
                </p>
            @endif
            @php
                $deskReady = \Illuminate\Support\Facades\Schema::hasTable('repair_desk_requests');
                $desk = $deskReady ? app(\App\Services\Repairs\RepairDeskService::class)->openFor($repairIssue) : null;
                $marketplaceOpen = $contractorRequest && $open;
            @endphp
            @if($desk)
                <div class="alert alert-info small mb-2">
                    With the Resisquare team · {{ $desk->statusLabel() }}
                    @if($desk->landlord_note)<div>{{ $desk->landlord_note }}</div>@endif
                    @if($desk->team_note)<div>Team: {{ $desk->team_note }}</div>@endif
                </div>
                @if($desk->status === \App\Models\RepairDeskRequest::REQUESTED)
                    <form method="POST" action="{{ route('admin.property_repairs.desk.withdraw', $repairIssue) }}">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Take it back</button>
                    </form>
                @endif
            @else
                <div class="d-flex flex-wrap gap-2 align-items-start">
                    <a class="btn btn-primary btn-sm" href="{{ route('admin.property_repairs.marketplace.find', $repairIssue) }}">
                        {{ $marketplaceOpen ? 'Add more contractors' : ($contractorRequest ? 'Send again' : 'Find a contractor') }}
                    </a>
                    @unless($marketplaceOpen || ! $deskReady)
                        <form method="POST" action="{{ route('admin.property_repairs.desk.store', $repairIssue) }}" class="d-flex flex-wrap gap-2">
                            @csrf
                            <input name="note" maxlength="1000" class="form-control form-control-sm" style="max-width:280px" placeholder="Note for the Resisquare team (optional)">
                            <button class="btn btn-outline-primary btn-sm" type="submit">Send to Resisquare</button>
                        </form>
                    @endunless
                </div>
                @if($marketplaceOpen)
                    <p class="small text-muted mt-2 mb-0">Contractors have been asked. Close that request before sending this repair to Resisquare.</p>
                @endif
            @endif
        @endif
    </div>
</div>

@if($compare->isNotEmpty() && ! $awarded)
    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="lw-section-title m-0">Offers</div>
                <span class="small">
                    Sort:
                    @foreach(['price' => 'Price', 'soonest' => 'Soonest visit', 'newest' => 'Newest'] as $key => $label)
                        <a href="{{ request()->fullUrlWithQuery(['offer_sort' => $key]) }}#contractors" class="{{ $marketplace['sort'] === $key ? 'fw-semibold' : 'text-muted' }}">{{ $label }}</a>@if(! $loop->last) · @endif
                    @endforeach
                </span>
            </div>
            <div class="row g-2">
                @foreach($compare as $offer)
                    @php
                        $profile = $offer->contractor?->contractorProfile;
                        $stats = $performance[$offer->contractor_user_id] ?? null;
                        $slot = $offer->earliestSlot();
                    @endphp
                    <div class="col-md-6 col-xl-4">
                        <div class="border rounded p-3 h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $profile?->displayName() ?: 'Contractor' }}</strong>
                                <x-lw.pill :tone="$offer->status === 'offer_expired' ? 'idle' : 'warn'">{{ $offer->statusLabel() }}</x-lw.pill>
                            </div>
                            <div class="fs-5 fw-semibold mt-1">£{{ number_format((float) $offer->gross_amount, 2) }}</div>
                            <div class="small">{{ $pricing->priceSummary($offer) }}</div>
                            <div class="small text-muted mt-1">
                                Visit {{ $slot ? $slot->format('D j M, H:i') : 'to be arranged' }}
                                @if($offer->status === 'offer_sent') · valid until {{ optional($offer->offer_expires_at)->format('D j M, H:i') }} @endif
                            </div>
                            @if($offer->details_changed_at)
                                <div class="small text-warning">Sent before you changed the details</div>
                            @endif
                            @if($offer->offer_note)
                                <div class="small mt-1">“{{ $offer->offer_note }}”</div>
                            @endif
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @foreach($profile ? \App\Support\ContractorCompliance::badges($profile) : [] as $badge)
                                    <span class="badge text-bg-light">{{ $badge }}</span>
                                @endforeach
                                @if($stats && $stats['rate'] !== null)
                                    <span class="badge text-bg-light">Responds {{ $stats['rate'] }}%</span>
                                @endif
                                @if(($stats['rating'] ?? null) !== null)
                                    <span class="badge text-bg-light">{{ $stats['rating'] }}★ ({{ $stats['ratings'] }})</span>
                                @endif
                            </div>
                            @include('backend.repair.partials.job-thread', [
                                'offer' => $offer,
                                'side' => \App\Models\RepairJobMessage::LANDLORD,
                                'action' => route('admin.property_repairs.job_offers.message', $offer),
                                'class' => 'mt-2',
                            ])
                            <div class="d-flex gap-2 mt-auto pt-2">
                                @if($offer->status === 'offer_sent')
                                    <a class="btn btn-primary btn-sm" href="{{ route('admin.property_repairs.job_offers.confirm', $offer) }}">{{ $offer->pricing_type === 'inspection' ? 'Accept inspection' : 'Accept' }}</a>
                                @elseif($offer->status === 'offer_expired' && ! $offer->reconfirm_requested_at)
                                    <form method="POST" action="{{ route('admin.property_repairs.job_offers.reconfirm', $offer) }}">@csrf
                                        <button class="btn btn-outline-primary btn-sm" type="submit">Ask to reconfirm</button>
                                    </form>
                                @elseif($offer->status === 'inspection_accepted')
                                    <span class="small text-muted">Waiting for the firm price after the inspection</span>
                                @else
                                    <span class="small text-muted">Waiting for the contractor to reconfirm</span>
                                @endif
                                <form method="POST" action="{{ route('admin.property_repairs.job_offers.decline', $offer) }}">@csrf
                                    <button class="btn lw-btn-ghost btn-sm" type="submit">Decline</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

@if(($waiting->isNotEmpty() || $past->isNotEmpty() || ($open && ! $awarded)) && $contractorRequest)
    <div class="card lw-card mb-3">
        <div class="card-body">
            @if($waiting->isNotEmpty())
                <div class="lw-section-title mt-0">Waiting for a reply</div>
                <ul class="list-unstyled mb-3">
                    @foreach($waiting as $offer)
                        <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <span>
                                {{ $offer->contractor?->contractorProfile?->displayName() ?: 'Contractor' }}
                                <span class="small text-muted">· {{ $offer->statusLabel() }} · due {{ optional($offer->invite_expires_at)->diffForHumans() }}</span>
                            </span>
                            <form method="POST" action="{{ route('admin.property_repairs.job_offers.withdraw', $offer) }}">@csrf
                                <button class="btn btn-link btn-sm text-muted" type="submit">Withdraw</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if($past->isNotEmpty())
                <details class="mb-2">
                    <summary class="small text-muted">{{ $past->count() }} closed {{ \Illuminate\Support\Str::plural('reply', $past->count()) }}</summary>
                    <ul class="list-unstyled small mt-2 mb-0">
                        @foreach($past as $offer)
                            <li>{{ $offer->contractor?->contractorProfile?->displayName() ?: 'Contractor' }} · {{ $offer->statusLabel() }}@if($offer->decline_reason) · {{ $offer->decline_reason }}@endif @if($offer->withdraw_reason) · {{ $offer->withdraw_reason }}@endif</li>
                        @endforeach
                    </ul>
                </details>
            @endif
            @if($open && ! $awarded)
                <div class="d-flex flex-wrap gap-2">
                    @if($compare->isNotEmpty())
                        <form method="POST" action="{{ route('admin.property_repairs.marketplace.close', $repairIssue) }}">@csrf
                            <input type="hidden" name="outcome" value="closed">
                            <button class="btn btn-outline-secondary btn-sm" type="submit" onclick="return confirm('Reject every offer? You can send the repair to others afterwards.')">Reject all offers</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.property_repairs.marketplace.close', $repairIssue) }}">@csrf
                        <input type="hidden" name="outcome" value="cancelled">
                        <button class="btn btn-link btn-sm text-danger" type="submit" onclick="return confirm('Cancel this request? Every invited contractor will be told.')">Cancel request</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endif
