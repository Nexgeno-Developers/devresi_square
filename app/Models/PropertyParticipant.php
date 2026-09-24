<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class PropertyParticipant extends Model implements Auditable
{
    use HasFactory;
    use BelongsToSaasAccount;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'account_id',
        'property_id',
        'user_id',
        'participant_type',
        'access_level',
        'can_view_finance',
        'can_view_documents',
        'can_upload_documents',
        'status',
        'created_by',
    ];

    protected $casts = [
        'can_view_finance' => 'boolean',
        'can_view_documents' => 'boolean',
        'can_upload_documents' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('property_id', $propertyId);
    }
}
