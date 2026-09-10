<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    public const LANDLORD_TYPE_NAMES = [
        'Inspection',
        'Maintenance',
        'Move-In/Move-Out',
        'Reminder',
        'Tenancy Check',
        'Contract',
        'Meeting',
        'Viewing',
    ];

    public const LANDLORD_HIDDEN_SUBTYPE_NAMES = [
        'Buyer Viewing',
        'Sales Agreement Signing',
        'In-Branch Client Meeting',
        'Vendor Meeting',
        'Investor Meeting',
    ];

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

    public function scopeVisibleToCurrentUser($query)
    {
        if (! is_landlord_plan_user()) {
            return $query;
        }

        return $query->whereIn('name', self::LANDLORD_TYPE_NAMES);
    }
}
