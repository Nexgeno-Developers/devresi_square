<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class RepairIssueContractorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'repair_issue_id',
        'contractor_id',
        'assigned_by',
        'cost_price',
        'quote_attachment',
        'contractor_preferred_availability',
        'status',
        'quote_token',
        'quote_requested_at',
        'quote_submitted_at',
        'contractor_availability_options',
        'consultant_name',
        'consultant_phone',
        'tentative_start_date',
        'tentative_end_date',
        'quote_notes',
    ];

    protected $casts = [
        'quote_requested_at' => 'datetime',
        'quote_submitted_at' => 'datetime',
        'contractor_preferred_availability' => 'datetime',
        'contractor_availability_options' => 'array',
        'tentative_start_date' => 'date',
        'tentative_end_date' => 'date',
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
        $contractorRoleId = Role::where('name', 'Contractor')->value('id');

        return $this->belongsTo(User::class, 'contractor_id')
            ->where(function ($query) use ($contractorRoleId) {
                $query->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Contractor');
                })->orWhereHas('category', function ($categoryQuery) {
                    $categoryQuery->where('name', 'Contractor');
                });

                if ($contractorRoleId && Schema::hasColumn('users', 'role_id')) {
                    $query->orWhere('role_id', $contractorRoleId);
                }
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
