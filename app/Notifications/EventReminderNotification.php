<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\EventReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Event $event,
        private readonly EventReminder $reminder,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Appointment reminder: {$this->event->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your appointment \"{$this->event->title}\" starts at {$this->event->start_datetime->format('H:i d M, Y')}.")
            ->line("This reminder was set {$this->reminder->minutes_before} minutes before.")
            ->action('View calendar', route('backend.events.calendar'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'appointment_reminder',
            'title' => 'Appointment reminder',
            'message' => "{$this->event->title} starts at {$this->event->start_datetime->format('d M Y, H:i')}.",
            'event_id' => $this->event->id,
            'start' => $this->event->start_datetime->toDateTimeString(),
            'minutes_before' => $this->reminder->minutes_before,
            'url' => route('backend.events.calendar'),
        ];
    }
}
