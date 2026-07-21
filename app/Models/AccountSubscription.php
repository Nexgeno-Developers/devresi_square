<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class AccountSubscription extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'account_id',
        'plan_id',
        'billing_cycle',
        'status',
        'trial_started_at',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'stripe_subscription_id',
        'stripe_price_id',
        'price_at_signup_minor',
        'currency_at_signup',
        'plan_name_at_signup',
        'cancel_at_period_end',
        'cancelled_at',
    ];

    protected $casts = [
        'trial_started_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'price_at_signup_minor' => 'integer',
        'cancel_at_period_end' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(AccountSubscriptionAddon::class);
    }

    public function accountSubscriptionAddons(): HasMany
    {
        return $this->addons();
    }

    public function activeAddons(): HasMany
    {
        return $this->addons()->where('status', 'active');
    }

    public function signupPriceMajor(): string
    {
        return number_format(($this->price_at_signup_minor ?? 0) / 100, 2, '.', '');
    }

    public function formattedSignupPrice(): string
    {
        $currency = strtoupper($this->currency_at_signup ?: 'GBP');
        $prefix = $currency === 'GBP'
            ? html_entity_decode('&pound;', ENT_QUOTES, 'UTF-8')
            : $currency . ' ';

        return $prefix . number_format(($this->price_at_signup_minor ?? 0) / 100, 2);
    }
}
