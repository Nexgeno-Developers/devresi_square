<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlPeriodClose extends Model
{
    protected $fillable = ['period', 'is_closed', 'closed_by', 'closed_at', 'notes'];

    protected $casts = [
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public static function isClosed(string $period): bool
    {
        return static::where('period', $period)->where('is_closed', true)->exists();
    }
}
