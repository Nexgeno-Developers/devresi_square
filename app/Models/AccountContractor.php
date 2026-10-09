<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountContractor extends Model
{
    protected $fillable = [
        'account_id',
        'contractor_user_id',
        'email',
        'name',
        'token',
        'invited',
        'favourite',
        'invited_by',
        'invited_at',
        'claimed_at',
    ];

    protected $casts = [
        'invited' => 'boolean',
        'favourite' => 'boolean',
        'invited_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_user_id');
    }
}
