<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GlAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'auditable_type', 'auditable_id', 'action',
        'user_id', 'old_values', 'new_values', 'ip_address', 'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, Model $model, ?array $oldValues = null): void
    {
        try {
            static::create([
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'action' => $action,
                'user_id' => auth()->check() ? auth()->id() : null,
                'old_values' => $oldValues,
                'new_values' => $action !== 'deleted' ? $model->getAttributes() : null,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silently skip if audit table doesn't exist yet (e.g. during migrations)
        }
    }
}
