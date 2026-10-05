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

    public function scopeTenantChoices($query)
    {
        return $query
            ->whereNull('parent_id')
            ->whereNotIn('name', ['Tenant reported', 'Isolation test', 'Staging General Repair'])
            ->where('name', 'not like', 'Handoff %')
            ->where(function ($query) {
                $query->where('status', 1)->orWhereNull('status');
            });
    }

    public static function tenantAreaChoices()
    {
        return static::query()
            ->tenantChoices()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->unique(fn (self $category) => mb_strtolower(trim($category->name)))
            ->take(12)
            ->values();
    }
}
