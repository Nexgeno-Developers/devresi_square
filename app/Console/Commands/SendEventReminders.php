<?php

namespace App\Console\Commands;

use App\Models\EventReminder;
use App\Notifications\EventReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Enums\CrmNotificationEvent;
use App\Services\Notifications\CrmNotificationService;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send due event reminders to invited users';

    public function handle(): int
    {
        $now = now();
        $sent = 0;

        EventReminder::query()
            ->where('sent', false)
            ->whereHas('event', function ($query) use ($now) {
                $query->where('start_datetime', '>=', $now)
                    ->where('status', '!=', 'Cancelled');
            })
            ->with('event.users')
            ->get()
            ->each(function (EventReminder $reminder) use ($now, &$sent): void {
                $event = $reminder->event;
                $triggerTime = $event->start_datetime->copy()->subMinutes($reminder->minutes_before);

                if ($now->lt($triggerTime)) {
                    return;
                }

                try {
                    app(CrmNotificationService::class)->dispatch(
                        CrmNotificationEvent::AppointmentReminder,
                        $event,
                        [
                            'account_id' => $event->account_id,
                            'recipients' => $event->users,
                            'appointment_title' => $event->title,
                            'appointment_at' => $event->start_datetime->timezone($event->account?->timezone ?: 'Europe/London')->format('d M Y, H:i'),
                            'action_url' => route('backend.events.calendar'),
                            'milestone' => 'reminder-'.$reminder->id,
                        ]
                    );
                    $reminder->update(['sent' => true]);
                    $sent++;
                } catch (\Throwable $exception) {
                    Log::error('Unable to send appointment reminder.', [
                        'event_id' => $event->id,
                        'reminder_id' => $reminder->id,
                        'exception' => $exception,
                    ]);
                }
            });

        $this->info("Sent {$sent} appointment reminder(s).");

        return self::SUCCESS;
    }
}
