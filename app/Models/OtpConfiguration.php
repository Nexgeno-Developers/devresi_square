<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpConfiguration extends Model
{
    protected $fillable = ['type', 'value'];

    public static function activeProvider(): ?self
    {
        return self::where('value', 1)->first();
    }
}
