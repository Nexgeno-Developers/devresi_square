<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        // Operating / business Stripe account — subscriptions and platform fees.
        'key' => env('STRIPE_KEY', env('STRIPE_PUBLISHABLE_TEST')),
        'secret' => env('STRIPE_SECRET', env('STRIPE_TEST_SECRET')),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

        // Kept for the existing accounting invoice test checkout flow.
        'test_key' => env('STRIPE_PUBLISHABLE_TEST'),
        'test_secret' => env('STRIPE_TEST_SECRET'),

        // Client-money Stripe account — tenant rent. Production must use a
        // separate Stripe account whose payouts go to the client/trust bank,
        // not the operating account. Staging may reuse the test keys above.
        'rent' => [
            'key' => env('STRIPE_RENT_KEY', env('STRIPE_KEY', env('STRIPE_PUBLISHABLE_TEST'))),
            'secret' => env('STRIPE_RENT_SECRET', env('STRIPE_SECRET', env('STRIPE_TEST_SECRET'))),
            'webhook_secret' => env('STRIPE_RENT_WEBHOOK_SECRET'),
            'currency' => env('STRIPE_RENT_CURRENCY', 'gbp'),
            // Optional Connect: client-money account ID. Direct charges land
            // rent there; RENT_PAYMENT_FEE_* is taken as application_fee on the
            // business account. Leave empty for two independent Stripe accounts.
            'connected_account_id' => env('STRIPE_RENT_CONNECTED_ACCOUNT_ID'),
            'fee_percent' => env('RENT_PAYMENT_FEE_PERCENT', 0),
            'fee_fixed' => env('RENT_PAYMENT_FEE_FIXED', 0),
            'fee_label' => env('RENT_PAYMENT_FEE_LABEL', 'Card payment fee'),
        ],
    ],

];
