<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairHistory extends Model
{
    use HasFactory;

    protected $fillable = ['repair_issue_id', 'action', 'previous_status', 'new_status', 'note'];

    public function repairIssue()
    {
        return $this->belongsTo(RepairIssue::class);
    }
}
