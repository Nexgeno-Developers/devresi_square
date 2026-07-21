<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyResponsibility extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyResponsibilityFactory> */
    use HasFactory, SoftDeletes, BelongsToSaasAccount;

    protected $fillable = [
        'account_id',
        'property_id',
        'responsibility_type',
        'user_id',
        'branch_id',
        'designation_id',
        'commission_percentage',
        'commission_amount',
        'status',
        'starts_at',
        'ends_at',
        'added_by',
        'deleted_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Define relationships if needed
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }
}
