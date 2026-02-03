<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairIssuePropertyManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_issue_id',
        'property_manager_id',
        'assigned_at',
        'assigned_by',
        'notes'
    ];

    /**
     * Get the repair issue for this property manager assignment.
     */
    public function repairIssue()
    {
        return $this->belongsTo(RepairIssue::class);
    }

    /**
     * Get the property manager (user) assigned to this repair issue.
     */
    public function propertyManager()
    {
        return $this->belongsTo(User::class, 'property_manager_id');
    }
}
