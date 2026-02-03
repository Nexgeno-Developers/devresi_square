<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkOrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['work_order_id', 'title', 'description', 'unit_price', 'quantity', 'tax_rate_id', 'tax_rate', 'total_price'];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
