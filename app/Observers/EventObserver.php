<?php

namespace App\Observers;

use App\Enums\CrmNotificationEvent;
use App\Models\Event;
use App\Services\Notifications\CrmNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class EventObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(Event $event): void
    {
        if (! $event->wasChanged(['start_datetime', 'end_datetime']) || $event->wasChanged('status')) {
            return;
        }

        $event->refresh()->load('users', 'account');
        if ($event->users->isEmpty()) return;

        app(CrmNotificationService::class)->dispatch(
            CrmNotificationEvent::AppointmentRescheduled,
            $event,
            [
                'account_id' => $event->account_id,
                'recipients' => $event->users,
                'appointment_title' => $event->title,
                'appointment_at' => $event->start_datetime->timezone($event->account?->timezone ?: 'Europe/London')->format('d M Y, H:i'),
                'action_url' => route('backend.events.calendar'),
                'milestone' => 'rescheduled-'.$event->updated_at?->timestamp,
            ],
            auth()->user(),
        );
    }
}
