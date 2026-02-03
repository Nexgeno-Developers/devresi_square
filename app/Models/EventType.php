<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    protected $fillable = ['name', 'slug', 'description'];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    /**
     * Each event type can have many sub-types.
     */
    public function events()
    {
        return $this->hasMany(Event::class, 'type_id');
    }

    /**
     * Each event type can have many sub-types.
     */
    public function subTypes()
    {
        return $this->hasMany(EventSubType::class);
    }
}
