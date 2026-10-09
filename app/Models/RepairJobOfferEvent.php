<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairJobOfferEvent extends Model
{
    protected $fillable = [
        'repair_job_offer_id',
        'actor_user_id',
        'from_status',
        'to_status',
        'note',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(RepairJobOffer::class, 'repair_job_offer_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
