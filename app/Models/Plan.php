<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'subscription_plans';

    protected $fillable = [
        'name', 'slug', 'description', 'badge_label', 'is_featured',
        'price_monthly', 'price_yearly',
        'max_properties', 'max_staff', 'max_tenancies',
        'features', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'features'      => 'array',
        'is_active'     => 'boolean',
        'is_featured'   => 'boolean',
        'price_monthly' => 'decimal:2',
        'price_yearly'  => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Plan $plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });

        static::updating(function (Plan $plan) {
            if ($plan->isDirty('name') && !$plan->isDirty('slug')) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function subscriptions()
    {
        return $this->hasMany(UserPlan::class, 'plan_id');
    }

    public function activeSubscriptionsCount(): int
    {
        return $this->subscriptions()->where('status', 'active')->count();
    }

    public function formattedMonthlyPrice(): string
    {
        return '£' . number_format($this->price_monthly, 2);
    }

    public function formattedYearlyPrice(): string
    {
        return '£' . number_format($this->price_yearly, 2);
    }

    public function yearlySavingPercent(): int
    {
        if (!$this->price_monthly || !$this->price_yearly) return 0;
        return (int) round((1 - ($this->price_yearly / ($this->price_monthly * 12))) * 100);
    }
}
