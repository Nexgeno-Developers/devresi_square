<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAsset extends Model
{
    protected $fillable = [
        'name', 'asset_code', 'description', 'category',
        'purchase_date', 'purchase_cost', 'salvage_value',
        'useful_life_months', 'depreciation_method',
        'accumulated_depreciation', 'net_book_value',
        'status', 'disposal_date', 'disposal_amount',
        'gl_asset_account_id', 'gl_depreciation_account_id',
        'gl_expense_account_id', 'notes',
    ];

    protected $casts = [
        'purchase_cost' => 'float',
        'salvage_value' => 'float',
        'accumulated_depreciation' => 'float',
        'net_book_value' => 'float',
        'disposal_amount' => 'float',
    ];

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'gl_asset_account_id');
    }

    public function depreciationAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'gl_depreciation_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'gl_expense_account_id');
    }

    public function getMonthlyDepreciationAttribute(): float
    {
        if ($this->useful_life_months <= 0) {
            return 0;
        }
        return ($this->purchase_cost - $this->salvage_value) / $this->useful_life_months;
    }

    public function getDepreciableAmountAttribute(): float
    {
        return max(0, $this->purchase_cost - $this->salvage_value - $this->accumulated_depreciation);
    }
}
