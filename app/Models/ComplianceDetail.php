<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'compliance_details';

    protected $fillable = [
        'compliance_record_id',
        'key',
        'value',
    ];

    /**
     * The compliance record that this detail belongs to.
     */
    public function complianceRecord()
    {
        return $this->belongsTo(ComplianceRecord::class);
    }
}
