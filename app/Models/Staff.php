<?php

namespace App\Models;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $fillable = [
        'user_id',
        'parent_id',
        'role_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function contacts()
    {
        return $this->hasMany(StaffContact::class);
    }

    public function emails()
    {
        return $this->hasMany(StaffContact::class)->where('type', 'email');
    }

    public function phones()
    {
        return $this->hasMany(StaffContact::class)->where('type', 'phone');
    }
}

