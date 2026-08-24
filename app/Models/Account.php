<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable;

class Account extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'owner_user_id',
        'account_type',
        'account_name',
        'billing_email',
        'billing_phone',
        'currency',
        'timezone',
        'status',
        'trial_started_at',
        'trial_ends_at',
        'stripe_customer_id',
        'registration_welcome_email_sent_at',
        'subscription_activation_email_sent_at',
    ];

    protected $casts = [
        'trial_started_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'registration_welcome_email_sent_at' => 'datetime',
        'subscription_activation_email_sent_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function accountUsers(): HasMany
    {
        return $this->hasMany(AccountUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'account_users')
            ->withPivot([
                'member_type',
                'access_level',
                'can_login',
                'branch_id',
                'designation_id',
                'status',
                'created_by',
            ])
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AccountSubscription::class);
    }

    public function accountSubscriptions(): HasMany
    {
        return $this->subscriptions();
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(AccountSubscription::class)
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->latestOfMany();
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(AccountSubscription::class)->latestOfMany();
    }

    public function activePlan(): ?Plan
    {
        return $this->currentSubscription?->plan;
    }

    public function getActivePlanAttribute(): ?Plan
    {
        return $this->activePlan();
    }

    public function propertyParticipants(): HasMany
    {
        return $this->hasMany(PropertyParticipant::class);
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }
}
