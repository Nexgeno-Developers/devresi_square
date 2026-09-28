@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
@php
    $categories = $categories ?? collect();
@endphp
<div class="tenant-portal tp-maintenance" data-tenant-maintenance="1">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Maintenance</p>
            <h1>Something broken?</h1>
            <p>Photo, a short note, and when you can be in.</p>
        </div>
    </div>

    @if($canRaise && $tenancies->isNotEmpty())
        <form class="tp-card tp-report-form mb-3" method="POST" action="{{ route('tenant.maintenance.store') }}" enctype="multipart/form-data">
            @csrf
            @if($tenancies->count() > 1)
                <label class="tp-report-home">
                    <span class="tp-metric-label">Which home?</span>
                    <select name="property_id" class="form-control" required>
                        @foreach($tenancies as $tenancy)
                            @if($tenancy->property)
                                <option value="{{ $tenancy->property_id }}" @selected((string) old('property_id') === (string) $tenancy->property_id)>
                                    {{ $tenancy->property->full_address ?: $tenancy->property->prop_name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </label>
            @else
                <input type="hidden" name="property_id" value="{{ $tenancies->first()?->property_id }}">
            @endif

            <div class="tp-report-layout">
            <div class="tp-report-main">
            <div class="tp-report-top">
                <div class="tp-photo-field">
                    <label class="tp-photo-capture">
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" capture="environment" required>
                        <span class="tp-photo-capture-label">
                            <i class="bi bi-camera"></i>
                            Take or choose a photo
                        </span>
                    </label>
                    @error('photo')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <label class="tp-report-note">
                    <span class="tp-metric-label">What needs attention?</span>
                    <textarea name="description" class="form-control" rows="3" required maxlength="2000" placeholder="Leaking tap in the kitchen…">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </label>
            </div>

            @if($categories->isNotEmpty())
                <fieldset class="tp-report-area">
                    <legend class="tp-metric-label">Area</legend>
                    <div class="tp-chip-row">
                        @foreach($categories as $category)
                            <label class="tp-chip">
                                <input type="radio" name="repair_category_id" value="{{ $category->id }}" @checked((string) old('repair_category_id') === (string) $category->id)>
                                <span>{{ $category->name === 'Staging General Repair' ? 'General repair' : $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <div class="tp-report-grid">
                @if(!empty($priorityComplaints))
                    <fieldset class="tp-report-complaints" data-priority-complaints>
                        <legend class="tp-metric-label">Priority complaint</legend>
                        <div class="tp-complaint-grid">
                            @foreach($priorityComplaints as $complaint)
                                <label class="tp-chip {{ ($complaint['clock'] ?? '') === 'make_safe_24' ? 'is-24h' : 'is-72h' }}">
                                    <input type="radio" name="complaint_code" value="{{ $complaint['code'] }}"
                                        data-clock="{{ $complaint['clock_label'] }}"
                                        data-safety="{{ $complaint['safety_notice'] }}"
                                        @checked(old('complaint_code') === $complaint['code'])>
                                    <span><em>{{ ($complaint['clock'] ?? '') === 'make_safe_24' ? '24 hours' : '72 hours' }}</em>{{ $complaint['title'] }}</span>
                                </label>
                            @endforeach
                            <label class="tp-chip is-usual">
                                <input type="radio" name="complaint_code" value="" data-clock="" data-safety="" @checked(old('complaint_code', '') === '')>
                                <span><em>Usual repair</em>Something else</span>
                            </label>
                        </div>
                        <p class="tp-priority-clock mb-0" data-priority-clock hidden></p>
                        <p class="tp-safety-note" data-priority-safety hidden></p>
                    </fieldset>
                @endif
            </div>
            </div>

            <div class="tp-report-side">
                <fieldset class="tp-availability" data-availability
                    data-today="{{ now()->toDateString() }}"
                    data-until="{{ now()->addDays(60)->toDateString() }}"
                    data-selected="{{ old('tenant_availability') }}">
                    <legend class="tp-metric-label">When can someone visit?</legend>
                    <div class="tp-avail-nav">
                        <p class="tp-muted tp-avail-hint">Optional. Next 60 days.</p>
                        <div class="tp-avail-nav-buttons">
                            <button type="button" class="tp-btn tp-btn-ghost" data-avail-prev aria-label="Previous month">&larr;</button>
                            <strong data-avail-title></strong>
                            <button type="button" class="tp-btn tp-btn-ghost" data-avail-next aria-label="Next month">&rarr;</button>
                        </div>
                    </div>
                    <div class="tp-avail-weekdays" aria-hidden="true">
                        <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                    </div>
                    <div class="tp-avail-grid" data-avail-grid></div>
                    <div class="tp-slot-row" data-avail-slots>
                        <label class="tp-chip">
                            <input type="radio" name="availability_slot" value="09:00" data-avail-slot>
                            <span>Morning</span>
                        </label>
                        <label class="tp-chip">
                            <input type="radio" name="availability_slot" value="13:00" data-avail-slot>
                            <span>Afternoon</span>
                        </label>
                        <label class="tp-chip">
                            <input type="radio" name="availability_slot" value="17:00" data-avail-slot>
                            <span>Evening</span>
                        </label>
                    </div>
                    <div class="tp-avail-foot">
                        <p class="tp-muted tp-avail-summary" data-avail-summary>Pick a day, then a time.</p>
                        <button type="button" class="tp-avail-clear" data-avail-clear hidden>Clear</button>
                    </div>
                    <input type="hidden" name="tenant_availability" value="{{ old('tenant_availability') }}">
                    @error('tenant_availability')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </fieldset>
                <div class="tp-report-actions">
                    <button type="submit" class="tp-btn">Send request</button>
                    <details>
                        <summary class="tp-muted">More options</summary>
                        <label class="d-block mt-2">
                            Priority
                            <select name="priority" class="form-control">
                                <option value="medium" @selected(old('priority', 'medium') === 'medium')>Normal</option>
                                <option value="low" @selected(old('priority') === 'low')>Low</option>
                                <option value="high" @selected(old('priority') === 'high')>High</option>
                                <option value="critical" @selected(old('priority') === 'critical')>Urgent</option>
                            </select>
                        </label>
                        <label class="d-block mt-2">
                            Access details
                            <textarea name="access_details" class="form-control" rows="2" maxlength="2000" placeholder="Buzzer 4, or a key with the neighbour">{{ old('access_details') }}</textarea>
                        </label>
                    </details>
                </div>
            </div>
            </div>
        </form>
    @endif

    @php
        $openRepairs = $repairs->reject(fn ($repair) => in_array($repair->tenantStatusLabel(), ['Done', 'Cancelled'], true));
        $historyRepairs = $repairs->filter(fn ($repair) => in_array($repair->tenantStatusLabel(), ['Done', 'Cancelled'], true));
    @endphp
    <div class="tp-requests">
        <h2 class="tp-section-title">Open</h2>
        <div class="tp-request-list">
        @forelse($openRepairs->groupBy(fn ($repair) => (int) ($repair->property_id ?: 0)) as $rows)
            @php $groupAddress = $rows->first()->property?->line_1 ?: 'Repair'; @endphp
            <h3 class="h6 mt-3" data-address-group="{{ $groupAddress }}">{{ $groupAddress }}</h3>
            @foreach($rows as $repair)
            @include('backend.tenant.portal._repair-card', ['repair' => $repair, 'list' => 'open'])
            @endforeach
        @empty
            <div class="tp-card">
                <p class="tp-empty mb-0">No open repairs. Use the form above when something needs fixing.</p>
            </div>
        @endforelse
        </div>
        @if($historyRepairs->isNotEmpty())
            <h2 class="tp-section-title mt-4">History</h2>
            <div class="tp-request-list">
                @foreach($historyRepairs as $repair)
                    @include('backend.tenant.portal._repair-card', ['repair' => $repair, 'list' => 'history'])
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var complaints = document.querySelector('[data-priority-complaints]');
    if (complaints) {
        var clock = complaints.querySelector('[data-priority-clock]');
        var safety = complaints.querySelector('[data-priority-safety]');
        var priority = document.querySelector('select[name="priority"]');

        function sync() {
            var chosen = complaints.querySelector('input[name="complaint_code"]:checked');
            var isPriority = chosen && chosen.value !== '';
            if (clock) {
                clock.hidden = !isPriority;
                clock.textContent = isPriority ? (chosen.getAttribute('data-clock') || '') : '';
            }
            if (safety) {
                var notice = isPriority ? (chosen.getAttribute('data-safety') || '') : '';
                safety.hidden = notice === '';
                safety.textContent = notice;
            }
            if (priority) priority.disabled = !!isPriority;
        }

        complaints.addEventListener('change', sync);
        sync();
    }

    var availability = document.querySelector('[data-availability]');
    if (!availability) return;
    var today = availability.getAttribute('data-today');
    var until = availability.getAttribute('data-until');
    var hidden = availability.querySelector('input[name="tenant_availability"]');
    var title = availability.querySelector('[data-avail-title]');
    var grid = availability.querySelector('[data-avail-grid]');
    var slots = availability.querySelector('[data-avail-slots]');
    var summary = availability.querySelector('[data-avail-summary]');
    var clearBtn = availability.querySelector('[data-avail-clear]');
    var selected = (hidden.value || '').slice(0, 10);
    var selectedSlot = (hidden.value || '').slice(11, 16);
    var cursor = selected ? new Date(selected + 'T12:00:00') : new Date(today + 'T12:00:00');
    var slotNames = { '09:00': 'Morning', '13:00': 'Afternoon', '17:00': 'Evening' };

    function iso(date) {
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return date.getFullYear() + '-' + month + '-' + day;
    }

    function paint() {
        var year = cursor.getFullYear();
        var month = cursor.getMonth();
        title.textContent = cursor.toLocaleString(undefined, { month: 'long', year: 'numeric' });
        grid.innerHTML = '';
        var first = new Date(year, month, 1);
        var lead = (first.getDay() + 6) % 7;
        var days = new Date(year, month + 1, 0).getDate();
        for (var i = 0; i < lead; i++) {
            var pad = document.createElement('span');
            grid.appendChild(pad);
        }
        for (var day = 1; day <= days; day++) {
            var date = new Date(year, month, day);
            var key = iso(date);
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'tp-avail-day' + (key === today ? ' is-today' : '') + (key === selected ? ' is-selected' : '');
            button.textContent = String(day);
            button.disabled = key < today || key > until;
            button.setAttribute('aria-pressed', key === selected ? 'true' : 'false');
            button.setAttribute('aria-label', key);
            button.addEventListener('click', function (event) {
                selected = event.currentTarget.getAttribute('aria-label');
                write();
            });
            grid.appendChild(button);
        }
        var monthKey = year + '-' + String(month + 1).padStart(2, '0');
        availability.querySelector('[data-avail-prev]').disabled = monthKey <= today.slice(0, 7);
        availability.querySelector('[data-avail-next]').disabled = monthKey >= until.slice(0, 7);
        slots.hidden = false;
        availability.querySelectorAll('[data-avail-slot]').forEach(function (input) {
            input.checked = input.value === selectedSlot;
        });
        if (selected && selectedSlot && slotNames[selectedSlot]) {
            var shown = new Date(selected + 'T12:00:00');
            summary.textContent = slotNames[selectedSlot] + ', ' + shown.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
        } else if (selected) {
            summary.textContent = 'Choose morning, afternoon, or evening.';
        } else if (selectedSlot) {
            summary.textContent = 'Choose a day.';
        } else {
            summary.textContent = 'Pick a day, then a time.';
        }
        clearBtn.hidden = selected === '' && selectedSlot === '';
    }

    function write() {
        hidden.value = selected && selectedSlot ? selected + 'T' + selectedSlot : '';
        paint();
    }

    availability.querySelector('[data-avail-prev]').addEventListener('click', function () {
        cursor = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1, 12);
        paint();
    });
    availability.querySelector('[data-avail-next]').addEventListener('click', function () {
        cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1, 12);
        paint();
    });
    availability.querySelectorAll('[data-avail-slot]').forEach(function (input) {
        input.addEventListener('change', function () {
            selectedSlot = input.value;
            write();
        });
    });
    clearBtn.addEventListener('click', function () {
        selected = '';
        selectedSlot = '';
        hidden.value = '';
        paint();
    });
    paint();
});
</script>
@endpush
