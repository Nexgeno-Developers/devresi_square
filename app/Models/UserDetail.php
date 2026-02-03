<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employment_status',
        'business_name',
        'registered_address',
        'guarantee',
        'previously_rented',
        'poor_credit',
        'correspondence_address',
        'allow_email',
        'allow_post',
        'allow_text',
        'allow_call',
        'occupation',
        'vat_number',
        'emails',
        'phones',
        'other',
        'budget',
        'area',
        'tentative_move_in',
        'no_of_beds',
        'no_of_tenants',
        'specialisations',
        'cover_areas',
        'pi_insurance',
        'pi_reference_number',
        'pi_certificate',
        'nationality_id',
        'visa_expiry',
        'passport_no',
        'nrl_number',
        'right_to_rent_check',
        'checked_by_user',
        'checked_by_external',
    ];

    // cast these fields to/from arrays automatically
    protected $casts = [
        'emails' => 'array',
        'phones' => 'array',
        'allow_email' => 'boolean',
        'allow_post'  => 'boolean',
        'allow_text'  => 'boolean',
        'allow_call'  => 'boolean',
    ];

    /**
     * Define relationship with User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function userCheckedBy()
    {
        return $this->belongsTo(User::class, 'checked_by_user');
    }

}
