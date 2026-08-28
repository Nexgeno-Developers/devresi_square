<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Landlord MVP route restrictions
    |--------------------------------------------------------------------------
    */
    'blocked_route_name_prefixes' => [
        'admin.accounting.',
        'backend.saas.',
        'admin.registrations.',
        'admin.designations.',
        'admin.branches.',
        'roles.',
        'staffs.',
        'business_settings.',
        'smtp_settings.',
        'env_key_update.',
        'admin.companies.',
    ],

    'wizard_steps' => [
        1 => 'Find your property',
        2 => 'Property details',
        3 => 'Review your Property Passport',
    ],
];
