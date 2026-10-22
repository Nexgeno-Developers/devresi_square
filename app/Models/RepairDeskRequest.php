<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairDeskRequest extends Model
{
    use BelongsToSaasAccount;

    public const REQUESTED = 'requested';

    public const IN_PROGRESS = 'in_progress';

    public const COMPLETED = 'completed';

    public const RETURNED = 'returned';

    public const OPEN = [
        self::REQUESTED,
        self::IN_PROGRESS,
    ];

    public const STATUSES = [
        self::REQUESTED => 'Waiting for the team',
        self::IN_PROGRESS => 'With the Resisquare team',
        self::COMPLETED => 'Completed by the team',
        self::RETURNED => 'Returned to the landlord',
    ];

    protected $fillable = [
        'account_id',
        'repair_issue_id',
        'requested_by',
        'assigned_to',
        'status',
        'landlord_note',
        'team_note',
    ];

    public function repairIssue(): BelongsTo
    {
        return $this->belongsTo(RepairIssue::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RepairDeskEvent::class)->orderBy('id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }
}
