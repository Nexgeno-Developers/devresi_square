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

    // Each reminder belongs to one EventInstance
    public function instance()
    {
        return $this->belongsTo(EventInstance::class, 'event_id');
    }
}