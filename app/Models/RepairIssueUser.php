<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairIssueUser extends Model
{
    use HasFactory;

    protected $fillable = ['repair_issue_id', 'user_id', 'user_category_name'];

    public function repairIssue()
    {
        return $this->belongsTo(RepairIssue::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

