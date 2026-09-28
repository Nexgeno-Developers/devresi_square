<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenancy extends Model
{
    /** @use HasFactory<\Database\Factories\TenancyFactory> */
    use HasFactory, BelongsToSaasAccount;

    protected $fillable = [
        'account_id',
        'company_id',
        'branch_id',
        'property_id',
        'offer_id',
        'status',
        'move_in',
        'move_out',
        'tenancy_renewal_confirm_date',
        'extension_date',
        'rent',
        'deposit',
        'deposit_type',
        'deposit_number',
        'frequency',
        'rent_due_day',
        'rent_auto_invoice',
        'rent_auto_invoice_tenant_user_id',
        'rent_next_period_start',
        'tenancy_sub_status_id',
        'tenancy_type_id',
        'deposit_held_by',
        'deposit_service',
        'tds_dps_number',
        'reference_number',
        'deposit_scheme',
        'periodic',
        'rolling_contract',
        'renewal_exempt',
        'term_months',
        'term_days'
        ,'deposit_received_at'
        ,'deposit_protected_at'
        ,'prescribed_information_sent_at'
        ,'written_terms_sent_at'
    ];

    protected $casts = [
        'move_in' => 'date',
        'move_out' => 'date',
        'rent_auto_invoice' => 'boolean',
        'rent_next_period_start' => 'date',
        'deposit_received_at' => 'datetime',
        'deposit_protected_at' => 'datetime',
        'prescribed_information_sent_at' => 'datetime',
        'written_terms_sent_at' => 'datetime',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function tenantMembers()
    {
        return $this->hasMany(TenantMember::class);
    }

    /**
     * Relationship with TenancySubStatus.
     */
    public function tenancySubStatus()
    {
        return $this->belongsTo(TenancySubStatus::class, 'tenancy_sub_status_id');
    }

    /**
     * Relationship with TenancyType.
     */
    public function tenancyType()
    {
        return $this->belongsTo(TenancyType::class, 'tenancy_type_id');
    }

    // Define a many-to-many relationship with PropertyManager (via User)
    public function propertyManagers()
    {
        return $this->belongsToMany(User::class, 'property_manager_tenancy', 'tenancy_id', 'property_manager_id');
    }

    public function notices()
    {
        return $this->hasMany(TenancyNotice::class);
    }

    public function correctionRequests()
    {
        return $this->hasMany(TenancyCorrectionRequest::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * True when a deposit is on this tenancy and protection should be tracked.
     */
    public function hasDepositToProtect(): bool
    {
        return $this->deposit_received_at !== null
            || (is_numeric($this->deposit) && (float) $this->deposit > 0);
    }

    public function rentFrequencyLabel(): string
    {
        $frequency = strtolower(trim((string) $this->frequency));

        return str_contains($frequency, 'week') ? 'Weekly' : 'Monthly';
    }

    public function rentDueLabel(): string
    {
        if ($this->rentFrequencyLabel() === 'Weekly') {
            return 'Each week from the period start';
        }

        $day = (int) ($this->rent_due_day ?: ($this->move_in?->day ?: 1));
        $day = max(1, min(28, $day));

        return 'Day '.$day.' of each month';
    }

    /**
     * Next rent due date on or after the issue date, using the tenancy due day.
     */
    public function suggestedInvoiceDueDate(?\Carbon\CarbonInterface $issueDate = null): \Carbon\Carbon
    {
        $issue = \Carbon\Carbon::parse($issueDate ?? now())->startOfDay();

        if ($this->rentFrequencyLabel() === 'Weekly') {
            return $issue;
        }

        $day = (int) ($this->rent_due_day ?: ($this->move_in?->day ?: $issue->day));
        $day = max(1, min(28, $day));
        $due = $issue->copy()->day($day);

        if ($due->lt($issue)) {
            $due = $issue->copy()->addMonthNoOverflow()->day($day);
        }

        return $due;
    }

    public function depositSchemeLabel(): ?string
    {
        $scheme = strtolower(trim((string) $this->deposit_scheme));

        return match ($scheme) {
            '' => null,
            'tds' => 'Tenancy Deposit Scheme',
            'dps' => 'Deposit Protection Service',
            'mydeposits' => 'mydeposits',
            default => (string) $this->deposit_scheme,
        };
    }

    public function depositSchemeReference(): ?string
    {
        $ref = trim((string) ($this->tds_dps_number ?: $this->reference_number ?: ''));

        return $ref !== '' ? $ref : null;
    }

    public function depositProtectionDeadline(): ?\Carbon\CarbonInterface
    {
        if (! $this->deposit_received_at) {
            return null;
        }

        return $this->deposit_received_at->copy()->addDays(30);
    }

    public function hasPrescribedInformationDocument(): bool
    {
        $morphTypes = array_values(array_unique([
            static::class,
            $this->getMorphClass(),
            'Tenancy',
            'App\\Models\\Tenancy',
        ]));

        $typeIds = DocumentType::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%Prescribed Information%')
                    ->orWhere('name', 'like', '%prescribed information%');
            })
            ->pluck('id');

        $query = Document::query()
            ->where('documentable_id', $this->id)
            ->whereIn('documentable_type', $morphTypes);

        if ($typeIds->isEmpty()) {
            return (clone $query)
                ->where(function ($inner) {
                    $inner->where('title', 'like', '%prescribed%')
                        ->orWhere('title', 'like', '%Prescribed%');
                })
                ->exists();
        }

        return $query->whereIn('document_type_id', $typeIds)->exists();
    }

    /**
     * Checklist complete: scheme + reference, protected date, prescribed sent, prescribed doc attached.
     */
    public function depositProtectionComplete(): bool
    {
        if (! $this->hasDepositToProtect()) {
            return true;
        }

        return $this->deposit_scheme
            && $this->depositSchemeReference()
            && $this->deposit_protected_at
            && $this->prescribed_information_sent_at
            && $this->hasPrescribedInformationDocument();
    }

    public function depositProtectionNeedsAttention(): bool
    {
        return $this->hasDepositToProtect() && ! $this->depositProtectionComplete();
    }

    /**
     * Open deposit-protection gaps for an account (dashboard needs-you).
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function needingDepositProtectionForAccount(int $accountId)
    {
        return static::query()
            ->with(['property', 'documents.documentType'])
            ->forAccount($accountId)
            ->where(function ($query) {
                $query->whereNotNull('deposit_received_at')
                    ->orWhere('deposit', '>', 0);
            })
            ->orderByDesc('deposit_received_at')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (self $tenancy) => $tenancy->depositProtectionNeedsAttention())
            ->values();
    }
}
