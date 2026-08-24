<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventReminder extends Model
{
    protected $fillable = [
        'event_id',
        'minutes_before',
        'channel',
        'sent',
    ];

    // Each reminder belongs to an event. Events are the active calendar
    // records; event instances are no longer used for appointment reminders.
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
