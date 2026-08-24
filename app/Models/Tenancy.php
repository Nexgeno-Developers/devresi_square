<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenancy extends Model
{
    /** @use HasFactory<\Database\Factories\TenancyFactory> */
    use HasFactory, BelongsToSaasAccount;

    protected $fillable = [
        'account_id',
        'company_id',
        'branch_id',
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
        ,'deposit_received_at'
        ,'deposit_protected_at'
        ,'prescribed_information_sent_at'
        ,'written_terms_sent_at'
    ];

    protected $casts = [
        'move_in' => 'date',
        'move_out' => 'date',
        'deposit_received_at' => 'datetime',
        'deposit_protected_at' => 'datetime',
        'prescribed_information_sent_at' => 'datetime',
        'written_terms_sent_at' => 'datetime',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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

    public function notices()
    {
        return $this->hasMany(TenancyNotice::class);
    }
}
