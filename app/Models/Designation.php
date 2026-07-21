<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class Designation extends Model
{
    use HasFactory, BelongsToSaasAccount;

    protected $fillable = ['account_id', 'company_id', 'title', 'status'];  // The fields we want to allow mass assignment

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'designation_has_permissions')
            ->withTimestamps();
    }
}
