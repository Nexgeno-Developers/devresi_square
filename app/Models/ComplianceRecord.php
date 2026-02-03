<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'compliance_records';

    protected $fillable = [
        'property_id',
        'compliance_type_id',
        'issued_date',
        'expiry_date',
        'photos',
        'status',
    ];

    /**
     * The compliance type that this record belongs to.
     */
    public function complianceType()
    {
        return $this->belongsTo(ComplianceType::class);
    }

    /**
     * The property that this compliance record belongs to.
     */
    public function property()
    {
        return $this->belongsTo(Property::class); // Assuming you have a Property model
    }

    /**
     * The compliance details for this compliance record.
     */
    public function complianceDetails()
    {
        return $this->hasMany(ComplianceDetail::class);
    }
}
