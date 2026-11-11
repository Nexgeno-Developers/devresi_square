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
        $messageUrl = $this->messageOrganiserUrl($notifiable);

        return (new MailMessage)
            ->subject("Appointment invitation: {$this->event->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been invited to the appointment \"{$this->event->title}\".")
            ->line('Starts: ' . $this->event->start_datetime->format('d M Y, H:i'))
            ->when($this->event->location, fn (MailMessage $mail) => $mail->line("Location: {$this->event->location}"))
            ->when($this->event->description, fn (MailMessage $mail) => $mail->line($this->event->description))
            ->action('View calendar', route('backend.events.calendar'))
            ->when($messageUrl, fn (MailMessage $mail) => $mail->line('Questions? Message the organiser: '.$messageUrl));
    }

    /** Link into the inbox for invitees who are not staff of the organising account. */
    private function messageOrganiserUrl(object $notifiable): ?string
    {
        if (! config('messaging.enabled', true) || ! $notifiable instanceof \App\Models\User || ! $this->event->account_id) {
            return null;
        }
        if ($notifiable->hasRole('Contractor')) {
            return route('contractor.messages.start', ['context' => 'event', 'id' => $this->event->id]);
        }
        $isStaff = \App\Models\AccountUser::query()
            ->where('account_id', $this->event->account_id)->where('user_id', $notifiable->id)->where('status', 'active')
            ->whereIn('member_type', \App\Support\AccountMembership::WORKSPACE_OPERATOR_TYPES)->exists();

        return $isStaff ? null : route('portal.messages.start', ['context' => 'event', 'id' => $this->event->id]);
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
