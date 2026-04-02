<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlAccount extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'type', 'is_active', 'parent_id', 'group', 'sort_order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('code');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(GlAccountBalance::class, 'gl_account_id');
    }
}

