<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Event extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    // Audit only these fields
    protected $auditInclude = [
        'title',
        'type_id',
        'sub_type_id',
        'office',
        'status',
        'diary_owner',
        'on_behalf_of',
        'description',
        'location',
        'rrule',
        'exdates',
        'start_datetime',
        'end_datetime',
        'parent_id',
    ];

    protected $fillable = [
        'title',
        'parent_id',      // foreign key
        'type_id',       // foreign key
        'sub_type_id',   // foreign key
        'office',
        'status',
        'diary_owner',
        'on_behalf_of',
        'start_datetime',
        'end_datetime',
        'description',
        'location',
        'repeat_until_date',
        'rrule',
        'exdates',
        'is_exception',
        'instance_status',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    // in app/Models/Event.php

    public function type()
    {
        return $this->belongsTo(EventType::class, 'type_id');
    }

    // public function subType()
    // {
    //     return $this->belongsTo(EventSubType::class, 'sub_type_id');
    // }

    public function children()
    {
        return $this->hasMany(Event::class, 'parent_id');
    }

    /**
     * Each event can have a parent event (for recurring events).
     * This is a self-referential relationship.
     */
    public function parent()
    {
        return $this->belongsTo(Event::class, 'parent_id');
    }


    /**
     * Each master event has many instances.
     */
    public function instances()
    {
        return $this->hasMany(EventInstance::class);
    }

    /**
     * Color helper from status (unchanged).
     */
    public function getColorAttribute()
    {
        return match ($this->status) {
            'Cancelled' => '#dc3545',
            'Confirmed' => '#28a745',
            'Rescheduled' => '#ffc107',
            default => '#007bff',
        };
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
        return $this->hasMany(EventReminder::class, 'event_id');
    }

    /**
     * All the Properties attached to this Event.
     */
    public function properties()
    {
        return $this->morphedByMany(Property::class, 'eventable');
    }

    /**
     * All the Repairs attached to this Event.
     */
    public function repairIssues()
    {
        return $this->morphedByMany(RepairIssue::class, 'eventable');
    }

    /**
     * All the Users attached to this Event.
     */
    public function users()
    {
        return $this->morphedByMany(User::class, 'eventable');
    }

    public function diaryOwner()
    {
        return $this->belongsTo(User::class, 'diary_owner');
    }

    public function onBehalfOf()
    {
        return $this->belongsTo(User::class, 'on_behalf_of');
    }

}
