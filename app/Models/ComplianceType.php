<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'compliance_types';

    protected $fillable = [
        'name',
        'alias',
        'description',
    ];

    /**
     * The compliance records that belong to the compliance type.
     */
    public function complianceRecords()
    {
        return $this->hasMany(ComplianceRecord::class);
    }
}
