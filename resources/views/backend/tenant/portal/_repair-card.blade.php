@php
    $story = app(\App\Services\Portal\TenantRepairStory::class)->for($repair);
    $label = $repair->tenantStatusLabel();
    $pillClass = match ($label) {
        'Done' => 'is-active',
        'In progress' => 'is-warn',
        'Cancelled' => '',
        default => 'is-open',
    };
    $category = $story['category'] === 'Staging General Repair' ? 'General repair' : $story['category'];
    $title = \Illuminate\Support\Str::limit($story['title'], 72);
    $dialogId = 'repair-dialog-'.$repair->id;
    $photoIds = collect($repair->repairPhotos)
        ->flatMap(fn ($photo) => array_filter(array_map('trim', explode(',', (string) $photo->photos))))
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->unique()
        ->values();
    $contractor = $story['offer']?->contractor?->contractorProfile?->displayName()
        ?: ($story['offer']?->contractor?->first_name ?: $story['offer']?->contractor?->name);
@endphp
<div class="tp-repair-unit">
    <button type="button" class="tp-repair-card" data-open-repair="{{ $dialogId }}" data-repair-{{ $list }}="{{ $repair->id }}" data-repair-home="{{ $repair->property?->line_1 }}">
        <span class="tp-repair-kicker">
            <span class="tp-pill {{ $pillClass }}">{{ $label }}</span>
            <span>{{ $category }}</span>
        </span>
        <span class="tp-repair-title">{{ $title }}</span>
        @if($repair->isPriorityComplaint() && filled($repair->classification_snapshot['tenant_phrase'] ?? null))
            <span class="tp-repair-note">{{ $repair->classification_snapshot['tenant_phrase'] }}</span>
        @endif
        <span class="tp-repair-visit is-{{ $story['visit_tone'] }}">{{ $story['visit'] }}</span>
        <span class="tp-repair-foot">Reported {{ $story['reported'] }} · Open details</span>
    </button>

    <dialog id="{{ $dialogId }}" class="tp-repair-dialog">
        <div class="tp-repair-dialog-bar">
            <div>
                <p class="tp-kicker">{{ $category }}</p>
                <h2>{{ $story['title'] }}</h2>
            </div>
            <form method="dialog"><button type="submit" class="tp-btn tp-btn-ghost" aria-label="Close">Close</button></form>
        </div>
        <div class="tp-repair-dialog-body">
            <p class="tp-repair-visit is-{{ $story['visit_tone'] }}">{{ $story['visit'] }}</p>
            <dl class="tp-repair-facts">
                <div>
                    <dt>Reported</dt>
                    <dd>{{ $story['reporter'] }} · {{ $story['reported'] }}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>{{ $label }}@if($repair->reference_number) · {{ $repair->reference_number }}@endif</dd>
                </div>
                <div>
                    <dt>When you can be in</dt>
                    <dd>{{ $story['availability_note'] }}</dd>
                </div>
                @if($contractor && $story['offer'] && in_array($story['offer']->status, [\App\Models\RepairJobOffer::JOB_ACCEPTED, \App\Models\RepairJobOffer::INSPECTION_ACCEPTED], true))
                    <div>
                        <dt>Contractor</dt>
                        <dd>{{ $contractor }}</dd>
                    </div>
                @endif
                @if(filled($repair->access_details))
                    <div>
                        <dt>Access</dt>
                        <dd>{{ $repair->access_details }}</dd>
                    </div>
                @endif
                @if(filled($repair->landlord_note))
                    <div>
                        <dt>From your landlord</dt>
                        <dd>{{ $repair->landlord_note }}</dd>
                    </div>
                @endif
            </dl>

            @if($repair->isPriorityComplaint() && filled($repair->classification_snapshot['tenant_phrase'] ?? null))
                <p class="tp-safety-note">{{ $repair->classification_snapshot['tenant_phrase'] }}</p>
            @endif

            @if($story['needs_confirm'] && $story['when'])
                <section class="tp-visit-choice">
                    <h3>Does {{ $story['when']->format('D j M, H:i') }} work?</h3>
                    <p>The visit cannot go ahead until you confirm this time, or offer another.</p>
                    <form method="POST" action="{{ route('tenant.maintenance.visit', $repair) }}">
                        @csrf
                        <input type="hidden" name="action" value="confirm">
                        <button type="submit" class="tp-btn">This time works</button>
                    </form>
                    <form method="POST" action="{{ route('tenant.maintenance.visit', $repair) }}" class="tp-visit-other">
                        @csrf
                        <input type="hidden" name="action" value="reschedule">
                        <label>
                            <span>Another day</span>
                            <input type="date" name="date" required min="{{ now()->toDateString() }}" max="{{ now()->addDays(60)->toDateString() }}">
                        </label>
                        <fieldset>
                            <legend>Time</legend>
                            <label class="tp-chip"><input type="radio" name="slot" value="09:00" required><span>Morning</span></label>
                            <label class="tp-chip"><input type="radio" name="slot" value="13:00"><span>Afternoon</span></label>
                            <label class="tp-chip"><input type="radio" name="slot" value="17:00"><span>Evening</span></label>
                        </fieldset>
                        <label>
                            <span>Note (optional)</span>
                            <input type="text" name="note" maxlength="500" placeholder="I’m at work until 4">
                        </label>
                        <button type="submit" class="tp-btn tp-btn-ghost">Offer these times</button>
                    </form>
                </section>
            @endif

            @if($photoIds->isNotEmpty())
                <div class="tp-repair-photos">
                    @foreach($photoIds as $photoId)
                        <img src="{{ uploaded_asset($photoId) }}" alt="Photo of the repair">
                    @endforeach
                </div>
            @endif

            <h3 class="tp-repair-activity-title">What has happened</h3>
            <ol class="tp-repair-activity">
                @foreach($story['activity'] as $event)
                    <li>
                        <span class="tp-repair-activity-actor">{{ $event['actor'] }}</span>
                        <span>{{ $event['text'] }}</span>
                        @if($event['at'])
                            <time datetime="{{ $event['at']->toIso8601String() }}">{{ $event['at']->format('j M Y, H:i') }}</time>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </dialog>
</div>
