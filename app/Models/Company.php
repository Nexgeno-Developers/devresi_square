<?php
namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use BelongsToSaasAccount;

    protected $fillable = [
        'account_id',
        'owner_user_id',
        'name',
        'company_type',
        'registration_number',
        'registered_address',
        'communication_address',
        'emails',
        'phones',
        'logo_path',
        'stamp_path',
        'vat_number',
        'website',
        'social_media',
        'services',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'emails' => 'array',
        'phones' => 'array',
        'social_media' => 'array',
        'services' => 'array',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function ownerTransfers()
    {
        return $this->hasMany(CompanyOwnerTransfer::class)->latest('transferred_at');
    }

}
