<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'type',
        'verify_via',
        'otp_code',
        'otp_expires_at',
        'otp_verified_at',
        // legacy columns kept for backward compat
        'email_verification_token',
        'email_verified_at',
        'phone_otp',
        'phone_otp_expires_at',
        'phone_verified_at',
        'status',
        'approved_by',
        'approved_at',
        'rejected_at',
        'user_id',
        'ip',
        'ref_url',
    ];

    protected $casts = [
        'otp_expires_at'       => 'datetime',
        'otp_verified_at'      => 'datetime',
        'email_verified_at'    => 'datetime',
        'phone_otp_expires_at' => 'datetime',
        'phone_verified_at'    => 'datetime',
        'approved_at'          => 'datetime',
        'rejected_at'          => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function isVerified(): bool
    {
        return !is_null($this->otp_verified_at);
    }
}
