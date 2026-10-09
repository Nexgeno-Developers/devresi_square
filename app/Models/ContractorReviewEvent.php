<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorReviewEvent extends Model
{
    protected $fillable = [
        'contractor_profile_id',
        'actor_user_id',
        'action',
        'subject',
        'note',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
