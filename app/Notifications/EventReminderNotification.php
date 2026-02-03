<?php

namespace App\Notifications;

use App\Models\EventInstance;
use App\Models\EventReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EventReminderNotification extends Notification
{
    use Queueable;
    protected $instance;
    protected $reminder;
    /**
     * Create a new notification instance.
     */
    public function __construct(EventInstance $instance, EventReminder $reminder)
    {
        $this->instance = $instance;
        $this->reminder = $reminder;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    // public function via(object $notifiable): array
    // {
    //     return ['mail'];
    // }

    public function via($notifiable)
    {
        return [$this->reminder->channel];
    }
    /**
     * Get the mail representation of the notification.
     */
    /*public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }*/
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Reminder: {$this->instance->event->title}")
            ->line("Your event “{$this->instance->event->title}” starts at {$this->instance->start_datetime->format('H:i d M, Y')}.")
            ->line("This reminder was set {$this->reminder->minutes_before} minutes before.");
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    /*public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }*/
    public function toArray($notifiable)
    {
        return [
            'title' => $this->instance->event->title,
            'start' => $this->instance->start_datetime->toDateTimeString(),
            'minutes_before' => $this->reminder->minutes_before,
        ];
    }
}
