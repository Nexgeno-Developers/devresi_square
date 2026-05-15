<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = ['title'];  // The fields we want to allow mass assignment

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'designation_has_permissions')
            ->withTimestamps();
    }
}
