<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Moyasar Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Moyasar payment gateway.
    | You can set your API keys and other settings here.
    |
    */

    'test_mode' => env('MOYASAR_TEST_MODE', true),

    'secret_key' => env('MOYASAR_SECRET_KEY', ''),

    'publishable_key' => env('MOYASAR_PUBLISHABLE_KEY', ''),

    'api_url' => env('MOYASAR_API_URL', 'https://api.moyasar.com/v1'),

    'webhook_secret' => env('MOYASAR_WEBHOOK_SECRET', ''),

        'supported_currencies' => [
        'SAR', // Saudi Riyal - Only supported currency for Moyasar
    ],

    'default_currency' => 'SAR',
];
