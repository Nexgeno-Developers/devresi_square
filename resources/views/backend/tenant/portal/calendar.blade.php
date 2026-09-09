@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Calendar</p>
            <h1>Appointments</h1>
            <p>Viewings, inspections, contractor visits and move-in dates for your home. The office diary stays with your landlord.</p>
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
            <p class="tp-empty">No upcoming appointments. When your landlord books an inspection or move-in, it will show here.</p>
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
