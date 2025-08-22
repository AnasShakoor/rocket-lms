<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Optimization Settings
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for image optimization
    | and error handling to improve page loading performance.
    |
    */

    // Enable/disable image error handling
    'handle_404_errors' => env('IMAGE_HANDLE_404_ERRORS', true),

    // Enable/disable lazy loading
    'enable_lazy_loading' => env('IMAGE_LAZY_LOADING', true),

    // Enable/disable broken image placeholders
    'show_placeholders' => env('IMAGE_SHOW_PLACEHOLDERS', false),

    // Timeout for image loading (in milliseconds)
    'loading_timeout' => env('IMAGE_LOADING_TIMEOUT', 5000),

    // Retry attempts for failed images
    'retry_attempts' => env('IMAGE_RETRY_ATTEMPTS', 1),

    // File extensions to optimize
    'optimized_extensions' => [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'
    ],

    // Exclude paths from optimization
    'exclude_paths' => [
        '/admin/',
        '/api/',
        '/vendor/'
    ],

    // Default placeholder for broken images
    'default_placeholder' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjNmM3NTdkIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SW1hZ2U8L3RleHQ+PC9zdmc+',

    // Console logging level
    'console_log_level' => env('IMAGE_CONSOLE_LOG_LEVEL', 'warn'), // 'info', 'warn', 'error', 'none'
];
