<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairPhoto extends Model
{
    use HasFactory;

    protected $fillable = ['repair_issue_id', 'photos'];

    public function repairIssue()
    {
        return $this->belongsTo(RepairIssue::class);
    }
}
