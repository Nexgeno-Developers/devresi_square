<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenancyType extends Model
{
    /** @use HasFactory<\Database\Factories\TenancyTypeFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    public function tenancies()
    {
        return $this->hasMany(Tenancy::class);
    }
}
