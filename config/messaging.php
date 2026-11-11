<?php

return [
    // Kill switch for the whole messaging feature.
    'enabled' => env('MESSAGING_ENABLED', true),

    'limits' => [
        'requests_per_hour' => 5,
        'requests_per_day' => 20,
        'new_login_requests_per_day' => 3,
        'max_pending_requests' => 25,
        'messages_per_minute' => 30,
        'attachments_per_hour' => 20,
        'reports_per_day' => 10,
    ],

    // Guardrails after a request is declined, ignored or withdrawn.
    'guardrails' => [
        'request_expires_days' => 30,
        'decline_cooldown_days' => 90,
        'expiry_cooldown_days' => 30,
        'withdraw_cooldown_days' => 30,
        'new_login_days' => 7,
        'mass_decline_threshold' => 5,
        'mass_decline_window_days' => 7,
        'pause_days' => 7,
        'repeat_pause_window_days' => 30,
    ],

    'body_max' => 5000,
    'intro_max' => 500,
    'unsend_minutes' => 15,

    'attachments' => [
        'max_files' => 5,
        'max_kb' => 10240,
        // No archives: only images and office/PDF documents.
        'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'],
        'directory' => 'uploads/private/messages',
    ],

    'poll' => [
        'conversation_seconds' => 10,
        'hidden_seconds' => 60,
        'badge_seconds' => 60,
    ],

    'moderation_context' => 10,

    'retention' => [
        'closed_request_intro_days' => 90,
        'report_days' => 365,
    ],
];
