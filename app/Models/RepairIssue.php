<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TracksUser;

class RepairIssue extends Model
{
    use HasFactory, TracksUser, BelongsToSaasAccount;

    protected $fillable = [
        'account_id',
        'company_id',
        'branch_id',
        'repair_category_id',
        'repair_navigation',
        'description',
        'tenant_availability',
        'access_details',
        'estimated_price',
        'vat_type',
        'vat_percentage',
        'priority',
        'sub_status',
        'status',
        'property_id',
        'tenant_id',
        'final_contractor_id',
        'reference_number',
        'created_by',
        'updated_by',
        'acknowledged_at',
        'acknowledged_by',
        'complaint_code',
        'classification_snapshot',
        'sla_due_at',
        'make_safe_due_at',
        'emergency_access',
        'reported_at',
        'dispatched_at',
        'dispatched_by',
        'make_safe_at',
        'make_safe_by',
        'resolved_at',
        'resolved_by',
        'landlord_note',
    ];

    // protected $casts = [
    //     'repair_navigation' => 'array', // Cast repair_navigation as an array (JSON)
    // ];

    // Optionally cast tenant_availability to datetime.
    protected $casts = [
        'tenant_availability' => 'datetime',
        'acknowledged_at' => 'datetime',
        'classification_snapshot' => 'array',
        'emergency_access' => 'boolean',
        'sla_due_at' => 'datetime',
        'make_safe_due_at' => 'datetime',
        'reported_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'make_safe_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the associated property for this repair issue.
     */
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


    public function repairCategory()
    {
        return $this->belongsTo(RepairCategory::class);
    }

    public function repairPhotos()
    {
        return $this->hasMany(RepairPhoto::class);
    }

    public function repairAssignments()
    {
        return $this->hasMany(RepairAssignment::class);
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function (RepairIssue $repairIssue) {
            if (blank($repairIssue->priority)) {
                $repairIssue->priority = 'medium';
            }
            if (blank($repairIssue->sub_status)) {
                $repairIssue->sub_status = 'Pending';
            }
            if (blank($repairIssue->status)) {
                $repairIssue->status = 'Pending';
            }
        });

        static::deleting(function ($repairIssue) {
            $repairIssue->repairPhotos()->delete();
        });
    }
    /**
     * Get the property manager assignments for this repair issue.
     */
    public function repairIssuePropertyManagers()
    {
        return $this->hasMany(RepairIssuePropertyManager::class);
    }

    /**
     * Get the contractor assignments for this repair issue.
     */
    public function repairIssueContractorAssignments()
    {
        return $this->hasMany(RepairIssueContractorAssignment::class);
    }

    public function repairHistories()
    {
        return $this->hasMany(RepairHistory::class);
    }

    public function slaEvents()
    {
        return $this->hasMany(RepairSlaEvent::class);
    }

    public function isPriorityComplaint(): bool
    {
        return filled($this->complaint_code);
    }

    /**
     * Staff open the landlord repair. Tenants open maintenance.
     *
     * @return array{action_url: string, portal_action_url: string, portal_action_tenants_only: true}
     */
    public function notificationLinks(): array
    {
        return [
            'action_url' => route('admin.property_repairs.show', $this->id),
            'portal_action_url' => route('tenant.maintenance'),
            'portal_action_tenants_only' => true,
        ];
    }

    /**
     * Visit window chosen by the tenant: morning, afternoon, or evening.
     */
    public function tenantAvailabilityLabel(): ?string
    {
        if (! $this->tenant_availability) {
            return null;
        }

        $slot = match ((int) $this->tenant_availability->format('G')) {
            9 => 'morning',
            13 => 'afternoon',
            17 => 'evening',
            default => $this->tenant_availability->format('H:i'),
        };

        return $this->tenant_availability->format('j M Y').', '.$slot;
    }

    public function repairIssueUsers()
    {
        return $this->hasMany(RepairIssueUser::class);
    }

    public function finalContractor()
    {
        return $this->belongsTo(User::class, 'final_contractor_id');
    }

    /*public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id')
            ->where('category_id', 3);
    }*/

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Tenant');
            });
    }


    // public function workOrders()
    // {
    //     return $this->hasMany(WorkOrder::class);
    // }
    public function workOrder()
    {
        return $this->hasOne(WorkOrder::class, 'repair_issue_id');
    }

    // Get the invoice through WorkOrder
    public function invoice()
    {
        return $this->hasOneThrough(Invoice::class, WorkOrder::class, 'repair_issue_id', 'work_order_id');
    }

    public function events()
    {
        return $this->morphToMany(Event::class, 'eventable');
    }
    
    /**
     * Get the display label for the repair issue.
     * This is used in dropdowns and other UI elements.
     */
    public function getDisplayLabelAttribute(): string
    {
        return "{$this->reference_number}";
    }

    /**
     * Plain-language status for the tenant portal.
     */
    public function tenantStatusLabel(): string
    {
        $status = strtolower(trim((string) $this->status));

        return match (true) {
            in_array($status, ['closed', 'invoice paid', 'completed', 'complete'], true) => 'Done',
            in_array($status, ['cancelled', 'canceled'], true) => 'Cancelled',
            in_array($status, ['under process', 'in progress', 'assigned', 'quoted', 'scheduled'], true) => 'In progress',
            in_array($status, ['pending', 'open', 'new'], true) => 'Received',
            default => $this->status ? ucfirst((string) $this->status) : 'Received',
        };
    }

    /**
     * 0 = reported, 1 = in progress, 2 = done — for a simple tenant timeline.
     */
    public function tenantStatusStep(): int
    {
        return match ($this->tenantStatusLabel()) {
            'Done' => 2,
            'In progress' => 1,
            default => 0,
        };
    }

    /**
     * Return an array of [id => “RefNo, …]
     * suitable for a <select> dropdown.
     */
    public static function optionsForSelect(): array
    {
        return self::all()->pluck('display_label', 'id')->toArray();
    }

}
