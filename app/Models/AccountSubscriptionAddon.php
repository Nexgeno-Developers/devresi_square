<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class AccountSubscriptionAddon extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'account_subscription_id',
        'account_id',
        'addon_id',
        'quantity',
        'billing_cycle',
        'status',
        'stripe_subscription_item_id',
        'stripe_price_id',
        'price_at_purchase_minor',
        'addon_name_at_purchase',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_at_purchase_minor' => 'integer',
    ];

    public function accountSubscription(): BelongsTo
    {
        return $this->belongsTo(AccountSubscription::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }
}
