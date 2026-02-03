<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['repair_issue_id', 'assigned_to', 'assigned_at', 'completed_at', 'status', 'notes'];

    public function repairIssue()
    {
        return $this->belongsTo(RepairIssue::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
