<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TracksUser;

class RepairIssue extends Model
{
    use HasFactory, TracksUser;

    protected $fillable = [
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
    ];

    // protected $casts = [
    //     'repair_navigation' => 'array', // Cast repair_navigation as an array (JSON)
    // ];

    // Optionally cast tenant_availability to datetime.
    protected $casts = [
        'tenant_availability' => 'datetime',
    ];

    /**
     * Get the associated property for this repair issue.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
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
     * Return an array of [id => “RefNo, …]
     * suitable for a <select> dropdown.
     */
    public static function optionsForSelect(): array
    {
        return self::all()->pluck('display_label', 'id')->toArray();
    }

}
