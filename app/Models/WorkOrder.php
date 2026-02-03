<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Upload;
use App\Traits\TracksUser;

class WorkOrder extends Model
{
    use HasFactory, TracksUser;

    protected $fillable = [
        'works_order_no',
        'repair_issue_id',
        'job_type_id',
        'job_sub_type_id',
        'job_status',
        'job_scope',
        'tentative_start_date',
        'tentative_end_date',
        'booked_date',
        'invoice_to',
        'invoice_to_id',
        'quote_attachment',
        'actual_cost',
        'charge_to_landlord',
        'payment_by',
        'estimated_cost',
        'status',
        'extra_notes',
        'date_time',
        'created_by',
        'updated_by',
    ];

    // Relationship with RepairIssue
    public function repairIssue()
    {
        return $this->belongsTo(RepairIssue::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'invoice_to_id');
    }
    
    public function quoteAttachment()
    {
        return $this->belongsTo(Upload::class, 'quote_attachment');
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class, 'job_type_id');
    }

    public function jobSubType()
    {
        return $this->belongsTo(JobType::class, 'job_sub_type_id'); // Assuming both types are from `job_types` table
    }
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(WorkOrderItem::class);
    }


    // Relationship with Supplier
    // public function supplier()
    // {
    //     return $this->belongsTo(Supplier::class);
    // }

}