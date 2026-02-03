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
        // if you have other columns, e.g.:
        // 'department',
        // 'status',
    ];

    // Relations, etc...
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

}
