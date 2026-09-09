<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Landlord MVP route restrictions
    |--------------------------------------------------------------------------
    |
    | Paying landlord workspace operators may only use listed route names.
    | A name matches if it equals an entry or starts with a prefix entry that
    | ends in a dot. Blocked prefixes always win.
    |
    */
    'blocked_route_name_prefixes' => [
        'admin.accounting.',
        'backend.accounting.',
        'accounting.',
        'backend.saas.plans',
        'backend.saas.addons',
        'backend.saas.accounts',
        'backend.saas.subscriptions',
        'admin.registrations.',
        'admin.designations.',
        'admin.branches.',
        'roles.',
        'staffs.',
        'business_settings.',
        'smtp_settings.',
        'env_key_update.',
        'admin.companies.',
        'admin.offers.',
        'admin.invoices.',
        'backend.transactions.',
        'backend.transaction_categories.',
        'cache.clear',
        'customer.statements',
    ],

    'enabled_route_name_prefixes' => [
        'backend.dashboard',
        'backend.home',
        'backend.logout',
        'backend.notifications.',
        'backend.accounts.',
        'backend.billing.',
        'backend.events.',
        'backend.properties.',
        'backend.saas.subscription.checkout',
        'admin.onboarding.landlord.',
        'admin.properties.',
        'admin.tenancies.',
        'admin.users.',
        'admin.owner-groups.',
        'admin.documents.',
        'admin.notes.',
        'admin.property_repairs.',
        'admin.work_orders.',
        'admin.compliance.',
        'admin.portal-access.',
        'admin.finance.',
        'admin.getUsersByProperty',
        'admin.getTenantsByProperty',
        'admin.bank_details.',
        'properties.search',
        'tenant.',
        'logout',
        'user.profile',
        'notes.upload_image',
    ],

    'wizard_enabled' => filter_var(env('LANDLORD_WIZARD_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'wizard_steps' => [
        1 => 'Find your property',
        2 => 'Property details',
        3 => 'Review your Property Passport',
    ],
];
