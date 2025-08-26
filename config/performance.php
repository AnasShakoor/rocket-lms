<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Settings
    |--------------------------------------------------------------------------
    |
    | This file contains performance optimization configurations for the application
    |
    */

    'database' => [
        'query_timeout' => env('DB_QUERY_TIMEOUT', 30),
        'max_connections' => env('DB_MAX_CONNECTIONS', 100),
        'min_connections' => env('DB_MIN_CONNECTIONS', 5),
        'connection_timeout' => env('DB_CONNECTION_TIMEOUT', 10),
    ],

    'cache' => [
        'default_ttl' => env('CACHE_DEFAULT_TTL', 3600), // 1 hour
        'user_ttl' => env('CACHE_USER_TTL', 1800), // 30 minutes
        'webinar_ttl' => env('CACHE_WEBINAR_TTL', 900), // 15 minutes
        'payment_ttl' => env('CACHE_PAYMENT_TTL', 300), // 5 minutes
    ],

    'optimization' => [
        'enable_query_cache' => env('ENABLE_QUERY_CACHE', true),
        'enable_view_cache' => env('ENABLE_VIEW_CACHE', true),
        'enable_route_cache' => env('ENABLE_ROUTE_CACHE', true),
        'enable_config_cache' => env('ENABLE_CONFIG_CACHE', true),
        'enable_optimization' => env('ENABLE_OPTIMIZATION', true),
    ],

    'pagination' => [
        'default_per_page' => env('DEFAULT_PER_PAGE', 20),
        'max_per_page' => env('MAX_PER_PAGE', 100),
    ],
];
