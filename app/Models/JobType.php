<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'parent_id', 'level', 'order_level'];

    public function parent()
    {
        return $this->belongsTo(JobType::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(JobType::class, 'parent_id')->orderBy('order_level');
    }

    public static function getHierarchy()
    {
        return self::whereNull('parent_id')->with('children')->orderBy('order_level')->get();
    }
}
