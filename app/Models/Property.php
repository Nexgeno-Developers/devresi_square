<?php

namespace App\Models;

use App\Models\Notes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes; // Import SoftDeletes
use App\Traits\TracksUser;

class Property extends Model
{
    use HasFactory, SoftDeletes, TracksUser;

    // Define the fillable properties
    protected $fillable = [
        "prop_ref_no",
        'prop_name',
        'line_1',
        'line_2',
        'city',
        'country',
        'county',
        'currency',
        'postcode',
        'frunishing_type',
        'property_type',
        'transaction_type',
        'specific_property_type',
        'bedroom',
        'bathroom',
        'reception',
        'parking',
        'parking_location',
        'balcony',
        'garden',
        'service',
        'management',
        'collecting_rent',
        'floor',
        'square_feet',
        'square_meter',
        'aspects',
        'sales_current_status',
        'letting_current_status',
        'sales_status_description',
        'letting_status_description',
        'available_from',
        'pets_allow',
        'market_on',
        'furniture',
        'kitchen',
        'heating_cooling',
        'safety',
        'other',
        'price',
        'access_arrangement',
        'key_highlights',
        'nearest_station',
        'nearest_school',
        'nearest_religious_places',
        'nearest_places',
        'useful_information',
        'ground_rent',
        'service_charge',
        'annual_council_tax',
        'council_tax_band',
        'local_authority',
        'letting_price',
        'tenure',
        'length_of_lease',
        'estate_charge',
        'miscellaneous_charge',
        'estate_charges_id',
        'epc_required',
        'epc_rating',
        'is_gas',
        'gas_safe_acknowledged',
        'photos',
        'floor_plan',
        'view_360',
        'video_url',
        'youtube_url',
        'instagram_url',
        'imp_notes',
        // 'designation',
        // 'branch',
        // 'commission_percentage',
        // 'commission_amount',
        'step',
        'quick_step',
        'created_by',
        'deleted_by',
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

    public function responsibilities()
    {
        return $this->hasMany(PropertyResponsibility::class, 'property_id');
    }

    public function estateCharge()
    {
        return $this->belongsTo(EstateCharge::class, 'estate_charges_id');
    }

    public function complianceRecords()
    {
        return $this->hasMany(ComplianceRecord::class);
    }

    public function countryRelation()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    // public function notes()
    // {
    //     return $this->hasMany(Notes::class);
    // }
    public function notes()
    {
        return $this->morphMany(Notes::class, 'noteable');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
    
    /**
     * The local authority this property belongs to.
     */
    public function localAuthority()
    {
        // assuming your properties table has local_authority FK
        return $this->belongsTo(LocalAuthority::class, 'local_authority');
    }

    public function events()
    {
        return $this->morphToMany(Event::class, 'eventable');
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->line_1,
            $this->line_2,
            $this->city,
            $this->county,
            $this->postcode,
            optional($this->countryRelation)->name // if you have a country relation
        ];

        // Filter out any null/empty values and join with a comma
        return implode(', ', array_filter($parts));
    }


    /**
     * Return an array of [id => “PropRef — Address…”, …]
     * suitable for a <select> dropdown.
     */
    // public static function optionsForSelect(): array
    // {
    //     return self::query()
    //         ->orderBy('prop_ref_no')
    //         ->get()
    //         ->mapWithKeys(function(self $p){
    //             $label = "{$p->prop_ref_no} — {$p->line_1}, {$p->city}";
    //             return [$p->getKey() => $label];
    //         })
    //         ->toArray();
    // }
    public function getDisplayLabelAttribute(): string
    {
        return "{$this->prop_ref_no} — {$this->line_1}, {$this->line_2}, {$this->city}";
    }

    public static function optionsForSelect(): array
    {
        return self::all()->pluck('display_label', 'id')->toArray();
    }


}
