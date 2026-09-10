<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSubType extends Model
{
    protected $fillable = ['event_type_id', 'name','slug', 'description'];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Each event sub-type belongs to one event type.
     */
    public function type()
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    public function scopeVisibleToCurrentUser($query)
    {
        if (! is_landlord_plan_user()) {
            return $query;
        }

        return $query
            ->whereHas('type', fn ($types) => $types->whereIn('name', EventType::LANDLORD_TYPE_NAMES))
            ->whereNotIn('name', EventType::LANDLORD_HIDDEN_SUBTYPE_NAMES);
    }
}
