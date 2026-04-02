<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\GlAccount;

class SysBankAccount extends Model
{
    use HasFactory;

    protected $table = 'sys_bank_accounts';
    protected $guarded = [];

    public function glAccount()
    {
        return $this->belongsTo(GlAccount::class, 'gl_account_id');
    }
}
