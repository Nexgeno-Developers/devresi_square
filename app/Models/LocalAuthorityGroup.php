<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalAuthorityGroup extends Model
{
    protected $fillable = ['name', 'display_prefix'];

    public function authorities(): HasMany
    {
        return $this->hasMany(LocalAuthority::class);
    }
}
