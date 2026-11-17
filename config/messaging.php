<?php

return [
    // Kill switch for the whole messaging feature.
    'enabled' => env('MESSAGING_ENABLED', true),

    'body_max' => 5000,

    'attachments_per_hour' => 20,

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
];
