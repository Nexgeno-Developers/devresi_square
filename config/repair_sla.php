<?php

/**
 * Priority complaints a tenant can raise. Anything not in this list stays a
 * normal repair. Clocks are the operational standard for Landlord and Tenant
 * Act 1985 s.11: make safe within 24 hours, or attend within 72 hours.
 */
return [
    'timezone' => 'Europe/London',

    'complaints' => [
        'gas_leak' => [
            'title' => 'Gas leak or suspected escape',
            'clock' => 'make_safe_24',
            'safety_notice' => 'Leave the property, do not use electrical switches, and call the National Gas Emergency Service on 0800 111 999. Then send this report.',
        ],
        'carbon_monoxide' => [
            'title' => 'Carbon monoxide alarm or faulty flue',
            'clock' => 'make_safe_24',
            'safety_notice' => 'Get everyone out. If anyone feels unwell, call 999. Call the National Gas Emergency Service on 0800 111 999, then send this report.',
        ],
        'electrical_hazard' => [
            'title' => 'Dangerous electrics',
            'clock' => 'make_safe_24',
        ],
        'heating_loss' => [
            'title' => 'No heating or hot water',
            'seasonal' => true,
        ],
        'burst_flood' => [
            'title' => 'Burst pipe or severe flooding',
            'clock' => 'make_safe_24',
        ],
        'sewage_backup' => [
            'title' => 'Sewage overflowing into the living space',
            'clock' => 'make_safe_24',
        ],
        'insecurity' => [
            'title' => 'Door, lock, or ground-floor window unsecured',
            'clock' => 'make_safe_24',
        ],
        'structural' => [
            'title' => 'Collapse or unstable structure',
            'clock' => 'make_safe_24',
        ],
        'contained_leak' => [
            'title' => 'Leak that can be caught in a container',
            'clock' => 'hours_72',
        ],
        'partial_power' => [
            'title' => 'One circuit out, others still work',
            'clock' => 'hours_72',
        ],
        'essential_appliance' => [
            'title' => 'Landlord oven or main hob failed',
            'clock' => 'hours_72',
        ],
        'roof_leak' => [
            'title' => 'Rain coming in, not flooding the room',
            'clock' => 'hours_72',
        ],
        'shared_access' => [
            'title' => 'Broken intercom, buzzer, or communal gate',
            'clock' => 'hours_72',
        ],
    ],

    'clocks' => [
        'make_safe_24' => [
            'sla_hours' => 24,
            'make_safe_hours' => 24,
            'warn_hours' => null,
            'emergency_access' => true,
            'tenant_phrase' => 'We will make the property safe within 24 hours.',
        ],
        'hours_72' => [
            'sla_hours' => 72,
            'make_safe_hours' => null,
            'warn_hours' => 48,
            'emergency_access' => false,
            'tenant_phrase' => 'We will attend within 72 hours.',
        ],
    ],
];
