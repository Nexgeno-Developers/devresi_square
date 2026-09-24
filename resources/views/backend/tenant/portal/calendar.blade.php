@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal" data-tenant-calendar="1">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Calendar</p>
            <h1>Appointments</h1>
            <p>Inspections and visits your landlord has shared with you.</p>
        </div>
    </div>

    <div class="tp-card tp-month-cal mb-3">
        <div class="tp-month-cal-nav">
            <a class="tp-btn tp-btn-ghost" href="{{ route('tenant.calendar', ['month' => $prevMonth]) }}" aria-label="Previous month">&larr;</a>
            <h2 class="mb-0">{{ $monthCursor->format('F Y') }}</h2>
            <a class="tp-btn tp-btn-ghost" href="{{ route('tenant.calendar', ['month' => $nextMonth]) }}" aria-label="Next month">&rarr;</a>
        </div>

        @if(! $hasAnyEvents)
            <p class="tp-empty tp-cal-empty">No visits on your calendar yet. When your landlord books an inspection or visit, it will appear on the days below.</p>
        @endif

        <div class="tp-month-cal-weekdays" aria-hidden="true">
            <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
        </div>
        <div class="tp-month-cal-grid">
            @foreach($calendarDays as $cell)
                <div class="tp-month-cal-day{{ $cell['in_month'] ? '' : ' is-outside' }}{{ $cell['is_today'] ? ' is-today' : '' }}{{ ! empty($cell['events']) ? ' has-events' : '' }}">
                    <span class="tp-month-cal-date">{{ $cell['date']->format('j') }}</span>
                    @foreach(array_slice($cell['events'], 0, 2) as $event)
                        <span class="tp-month-cal-event" data-cal-event="{{ $event->id }}" title="{{ $event->title }} · {{ $event->start_datetime?->format('H:i') }}">
                            {{ $event->start_datetime?->format('H:i') }} {{ \Illuminate\Support\Str::limit($event->title, 18) }}
                        </span>
                    @endforeach
                    @if(count($cell['events']) > 2)
                        <span class="tp-muted small">+{{ count($cell['events']) - 2 }} more</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="tp-card mb-3">
        <h2>Upcoming</h2>
        @forelse($upcoming as $event)
            <div class="tp-row">
                <div>
                    <p>{{ $event->title }}</p>
                    <p class="tp-muted mb-0">
                        {{ $event->start_datetime?->format('d M Y, H:i') }}
                        @if($event->type?->name)
                            · {{ $event->type->name }}
                        @endif
                    </p>
                </div>
                <span class="tp-pill">{{ $event->status ?: 'Scheduled' }}</span>
            </div>
        @empty
            <p class="tp-empty mb-0">Nothing upcoming this period.</p>
        @endforelse
    </div>

    @if($past->isNotEmpty())
        <div class="tp-card">
            <h2>Past</h2>
            @foreach($past as $event)
                <div class="tp-row">
                    <div>
                        <p>{{ $event->title }}</p>
                        <p class="tp-muted mb-0">{{ $event->start_datetime?->format('d M Y, H:i') }}</p>
                    </div>
                    <span class="tp-pill">{{ $event->status ?: 'Scheduled' }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
