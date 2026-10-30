@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page staff-calendar-page">
    <div class="staff-calendar-hero">
        <div class="staff-calendar-hero-copy">
            <p class="staff-calendar-kicker">Schedule</p>
            <h4>Calendar</h4>
            <p>Visits, inspections, viewings and appointments across your portfolio.</p>
        </div>
        <div class="staff-calendar-hero-actions">
            <ul class="staff-calendar-legend" aria-label="Event status">
                <li><span class="is-confirmed"></span>Confirmed</li>
                <li><span class="is-scheduled"></span>Scheduled</li>
                <li><span class="is-pending"></span>Pending</li>
                <li><span class="is-rescheduled"></span>Rescheduled</li>
            </ul>
            <button type="button" class="staff-calendar-add" onclick="window.openLandlordCalendarEvent && window.openLandlordCalendarEvent()">Add Event</button>
        </div>
    </div>
    <div class="staff-calendar-stage">
        @include('backend.partials.calendar')
    </div>
</div>

<style>
.staff-calendar-page {
    --cal-ink: #0f173a;
    --cal-muted: #5b6573;
    --cal-line: #e8edf3;
    --cal-wash: #f6f7fb;
    --cal-orange: #ff5c1d;
    --cal-orange-soft: #ffefe8;
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    padding-bottom: 0.75rem;
    box-sizing: border-box;
}
body #wrapper .staff-calendar-page .staff-calendar-hero {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 0.75rem;
    padding: 0.9rem 1.1rem;
    background: #fff;
    color: var(--cal-ink);
    border: 1px solid var(--cal-line);
    border-radius: 16px;
    box-shadow: 0 10px 28px rgba(15, 23, 58, 0.05);
}
body #wrapper .staff-calendar-page .staff-calendar-hero h4,
body #wrapper .staff-calendar-page .staff-calendar-hero p {
    color: var(--cal-ink);
}
.staff-calendar-kicker {
    margin: 0 0 0.15rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--cal-orange) !important;
}
body #wrapper .staff-calendar-page .staff-calendar-hero h4 {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: -0.02em;
}
body #wrapper .staff-calendar-page .staff-calendar-hero-copy p:last-child {
    margin: 0.15rem 0 0;
    color: var(--cal-muted) !important;
    font-size: 0.92rem;
}
.staff-calendar-hero-actions {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    flex-wrap: wrap;
}
.staff-calendar-legend {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
    padding: 0;
    list-style: none;
    color: var(--cal-muted);
    font-size: 0.75rem;
    font-weight: 650;
}
.staff-calendar-legend li {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.staff-calendar-legend span {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 99px;
}
.staff-calendar-legend .is-confirmed { background: #0f6b3c; }
.staff-calendar-legend .is-scheduled { background: var(--cal-ink); }
.staff-calendar-legend .is-pending { background: #c2410c; }
.staff-calendar-legend .is-rescheduled { background: #6d28d9; }
body #wrapper .staff-calendar-add {
    border: 0;
    border-radius: 999px;
    background: var(--cal-orange);
    color: #fff;
    font-weight: 700;
    letter-spacing: 0.01em;
    padding: 0.55rem 1rem;
    box-shadow: 0 8px 16px rgba(255, 92, 29, 0.28);
    transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
}
body #wrapper .staff-calendar-add:hover,
body #wrapper .staff-calendar-add:focus {
    background: #e84e12;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 12px 22px rgba(255, 92, 29, 0.34);
}
.staff-calendar-stage {
    flex: 1 1 auto;
    min-height: 0;
}
#calendar {
    height: 100%;
    background: #fff;
    border: 1px solid var(--cal-line);
    border-radius: 16px;
    box-shadow: 0 16px 40px rgba(15, 23, 58, 0.06);
    padding: 0.85rem 1rem 0.65rem;
    overflow: hidden;
}
#calendar .fc {
    height: 100%;
    --fc-border-color: var(--cal-line);
    --fc-page-bg-color: #fff;
    --fc-neutral-bg-color: var(--cal-wash);
    --fc-today-bg-color: transparent;
    --fc-now-indicator-color: var(--cal-orange);
    --fc-button-bg-color: #fff;
    --fc-button-border-color: transparent;
    --fc-button-text-color: var(--cal-ink);
    --fc-button-hover-bg-color: var(--cal-orange-soft);
    --fc-button-hover-border-color: transparent;
    --fc-button-active-bg-color: var(--cal-orange);
    --fc-button-active-border-color: var(--cal-orange);
    --fc-button-active-text-color: #fff;
    --fc-event-selected-overlay-color: rgba(15, 23, 58, 0.08);
    font-family: inherit;
}
#calendar .fc-toolbar.fc-header-toolbar {
    margin-bottom: 0.85rem;
    gap: 0.75rem;
    align-items: center;
}
#calendar .fc-toolbar-title {
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    color: var(--cal-ink);
}
#calendar .fc-toolbar-chunk:first-child,
#calendar .fc-toolbar-chunk:last-child .fc-button-group {
    background: var(--cal-wash);
    border-radius: 999px;
    padding: 0.2rem;
}
#calendar .fc-button {
    border: 0 !important;
    border-radius: 999px !important;
    box-shadow: none !important;
    font-weight: 700;
    font-size: 0.82rem;
    padding: 0.4rem 0.85rem;
    transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
}
#calendar .fc-button:focus {
    box-shadow: 0 0 0 3px rgba(255, 92, 29, 0.28) !important;
}
#calendar .fc-button-primary:not(:disabled).fc-button-active,
#calendar .fc-button-primary:not(:disabled):active {
    background: var(--cal-orange);
    color: #fff;
}
#calendar .fc-scrollgrid,
#calendar .fc-scrollgrid-section > td,
#calendar .fc-scrollgrid-section > th {
    border: 0;
}
#calendar .fc-col-header-cell {
    background: transparent;
    border: 0;
    padding: 0.35rem 0 0.55rem;
}
#calendar .fc-col-header-cell-cushion {
    color: var(--cal-muted);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: none;
}
#calendar .fc-daygrid-day {
    transition: background-color 0.18s ease;
}
#calendar .fc-daygrid-day-frame {
    min-height: 0;
}
#calendar .fc-daygrid-day:hover {
    background: #fbfcfe;
}
#calendar .fc-daygrid-day-number {
    color: var(--cal-ink);
    font-weight: 650;
    font-size: 0.82rem;
    text-decoration: none;
    margin: 0.35rem 0.4rem 0.1rem;
    padding: 0.15rem 0.4rem;
    border-radius: 999px;
    transition: background-color 0.18s ease, color 0.18s ease;
}
#calendar .fc-day-today {
    background: linear-gradient(180deg, #fff8f4 0%, #fff 42%);
}
#calendar .fc-day-today .fc-daygrid-day-number {
    background: var(--cal-orange);
    color: #fff;
}
#calendar .fc-day-other .fc-daygrid-day-number {
    color: #b0b7c3;
}
#calendar .fc-daygrid-event,
#calendar .fc-timegrid-event {
    border-radius: 8px;
    border-width: 1px;
    margin-top: 2px;
    box-shadow: none;
    transition: transform 0.16s ease, box-shadow 0.16s ease;
}
#calendar .fc-daygrid-event:hover,
#calendar .fc-timegrid-event:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(15, 23, 58, 0.1);
    z-index: 4;
}
#calendar .fc-event-chip {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
    padding: 2px 6px;
    font-size: 0.75rem;
    line-height: 1.35;
}
#calendar .fc-event-time {
    flex: 0 0 auto;
    font-weight: 700;
}
#calendar .fc-event-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
    font-weight: 650;
}
#calendar .fc-event-bell {
    flex: 0 0 auto;
    width: 0.38rem;
    height: 0.38rem;
    margin-left: auto;
    border-radius: 50%;
    background: currentColor;
}
#calendar .fc-timegrid-event .fc-event-chip {
    flex-direction: column;
    align-items: flex-start;
    gap: 0;
}
#calendar .fc-daygrid-more-link {
    display: inline-flex;
    margin: 2px 6px 0;
    padding: 0.05rem 0.4rem;
    border-radius: 999px;
    background: var(--cal-orange-soft);
    color: #c2410c;
    font-size: 0.72rem;
    font-weight: 700;
    text-decoration: none;
    transition: background-color 0.16s ease;
}
#calendar .fc-daygrid-more-link:hover {
    background: #ffd8c7;
    color: #9a3412;
}
#calendar .fc-popover {
    border: 1px solid var(--cal-line);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(15, 23, 58, 0.14);
}
#calendar .fc-popover-header {
    background: var(--cal-ink);
    color: #fff;
}
#calendar .fc-timegrid-slot-label-cushion,
#calendar .fc-timegrid-axis-cushion {
    color: var(--cal-muted);
    font-size: 0.72rem;
    font-weight: 650;
}
#calendar .fc-timegrid-now-indicator-line {
    border-color: var(--cal-orange);
}
#calendar .fc-timegrid-now-indicator-arrow {
    border-color: var(--cal-orange);
    color: var(--cal-orange);
}
#calendar .fc-view-harness {
    animation: staffCalendarIn 0.28s ease;
}
@keyframes staffCalendarIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    #calendar .fc-view-harness,
    #calendar .fc-daygrid-event,
    #calendar .fc-timegrid-event,
    #calendar .fc-button,
    body #wrapper .staff-calendar-add {
        animation: none;
        transition: none;
    }
}
@media (max-width: 767px) {
    .staff-calendar-page {
        height: auto;
        min-height: calc(100vh - 4.5rem);
    }
    #calendar {
        min-height: 36rem;
    }
    .staff-calendar-legend {
        display: none;
    }
}
</style>
@include('backend.partials._calendar_modals')

@endsection
