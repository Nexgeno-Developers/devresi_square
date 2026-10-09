<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorTrade extends Model
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const MORE_INFO = 'more_info';

    public const REJECTED = 'rejected';

    protected $fillable = [
        'contractor_profile_id',
        'trade',
        'status',
        'reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ContractorProfile::class, 'contractor_profile_id');
    }

    public function label(): string
    {
        return config('contractor_marketplace.trades.'.$this->trade, ucfirst($this->trade));
    }
}
