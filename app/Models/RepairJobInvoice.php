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
        'supplier', 'bill_to', 'site', 'payment', 'supplied_on',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'due_on' => 'date',
        'supplied_on' => 'date',
        'supplier' => 'array',
        'bill_to' => 'array',
        'site' => 'array',
        'payment' => 'array',
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

    /**
     * Values the printed bill needs. Invoices issued before payment details
     * were stored have no snapshot: the supplier falls back to the live profile,
     * the site falls back to the property, and no payment block is invented.
     *
     * @return array{supplier: array<string, mixed>, bill_to: array<string, mixed>, site: array<string, mixed>, payment: array<string, mixed>}
     */
    public function documentContext(?ContractorProfile $profile, ?RepairJobOffer $offer): array
    {
        $supplier = is_array($this->supplier) && $this->supplier !== []
            ? $this->supplier
            : [
                'name' => $profile?->displayName() ?: 'Contractor',
                'legal_name' => ($profile?->trading_name && $profile?->legal_name && $profile->trading_name !== $profile->legal_name)
                    ? $profile->legal_name
                    : null,
                'address' => collect([$profile?->address_line, $profile?->city, $profile?->postcode])->filter()->implode(', '),
                'phone' => $profile?->phone,
                'email' => $profile?->user?->email,
                'company_number' => $profile?->company_number,
                'vat_number' => $this->vat_number,
            ];

        $site = is_array($this->site) && filled($this->site['address'] ?? null)
            ? $this->site
            : ['address' => $offer?->repairIssue?->property?->full_address ?: ''];

        $payment = is_array($this->payment) && filled($this->payment['account_number'] ?? null)
            ? $this->payment
            : [];

        return [
            'supplier' => $supplier,
            'bill_to' => is_array($this->bill_to) ? $this->bill_to : [],
            'site' => $site,
            'payment' => $payment,
        ];
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
