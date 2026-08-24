<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenancyNotice extends Model
{
    use BelongsToSaasAccount;

    protected $fillable = [
        'account_id', 'tenancy_id', 'recipient_user_id', 'notice_type', 'served_at',
        'effective_at', 'document_id', 'status', 'notes', 'created_by',
    ];

    protected $casts = ['served_at' => 'datetime', 'effective_at' => 'datetime'];

    public function tenancy(): BelongsTo
    {
        return $this->belongsTo(Tenancy::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
