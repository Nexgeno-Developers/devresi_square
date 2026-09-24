<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Model;

class BankDetails extends Model
{
    use BelongsToSaasAccount;

    protected $fillable = [
        'account_id',
        'user_id',
        'account_name',
        'account_no',
        'sort_code',
        'bank_name',
        'swift_code',
        'is_active',
        'is_primary',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
