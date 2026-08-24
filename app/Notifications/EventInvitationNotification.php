<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Event $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Appointment invitation: {$this->event->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been invited to the appointment \"{$this->event->title}\".")
            ->line('Starts: ' . $this->event->start_datetime->format('d M Y, H:i'))
            ->when($this->event->location, fn (MailMessage $mail) => $mail->line("Location: {$this->event->location}"))
            ->when($this->event->description, fn (MailMessage $mail) => $mail->line($this->event->description))
            ->action('View calendar', route('backend.events.calendar'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'appointment_invitation',
            'title' => 'Appointment invitation',
            'message' => "You have been invited to {$this->event->title}.",
            'event_id' => $this->event->id,
            'starts_at' => $this->event->start_datetime->toDateTimeString(),
            'url' => route('backend.events.calendar'),
        ];
    }
}
