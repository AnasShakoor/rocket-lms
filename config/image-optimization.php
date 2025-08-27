<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for image optimization
    | and fallback handling, especially for localhost development.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Image Optimization
    |--------------------------------------------------------------------------
    |
    | Enable or disable image optimization features
    |
    */

    'enabled' => env('IMAGE_OPTIMIZATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Localhost Detection
    |--------------------------------------------------------------------------
    |
    | Domains that should be treated as localhost for optimization
    |
    */

    'localhost_domains' => [
        'localhost',
        '127.0.0.1',
        '.test',
        '.local',
        'laragon.test',
        'rocket-lms.test',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Fallback Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for handling missing images
    |
    */

    'fallbacks' => [
        'enabled' => env('IMAGE_FALLBACKS_ENABLED', true),
        'show_placeholder' => env('IMAGE_SHOW_PLACEHOLDER', true),
        'placeholder_text' => env('IMAGE_PLACEHOLDER_TEXT', '📷 Image'),
        'placeholder_style' => env('IMAGE_PLACEHOLDER_STYLE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lazy Loading Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for lazy loading images
    |
    */

    'lazy_loading' => [
        'enabled' => env('IMAGE_LAZY_LOADING', true),
        'threshold' => env('IMAGE_LAZY_THRESHOLD', 50), // pixels from viewport
        'root_margin' => env('IMAGE_LAZY_ROOT_MARGIN', '50px 0px'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for image performance optimization
    |
    */

    'performance' => [
        'add_decoding' => env('IMAGE_ADD_DECODING', true),
        'add_fetchpriority' => env('IMAGE_ADD_FETCHPRIORITY', true),
        'preload_critical' => env('IMAGE_PRELOAD_CRITICAL', true),
        'webp_conversion' => env('IMAGE_WEBP_CONVERSION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Placeholder Images
    |--------------------------------------------------------------------------
    |
    | SVG placeholder images for different content types
    |
    */

    'placeholders' => [
        'course' => [
            'width' => 300,
            'height' => 200,
            'background' => '#f3f4f6',
            'text_color' => '#6b7280',
            'text' => 'COURSE',
        ],
        'product' => [
            'width' => 300,
            'height' => 200,
            'background' => '#f3f4f6',
            'text_color' => '#6b7280',
            'text' => 'PRODUCT',
        ],
        'user' => [
            'width' => 100,
            'height' => 100,
            'background' => '#6b7280',
            'text_color' => '#f3f4f6',
            'text' => '👤',
        ],
        'default' => [
            'width' => 300,
            'height' => 200,
            'background' => '#f3f4f6',
            'text_color' => '#6b7280',
            'text' => 'Image',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Critical Images
    |--------------------------------------------------------------------------
    |
    | Images that should be preloaded for better performance
    |
    */

    'critical_images' => [
        '/assets/default/img/logo.png',
        '/assets/default/img/favicon.ico',
        '/assets/default/img/hero-bg.jpg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | How to handle image loading errors
    |
    */

    'error_handling' => [
        'hide_broken_images' => env('IMAGE_HIDE_BROKEN', true),
        'show_error_message' => env('IMAGE_SHOW_ERROR_MSG', false),
        'log_errors' => env('IMAGE_LOG_ERRORS', true),
        'retry_attempts' => env('IMAGE_RETRY_ATTEMPTS', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive Images
    |--------------------------------------------------------------------------
    |
    | Settings for responsive image handling
    |
    */

    'responsive' => [
        'enabled' => env('IMAGE_RESPONSIVE', true),
        'breakpoints' => [
            'xs' => 480,
            'sm' => 768,
            'md' => 1024,
            'lg' => 1200,
            'xl' => 1920,
        ],
        'aspect_ratios' => [
            '16:9' => 56.25,
            '4:3' => 75,
            '1:1' => 100,
            '3:2' => 66.67,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compression Settings
    |--------------------------------------------------------------------------
    |
    | Image compression and quality settings
    |
    */

    'compression' => [
        'quality' => env('IMAGE_QUALITY', 85),
        'format' => env('IMAGE_FORMAT', 'auto'), // auto, webp, jpg, png
        'progressive' => env('IMAGE_PROGRESSIVE', true),
        'optimize' => env('IMAGE_OPTIMIZE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Image caching configuration
    |
    */

    'cache' => [
        'enabled' => env('IMAGE_CACHE_ENABLED', true),
        'ttl' => env('IMAGE_CACHE_TTL', 86400), // 24 hours
        'prefix' => env('IMAGE_CACHE_PREFIX', 'img_opt_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    |
    | Performance monitoring for images
    |
    */

    'monitoring' => [
        'enabled' => env('IMAGE_MONITORING', true),
        'track_loading_times' => env('IMAGE_TRACK_LOADING', true),
        'track_error_rates' => env('IMAGE_TRACK_ERRORS', true),
        'slow_threshold' => env('IMAGE_SLOW_THRESHOLD', 1000), // ms
    ],

];
