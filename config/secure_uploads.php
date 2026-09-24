<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Max upload size (bytes)
    |--------------------------------------------------------------------------
    */
    'max_bytes' => (int) env('SECURE_UPLOAD_MAX_BYTES', 15 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Allowed extensions by category
    |--------------------------------------------------------------------------
    */
    'images' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf'],
    'archives' => ['zip'],

    /*
    |--------------------------------------------------------------------------
    | Extension → MIME allowlist (must match both)
    |--------------------------------------------------------------------------
    */
    'mime_map' => [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
        'txt' => ['text/plain'],
        'rtf' => ['application/rtf', 'text/rtf'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Explicitly blocked (defense in depth)
    |--------------------------------------------------------------------------
    */
    'blocked_extensions' => [
        'php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'cgi',
        'html', 'htm', 'shtml', 'svg', 'svgz',
        'js', 'mjs', 'jsx', 'ts',
        'exe', 'bat', 'cmd', 'com', 'msi', 'scr',
        'sh', 'bash', 'ps1', 'vbs', 'wsf',
        'asp', 'aspx', 'jsp', 'py', 'rb', 'pl',
    ],
];
