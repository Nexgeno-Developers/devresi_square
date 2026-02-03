<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id', 'level', 'description', 'icon', 'position', 'status'];

    public function parentCategory()
    {
        return $this->belongsTo(RepairCategory::class, 'parent_id');
    }

    public function subCategories()
    {
        return $this->hasMany(RepairCategory::class, 'parent_id');
    }

    public function repairIssues()
    {
        return $this->hasMany(RepairIssue::class);
    }
}
