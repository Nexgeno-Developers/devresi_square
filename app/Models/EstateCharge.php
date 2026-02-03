<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstateCharge extends Model
{
    use HasFactory;

    // Specify the table if not following Laravel's naming convention
    protected $table = 'estate_charges';

    // Mass-assignable attributes
    protected $fillable = [
        'ref_no',
        'property_id',
        'ownergroup_id',
        'description',
        'paid_by_landlord',
        'managed_by_property',
        'charge_landlord',
        'tax',
        'schedule_charge',
        'attachment',
        'type',
        'due_date',
        'start_date',
        'end_date',
        'amount',
        'frequency',
        'status',
        'added_by',
    ];

    /**
     * Relationship with the Property model.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Relationship with the OwnerGroup model.
     */
    public function ownerGroup(): BelongsTo
    {
        return $this->belongsTo(OwnerGroup::class, 'ownergroup_id');
    }

    /**
     * Relationship with the User model for the added_by field.
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
