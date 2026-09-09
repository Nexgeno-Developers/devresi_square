<?php

return [
    'postcodes' => [
        'base_url' => env('POSTCODES_IO_BASE_URL', 'https://api.postcodes.io'),
        'timeout' => (int) env('POSTCODES_IO_TIMEOUT', 8),
    ],

    'find_that_postcode' => [
        'base_url' => env('FIND_THAT_POSTCODE_BASE_URL', 'https://findthatpostcode.uk'),
        'timeout' => (int) env('FIND_THAT_POSTCODE_TIMEOUT', 8),
    ],

    /*
    | Free MHCLG Energy Performance Certificate API (Open Data Communities).
    | Register at https://epc.opendatacommunities.org/ — optional; Chimnie
    | and postcodes.io still fill the record when these are empty.
    */
    'epc' => [
        'base_url' => env('EPC_OPEN_DATA_BASE_URL', 'https://epc.opendatacommunities.org'),
        'email' => env('EPC_OPEN_DATA_EMAIL'),
        'api_key' => env('EPC_OPEN_DATA_API_KEY'),
        'timeout' => (int) env('EPC_OPEN_DATA_TIMEOUT', 10),
    ],
];
