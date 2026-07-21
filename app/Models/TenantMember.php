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
    ];

    protected $casts = [
        'can_login' => 'boolean',
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
