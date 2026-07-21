<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use BelongsToSaasAccount;

    protected $fillable = [
        'account_id',
        'user_id',
        'parent_id',
        'branch_id',
        'permissions_customized',
        'status',
    ];

    protected $casts = [
        'permissions_customized' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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
