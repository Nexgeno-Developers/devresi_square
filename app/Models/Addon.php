<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Addon extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'code',
        'name',
        'addon_type',
        'monthly_price_minor',
        'annual_price_minor',
        'currency',
        'stripe_monthly_price_id',
        'stripe_annual_price_id',
        'grant_quantity',
        'is_stackable',
        'is_active',
    ];

    protected $casts = [
        'monthly_price_minor' => 'integer',
        'annual_price_minor' => 'integer',
        'grant_quantity' => 'integer',
        'is_stackable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptionAddons(): HasMany
    {
        return $this->hasMany(AccountSubscriptionAddon::class);
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
