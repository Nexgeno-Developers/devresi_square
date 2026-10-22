<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairDeskEvent extends Model
{
    public const SENT = 'sent';

    public const TAKEN = 'taken';

    public const UPDATE = 'update';

    public const COMPLETED = 'completed';

    public const RETURNED = 'returned';

    public const LABELS = [
        self::SENT => 'Sent to Resisquare',
        self::TAKEN => 'Taken by the team',
        self::UPDATE => 'Update',
        self::COMPLETED => 'Completed',
        self::RETURNED => 'Returned',
    ];

    protected $fillable = [
        'repair_desk_request_id',
        'account_id',
        'actor_user_id',
        'kind',
        'body',
    ];

    public function deskRequest(): BelongsTo
    {
        return $this->belongsTo(RepairDeskRequest::class, 'repair_desk_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function label(): string
    {
        return self::LABELS[$this->kind] ?? ucfirst((string) $this->kind);
    }
}
