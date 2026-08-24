<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantMember extends Model
{
    /** @use HasFactory<\Database\Factories\TenantMemberFactory> */
    use HasFactory, BelongsToAccount;

    protected $fillable = [
        'account_id',
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
        'access_level',
        'can_login',
        'is_main_person',
        'group_id'
        ,'right_to_rent_required'
        ,'right_to_rent_checked_at'
        ,'right_to_rent_follow_up_due_at'
    ];

    protected $casts = [
        'can_login' => 'boolean',
        'right_to_rent_required' => 'boolean',
        'right_to_rent_checked_at' => 'datetime',
        'right_to_rent_follow_up_due_at' => 'datetime',
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
