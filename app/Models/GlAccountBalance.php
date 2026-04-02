<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GlAccountBalance extends Model
{
    use HasFactory;

    protected $fillable = ['gl_account_id','period','debit','credit'];

    public function account()
    {
        return $this->belongsTo(GlAccount::class, 'gl_account_id');
    }
}

