<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairContractorRequest extends Model
{
    public const SENT = 'sent';

    public const AWARDED = 'awarded';

    public const AWAITING = 'awaiting_contractor';

    public const CANCELLED = 'cancelled';

    public const CLOSED = 'closed';

    public const COMPLETED = 'completed';

    public const URGENCIES = ['emergency' => 'Emergency', 'urgent' => 'Urgent', 'routine' => 'Routine'];

    protected $fillable = [
        'account_id',
        'repair_issue_id',
        'urgency',
        'trade',
        'respond_by',
        'budget_ceiling',
        'show_budget',
        'access_notes',
        'availability_to_arrange',
        'status',
        'sent_by',
        'first_sent_at',
        'last_sent_at',
        'first_offer_at',
        'awarded_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
        'details_changed_at',
        'first_offer_notified_at',
        'last_digest_at',
    ];

    protected $casts = [
        'budget_ceiling' => 'decimal:2',
        'show_budget' => 'boolean',
        'availability_to_arrange' => 'boolean',
        'respond_by' => 'datetime',
        'first_sent_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'first_offer_at' => 'datetime',
        'awarded_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'details_changed_at' => 'datetime',
        'first_offer_notified_at' => 'datetime',
        'last_digest_at' => 'datetime',
    ];

    public function repairIssue(): BelongsTo
    {
        return $this->belongsTo(RepairIssue::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(RepairJobOffer::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isEmergency(): bool
    {
        return $this->urgency === 'emergency';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::SENT => 'Sent to contractors',
            self::AWARDED => 'Job accepted',
            self::AWAITING => 'Awaiting contractor',
            self::CANCELLED => 'Cancelled',
            self::CLOSED => 'Closed',
            self::COMPLETED => 'Completed',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }
}
