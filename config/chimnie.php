<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chimnie property data
    |--------------------------------------------------------------------------
    |
    | Test mode returns deterministic UK property records without calling
    | Chimnie. Turn it off and set CHIMNIE_API_KEY to use the live API.
    |
    */
    'test_mode' => filter_var(env('CHIMNIE_TEST_MODE', true), FILTER_VALIDATE_BOOLEAN),
    'api_key' => env('CHIMNIE_API_KEY'),
    'base_url' => env('CHIMNIE_BASE_URL', 'https://api.chimnie.com'),
    'timeout' => (int) env('CHIMNIE_TIMEOUT', 12),
];
