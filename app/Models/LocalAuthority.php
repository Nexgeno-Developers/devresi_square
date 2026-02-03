<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalAuthority extends Model
{
    protected $fillable = ['name', 'local_authority_group_id'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(LocalAuthorityGroup::class, 'local_authority_group_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->group->display_prefix . ' of ' . $this->name;
    }
}
