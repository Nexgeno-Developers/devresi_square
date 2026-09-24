<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'compliance_records';

    protected $fillable = [
        'property_id',
        'compliance_type_id',
        'issued_date',
        'expiry_date',
        'photos',
        'status',
        'responsible_user_id',
        'remediation_due_at',
        'completed_at',
        'served_to_tenant_at',
        'served_notes',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'remediation_due_at' => 'datetime',
        'completed_at' => 'datetime',
        'served_to_tenant_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->endOfDay()->lt(now());
    }

    public function isExpiringSoon(int $withinDays = 60): bool
    {
        if (! $this->expiry_date || $this->isExpired()) {
            return false;
        }

        return $this->expiry_date->lte(now()->addDays($withinDays));
    }

    public function needsAttention(int $withinDays = 60): bool
    {
        return $this->isExpired() || $this->isExpiringSoon($withinDays);
    }

    public function attentionLabel(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }
        if ($this->isExpiringSoon()) {
            return 'Due soon';
        }

        return 'On file';
    }

    /**
     * Gas, EPC and EICR gaps shown on Certificates: missing, missing expiry, expired, or due within 60 days.
     *
     * @return list<array{property: Property, type: ComplianceType, record: ?self, state: string}>
     */
    public static function certificateGapsForAccount(int $accountId): array
    {
        $properties = Property::query()
            ->forAccount($accountId)
            ->orderBy('line_1')
            ->get();

        $types = ComplianceType::query()
            ->whereIn('alias', ['gas', 'epc', 'eicr'])
            ->orderBy('name')
            ->get();

        $records = static::query()
            ->with('complianceType')
            ->whereIn('property_id', $properties->pluck('id'))
            ->get()
            ->groupBy(fn (self $record) => $record->property_id.'-'.$record->compliance_type_id);

        $rows = [];
        foreach ($properties as $property) {
            foreach ($types as $type) {
                $latest = ($records->get($property->id.'-'.$type->id) ?? collect())
                    ->sortByDesc(fn (self $record) => $record->expiry_date?->timestamp ?? 0)
                    ->first();

                if ($latest && $latest->expiry_date && ! $latest->needsAttention()) {
                    continue;
                }

                $rows[] = [
                    'property' => $property,
                    'type' => $type,
                    'record' => $latest,
                    'state' => ! $latest ? 'Missing' : ($latest->expiry_date ? $latest->attentionLabel() : 'Missing expiry'),
                ];
            }
        }

        return $rows;
    }

    public static function needingAttentionForAccount(int $accountId, int $withinDays = 60)
    {
        return static::query()
            ->with(['property', 'complianceType'])
            ->whereNull('completed_at')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($withinDays)->toDateString())
            ->whereHas('property', fn ($query) => $query->forAccount($accountId))
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * The compliance type that this record belongs to.
     */
    public function complianceType()
    {
        return $this->belongsTo(ComplianceType::class);
    }

    /**
     * The property that this compliance record belongs to.
     */
    public function property()
    {
        return $this->belongsTo(Property::class); // Assuming you have a Property model
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * The compliance details for this compliance record.
     */
    public function complianceDetails()
    {
        return $this->hasMany(ComplianceDetail::class);
    }
}
