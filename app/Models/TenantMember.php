<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantMember extends Model
{
    /** @use HasFactory<\Database\Factories\TenantMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'tenancy_id',
        'user_id',
        // 'name',
        // 'email',
        // 'phone',
        // 'employment_status',
        // 'business_name',
        // 'guarantee',
        // 'previously_rented',
        // 'poor_credit',
        'is_main_person',
        'group_id'
    ];

    public function tenancy()
    {
        return $this->belongsTo(Tenancy::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
