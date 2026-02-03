<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenancy extends Model
{
    /** @use HasFactory<\Database\Factories\TenancyFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id',
        'offer_id',
        'status',
        'move_in',
        'move_out',
        'tenancy_renewal_confirm_date',
        'extension_date',
        'rent',
        'deposit',
        'deposit_type',
        'deposit_number',
        'frequency',
        'tenancy_sub_status_id',
        'tenancy_type_id',
        'deposit_held_by',
        'deposit_service',
        'tds_dps_number',
        'reference_number',
        'deposit_scheme',
        'periodic',
        'rolling_contract',
        'renewal_exempt',
        'term_months',
        'term_days'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function tenantMembers()
    {
        return $this->hasMany(TenantMember::class);
    }

    /**
     * Relationship with TenancySubStatus.
     */
    public function tenancySubStatus()
    {
        return $this->belongsTo(TenancySubStatus::class, 'tenancy_sub_status_id');
    }

    /**
     * Relationship with TenancyType.
     */
    public function tenancyType()
    {
        return $this->belongsTo(TenancyType::class, 'tenancy_type_id');
    }

    // Define a many-to-many relationship with PropertyManager (via User)
    public function propertyManagers()
    {
        return $this->belongsToMany(User::class, 'property_manager_tenancy', 'tenancy_id', 'property_manager_id');
    }
}
