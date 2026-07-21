<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory, BelongsToSaasAccount;

    protected $fillable = [
        'account_id',
        'company_id',
        'is_main_head_office',
        'name',
        'address',
        'address_line_1',
        'address_line_2',
        'city',
        'county',
        'postcode',
        'country',
        'user_email',
        'user_phone',
        'alternate_phone',
        'alternate_email',
        'social_media',
        'status',
        'created_by',
    ];

    protected $casts = [
        'is_main_head_office' => 'boolean',
        'social_media' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }
}
