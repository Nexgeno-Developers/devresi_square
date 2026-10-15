<?php

return [
    // Launch scope is England only; Wales, Scotland and Northern Ireland have different landlord rules.
    'region' => 'england',

    'legacy_module_enabled' => (bool) env('LEGACY_CONTRACTOR_MODULE', false),

    'geocode' => [
        'enabled' => (bool) env('CONTRACTOR_GEOCODE', true),
        'endpoint' => 'https://api.postcodes.io',
    ],

    'privacy_version' => '2026-10',

    // Defaults; Super Admin can override these on the contractor settings page.
    'defaults' => [
        'public_liability_minimum' => 1000000,
        'employers_liability_minimum' => 5000000,
        'max_contractors_per_send' => 5,
        'max_contractors_per_emergency_send' => 15,
        'review_sla_working_days' => 2,
        'document_retention_days' => 365,
        'invite_hours' => ['emergency' => 2, 'urgent' => 24, 'routine' => 72],
        'offer_valid_hours' => ['emergency' => 24, 'urgent' => 48, 'routine' => 48],
        'digest_hours' => 3,
        'reminder_days' => [30, 14, 7],
        'emergency_sms' => 1,
        'invoice_due_days' => 14,
        'trade_documents' => [
            'gas' => ['gas_safe'],
            'electrical' => ['electrical_scheme'],
            'oil' => ['oftec'],
            'refrigeration' => ['fgas'],
        ],
    ],

    'trades' => [
        'general' => 'General repairs',
        'plumbing' => 'Plumbing',
        'gas' => 'Gas and heating',
        'electrical' => 'Electrical',
        'oil' => 'Oil heating',
        'refrigeration' => 'Air con and refrigeration',
        'carpentry' => 'Carpentry',
        'roofing' => 'Roofing',
        'decorating' => 'Decorating',
        'appliances' => 'Appliances',
        'locks' => 'Locks and security',
    ],

    'base_documents' => ['photo_id', 'public_liability'],

    'optional_documents' => ['employers_liability', 'dbs'],

    'documents' => [
        'photo_id' => [
            'label' => 'Photo ID',
            'hint' => 'Passport or driving licence',
            'file' => true,
        ],
        'public_liability' => [
            'label' => 'Public liability insurance',
            'hint' => 'The certificate page that shows cover and end date',
            'file' => true,
            'expires' => true,
            'cover' => true,
        ],
        'gas_safe' => [
            'label' => 'Gas Safe registration',
            'hint' => 'Your licence number. A photo of the card is optional.',
            'reference' => 'Gas Safe number',
            'file' => false,
            'expires' => true,
            'lookup' => 'https://www.gassaferegister.co.uk/find-an-engineer-or-check-the-register/check-an-engineer/',
        ],
        'electrical_scheme' => [
            'label' => 'Electrical scheme',
            'hint' => 'NICEIC, NAPIT or equivalent certificate',
            'issuers' => ['NICEIC', 'NAPIT', 'ELECSA', 'Stroma', 'Other'],
            'reference' => 'Registration number',
            'file' => true,
            'expires' => true,
        ],
        'oftec' => [
            'label' => 'OFTEC registration',
            'reference' => 'OFTEC number',
            'file' => true,
            'expires' => true,
        ],
        'fgas' => [
            'label' => 'F-Gas certificate',
            'reference' => 'Certificate number',
            'file' => true,
            'expires' => true,
        ],
        'employers_liability' => [
            'label' => 'Employers’ liability insurance',
            'hint' => 'Only if you employ staff',
            'file' => true,
            'expires' => true,
            'cover' => true,
        ],
        'dbs' => [
            'label' => 'Basic DBS certificate',
            'hint' => 'Optional. Shown to landlords as a badge.',
            'file' => true,
        ],
    ],

    'cover_options' => [1000000, 2000000, 5000000, 10000000],

    'reject_reasons' => [
        'unreadable' => 'Unreadable or blurry',
        'wrong_document' => 'Wrong document',
        'expired' => 'Expired',
        'name_mismatch' => 'Name does not match',
        'cover_too_low' => 'Cover too low',
        'not_on_register' => 'Not found on the public register',
        'other' => 'Other',
    ],

    'decline_reasons' => [
        'too_far' => 'Too far',
        'too_busy' => 'Too busy',
        'not_my_trade' => 'Not my trade',
        'other' => 'Other',
    ],

    'emergency_guidance' => [
        'gas' => 'If anyone can smell gas, call the National Gas Emergency Service on 0800 111 999 now. Open windows, do not use switches or flames.',
        'water' => 'For flooding, turn off the water at the stopcock and switch off electrics near the water if it is safe.',
        'electrical' => 'For a power cut or exposed wiring, call 105 and keep people away from the hazard.',
    ],
];
