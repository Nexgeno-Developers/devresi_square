<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankDetails extends Model
{
    protected $fillable = [
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
