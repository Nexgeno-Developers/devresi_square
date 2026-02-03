<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairIssueContractorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_issue_id',
        'contractor_id',
        'assigned_by',
        'cost_price',
        'quote_attachment',
        'contractor_preferred_availability',
        'status'
    ];

    /**
     * Get the repair issue associated with this contractor assignment.
     */
    public function repairIssue()
    {
        return $this->belongsTo(RepairIssue::class);
    }

    /**
     * Get the contractor (user) assigned to the repair issue.
     * Filters contractors by category_id = 6.
     */
    // public function contractor()
    // {
    //     return $this->belongsTo(User::class, 'contractor_id')
    //                 ->where('category_id', 6); // Only contractors with category_id = 6
    // }

    /**
     * Get the contractor (user) assigned to the repair issue.
     * Filters users by the role name 'Contractor' using Spatie Roles.
     */
    public function contractor()
    {
        return $this->belongsTo(User::class, 'contractor_id')
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'Contractor');
                    });
    }

    /**
     * Get the user (property manager) who assigned the contractor.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
