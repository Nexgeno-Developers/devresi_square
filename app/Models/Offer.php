<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    // Specify the fillable fields to allow mass assignment
    protected $fillable = [
        'property_id',      // Foreign key to the Property model
        'price',            // Price of the offer
        'deposit',          // Deposit required
        'term',             // Term as a string (replacing enum with string)
        'move_in_date',     // Move-in date
        'tenant_details',   // JSON field for tenant details
        'status',           // Enum field for status (Pending, Accepted, Rejected)
    ];

    // Specify the attributes that should be cast to native types
    // protected $casts = [
    //     'tenant_details' => 'array', // Automatically cast the 'tenant_details' field as an array
    // ];

    /**
     * Relationship with Property (One-to-Many)
     * An offer belongs to a specific property.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Relationship with Tenancy (One-to-Many)
     * An offer can result in multiple tenancies.
     */
    public function tenancies()
    {
        return $this->hasMany(Tenancy::class);
    }

    public static function rejectOtherOffers($propertyId, $acceptedOfferId)
    {
        Offer::where('property_id', $propertyId)
            ->where('id', '!=', $acceptedOfferId)
            ->where('status', '!=', 'Rejected')
            ->update(['status' => 'Rejected']);
    }

}
