<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstateChargeItem extends Model
{
    use HasFactory;

    // Specify the table if not following Laravel's naming convention
    protected $table = 'estate_charge_items';

    // Mass-assignable attributes
    protected $fillable = [
        'estate_charge_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'status',
    ];

    /**
     * Relationship with the EstateCharge model.
     */
    public function estateCharge(): BelongsTo
    {
        return $this->belongsTo(EstateCharge::class, 'estate_charge_id');
    }
}
