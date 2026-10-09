<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairJobInvoice extends Model
{
    public const ISSUED = 'issued';

    public const PAID = 'paid';

    public const VOID = 'void';

    protected $fillable = [
        'repair_job_offer_id', 'account_id', 'contractor_user_id', 'number', 'issued_on', 'due_on', 'lines',
        'net_amount', 'vat_amount', 'gross_amount', 'vat_number', 'cis_status', 'notes', 'status',
        'paid_at', 'paid_by', 'void_reason',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'due_on' => 'date',
        'lines' => 'array',
        'net_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(RepairJobOffer::class, 'repair_job_offer_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_user_id');
    }

    public function isOverdue(): bool
    {
        return $this->status === self::ISSUED && $this->due_on?->isPast();
    }

    public function statusLabel(): string
    {
        if ($this->isOverdue()) {
            return 'Overdue';
        }

        return [self::ISSUED => 'Unpaid', self::PAID => 'Paid', self::VOID => 'Void'][$this->status] ?? ucfirst((string) $this->status);
    }
}
