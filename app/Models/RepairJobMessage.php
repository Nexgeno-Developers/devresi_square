<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairJobMessage extends Model
{
    public const CONTRACTOR = 'contractor';

    public const LANDLORD = 'landlord';

    protected $fillable = ['repair_job_offer_id', 'user_id', 'side', 'body', 'masked', 'read_at'];

    protected $casts = [
        'masked' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(RepairJobOffer::class, 'repair_job_offer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
