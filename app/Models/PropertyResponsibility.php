<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyResponsibility extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyResponsibilityFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'user_id',
        'branch_id',
        'designation_id',
        'commission_percentage',
        'commission_amount',
        'added_by',
        'deleted_by',
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
