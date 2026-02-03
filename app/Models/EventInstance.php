<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'start_datetime',
        'end_datetime',
        'instance_status',
        'is_exception',
        'notified',
    ];

    // Casts for date/time
    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'notified' => 'boolean',
    ];

    /**
     * Inverse: an instance belongs to one master event.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /** 
     * Audit‐trail relationship: one instance has many changes 
     */
    public function changes()
    {
        return $this->hasMany(EventInstanceChange::class, 'event_instance_id');
    }

    public function reminders()
    {
        return $this->hasMany(EventReminder::class);
    }

}
