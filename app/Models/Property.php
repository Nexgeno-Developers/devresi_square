<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes; // Import SoftDeletes

class Property extends Model
{
    use HasFactory, SoftDeletes;

    // Define the fillable properties
    protected $fillable = [
        'prop_name',
        'line_1',
        'line_2',
        'city',
        'country',
        'postcode',
        'property_type',
        'transaction_type',
        'specific_property_type',
        'bedroom',
        'bathroom',
        'reception',
        'parking',
        'balcony',
        'garden',
        'service',
        'management',
        'collecting_rent',
        'floor',
        'square_feet',
        'square_meter',
        'aspects',
        'current_status',
        'status_description',
        'available_from',
        'market_on',
        'furniture',
        'kitchen',
        'heating_cooling',
        'safety',
        'other',
        'price',
        'ground_rent',
        'service_charge',
        'annual_council_tax',
        'council_tax_band',
        'letting_price',
        'tenure',
        'length_of_lease',
        'epc_rating',
        'is_gas',
        'photos',
        'floor_plan',
        'view_360',
        'video_url',
        'designation',
        'branch',
        'commission_percentage',
        'commission_amount',
        'step',
        'quick_step',
        'added_by',
    ];
    protected $casts = [
        'market_on' => 'json',
        // 'photos' => 'json',
        // 'floor_plan' => 'json',
        // 'view_360' => 'json',
        // 'video_url' => 'array',
    ];

    // protected static function booted()
    // {
    //     static::creating(function ($property) {
    //         $property->added_by = Auth::id(); // Automatically set the added_by field
    //     });
    // }
}