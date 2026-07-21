<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Plan extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'code',
        'name',
        'target_account_type',
        'description',
        'monthly_price_minor',
        'annual_price_minor',
        'currency',
        'stripe_monthly_price_id',
        'stripe_annual_price_id',
        'trial_days',
        'property_limit',
        'branch_limit',
        'staff_limit',
        'property_manager_limit',
        'allow_company_profile',
        'allow_invoice_branding',
        'allow_roles_permissions',
        'allow_contact_login',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price_minor' => 'integer',
        'annual_price_minor' => 'integer',
        'trial_days' => 'integer',
        'property_limit' => 'integer',
        'branch_limit' => 'integer',
        'staff_limit' => 'integer',
        'property_manager_limit' => 'integer',
        'allow_company_profile' => 'boolean',
        'allow_invoice_branding' => 'boolean',
        'allow_roles_permissions' => 'boolean',
        'allow_contact_login' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AccountSubscription::class);
    }

    public function accountSubscriptions(): HasMany
    {
        return $this->subscriptions();
    }

    public function monthlyPriceMajor(): string
    {
        return number_format(($this->monthly_price_minor ?? 0) / 100, 2, '.', '');
    }

    public function annualPriceMajor(): string
    {
        return number_format(($this->annual_price_minor ?? 0) / 100, 2, '.', '');
    }

    public function formattedMonthlyPrice(): string
    {
        return $this->formatMinorPrice($this->monthly_price_minor);
    }

    public function formattedAnnualPrice(): string
    {
        return $this->formatMinorPrice($this->annual_price_minor);
    }

    public function getMonthlyPriceAttribute(): string
    {
        return $this->monthlyPriceMajor();
    }

    public function getAnnualPriceAttribute(): string
    {
        return $this->annualPriceMajor();
    }

    private function formatMinorPrice(?int $minor): string
    {
        $prefix = strtoupper($this->currency ?? 'GBP') === 'GBP'
            ? html_entity_decode('&pound;', ENT_QUOTES, 'UTF-8')
            : strtoupper($this->currency ?? 'GBP') . ' ';

        return $prefix . number_format(($minor ?? 0) / 100, 2);
    }
}
