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
}
