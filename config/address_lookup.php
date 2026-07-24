<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active UK address provider
    |--------------------------------------------------------------------------
    |
    | Change only ADDRESS_LOOKUP_PROVIDER to switch the implementation used by
    | the property forms. Postcodes.io requires no key, but returns postcode
    | and locality data rather than Royal Mail delivery-point addresses.
    |
    */
    'default' => env('ADDRESS_LOOKUP_PROVIDER', 'postcodes_io'),

    'limit' => (int) env('ADDRESS_LOOKUP_LIMIT', 100),

    'providers' => [
        'postcodes_io' => [
            'driver' => 'postcodes_io',
            'name' => 'Postcodes.io',
            'full_address' => false,
            'auto_search_postcode' => true,
            'placeholder' => 'Enter a UK postcode, e.g. E14 9RU',
            'base_url' => env('POSTCODES_IO_BASE_URL', 'https://api.postcodes.io'),
            'timeout' => (int) env('POSTCODES_IO_TIMEOUT', 8),
            'attribution' => [
                'label' => 'Postcode data from Postcodes.io',
                'url' => 'https://postcodes.io/',
            ],
        ],

        'getaddress' => [
            'driver' => 'getaddress',
            'name' => 'getAddress',
            'full_address' => true,
            'auto_search_postcode' => true,
            'placeholder' => 'Enter a UK postcode or part of an address',
            'base_url' => env('GETADDRESS_BASE_URL', 'https://api.getAddress.io'),
            'api_key' => env('GETADDRESS_API_KEY'),
            'timeout' => (int) env('GETADDRESS_TIMEOUT', 8),
            'attribution' => [
                'label' => 'Address data from getAddress',
                'url' => 'https://getaddress.io/',
            ],
        ],

        'ideal_postcodes' => [
            'driver' => 'ideal_postcodes',
            'name' => 'Ideal Postcodes',
            'full_address' => true,
            'auto_search_postcode' => true,
            'placeholder' => 'Enter a UK postcode, e.g. E14 9RU',
            'base_url' => env('IDEAL_POSTCODES_BASE_URL', 'https://api.ideal-postcodes.co.uk/v1'),
            'api_key' => env('IDEAL_POSTCODES_API_KEY'),
            'timeout' => (int) env('IDEAL_POSTCODES_TIMEOUT', 8),
            'attribution' => [
                'label' => 'Address data from Ideal Postcodes',
                'url' => 'https://ideal-postcodes.co.uk/',
            ],
        ],

        'nominatim' => [
            'driver' => 'nominatim',
            'name' => 'OpenStreetMap',
            'full_address' => false,
            'auto_search_postcode' => false,
            'placeholder' => 'Enter a UK postcode or part of an address',
            'base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org'),
            'user_agent' => env(
                'NOMINATIM_USER_AGENT',
                env('APP_NAME', 'Laravel').' address lookup ('.env('APP_URL', 'http://localhost').')'
            ),
            'email' => env('NOMINATIM_CONTACT_EMAIL'),
            'timeout' => (int) env('NOMINATIM_TIMEOUT', 8),
            'cache_hours' => (int) env('NOMINATIM_CACHE_HOURS', 24),
            'attribution' => [
                'label' => '© OpenStreetMap contributors',
                'url' => 'https://www.openstreetmap.org/copyright',
            ],
        ],
    ],
];
