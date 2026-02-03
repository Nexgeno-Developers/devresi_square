<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\EventReminder;
use Illuminate\Console\Command;
use App\Notifications\EventReminderNotification;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:send-event-reminders';
    protected $signature = 'events:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    // protected $description = 'Command description';
    protected $description = 'Send due event reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // find reminders whose trigger time has come or passed
        EventReminder::where('sent', false)
            ->whereHas('instance', function ($q) use ($now) {
                $q->where('start_datetime', '>=', $now);
            })
            ->with('instance.event')
            ->get()
            ->each(function ($reminder) {
                $instance = $reminder->instance;
                $triggerTime = $instance->start_datetime->copy()
                    ->subMinutes($reminder->minutes_before);
                if (Carbon::now()->gte($triggerTime)) {
                    // dispatch notification
                    $instance->event->user->notify(
                        new EventReminderNotification($instance, $reminder)
                    );
                    $reminder->sent = true;
                    $reminder->save();
                }
            });
    }

    // command
    // $schedule->command('events:send-reminders')->everyMinute();

}
