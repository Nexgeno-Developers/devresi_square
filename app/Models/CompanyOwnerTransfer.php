<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyOwnerTransfer extends Model
{
    protected $fillable = [
        'company_id',
        'old_owner_user_id',
        'new_owner_user_id',
        'transferred_by',
        'note',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function oldOwner()
    {
        return $this->belongsTo(User::class, 'old_owner_user_id');
    }

    public function newOwner()
    {
        return $this->belongsTo(User::class, 'new_owner_user_id');
    }

    public function transferredBy()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
