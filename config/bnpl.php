<?php

return [
    /*
    |--------------------------------------------------------------------------
    | BNPL Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Buy Now, Pay Later functionality
    | including default settings, fee structures, and provider configurations.
    |
    */

    // Default VAT percentage
    'default_vat_percentage' => env('BNPL_DEFAULT_VAT', 15),

    // Default installment count
    'default_installment_count' => env('BNPL_DEFAULT_INSTALLMENTS', 4),

    // Minimum order amount for BNPL
    'min_order_amount' => env('BNPL_MIN_ORDER_AMOUNT', 50.00),

    // Maximum order amount for BNPL
    'max_order_amount' => env('BNPL_MAX_ORDER_AMOUNT', 10000.00),

    // Maximum concurrent BNPL payments per user
    'max_concurrent_payments' => env('BNPL_MAX_CONCURRENT_PAYMENTS', 3),

    // BNPL fee range (min and max percentages)
    'fee_range' => [
        'min' => env('BNPL_MIN_FEE_PERCENTAGE', 5.00),
        'max' => env('BNPL_MAX_FEE_PERCENTAGE', 15.00),
    ],

    // Installment options
    'installment_options' => [2, 3, 4, 6, 12],

    // Payment frequency (in days)
    'payment_frequency_days' => env('BNPL_PAYMENT_FREQUENCY_DAYS', 30),

    // Grace period for late payments (in days)
    'grace_period_days' => env('BNPL_GRACE_PERIOD_DAYS', 7),

    // Late payment fee percentage
    'late_payment_fee_percentage' => env('BNPL_LATE_PAYMENT_FEE', 5.00),

    // Auto-decline settings
    'auto_decline' => [
        'enabled' => env('BNPL_AUTO_DECLINE_ENABLED', true),
        'max_overdue_payments' => env('BNPL_MAX_OVERDUE_PAYMENTS', 2),
        'max_failed_payments' => env('BNPL_MAX_FAILED_PAYMENTS', 3),
    ],

    // Notification settings
    'notifications' => [
        'payment_reminder_days' => [7, 3, 1], // Days before due date
        'overdue_notification_days' => [1, 3, 7, 14], // Days after due date
        'enable_sms' => env('BNPL_ENABLE_SMS_NOTIFICATIONS', false),
        'enable_email' => env('BNPL_ENABLE_EMAIL_NOTIFICATIONS', true),
    ],

    // Integration settings
    'integrations' => [
        'webhook_url' => env('BNPL_WEBHOOK_URL'),
        'api_timeout' => env('BNPL_API_TIMEOUT', 30),
        'retry_attempts' => env('BNPL_RETRY_ATTEMPTS', 3),
    ],

    // Risk management
    'risk_management' => [
        'enabled' => env('BNPL_RISK_MANAGEMENT_ENABLED', true),
        'credit_score_threshold' => env('BNPL_CREDIT_SCORE_THRESHOLD', 600),
        'max_debt_to_income_ratio' => env('BNPL_MAX_DEBT_TO_INCOME_RATIO', 0.4),
        'fraud_detection_enabled' => env('BNPL_FRAUD_DETECTION_ENABLED', true),
    ],

    // Reporting settings
    'reporting' => [
        'enable_analytics' => env('BNPL_ENABLE_ANALYTICS', true),
        'retention_days' => env('BNPL_REPORTING_RETENTION_DAYS', 2555), // 7 years
        'export_formats' => ['csv', 'xlsx', 'pdf'],
    ],

    // UI/UX settings
    'ui' => [
        'show_bnpl_badge' => env('BNPL_SHOW_BADGE', true),
        'badge_text' => env('BNPL_BADGE_TEXT', 'Pay in installments'),
        'checkout_button_text' => env('BNPL_CHECKOUT_BUTTON_TEXT', 'Pay Later'),
        'enable_comparison_tool' => env('BNPL_ENABLE_COMPARISON', true),
    ],

    // Provider-specific settings
    'providers' => [
        'tabby' => [
            'enabled' => env('BNPL_TABBY_ENABLED', true),
            'api_key' => env('BNPL_TABBY_API_KEY'),
            'secret_key' => env('BNPL_TABBY_SECRET_KEY'),
            'environment' => env('BNPL_TABBY_ENVIRONMENT', 'sandbox'),
        ],
        'tamara' => [
            'enabled' => env('BNPL_TAMARA_ENABLED', true),
            'api_key' => env('BNPL_TAMARA_API_KEY'),
            'secret_key' => env('BNPL_TAMARA_SECRET_KEY'),
            'environment' => env('BNPL_TAMARA_ENVIRONMENT', 'sandbox'),
        ],
        'spotii' => [
            'enabled' => env('BNPL_SPOTII_ENABLED', true),
            'api_key' => env('BNPL_SPOTII_API_KEY'),
            'secret_key' => env('BNPL_SPOTII_SECRET_KEY'),
            'environment' => env('BNPL_SPOTII_ENVIRONMENT', 'sandbox'),
        ],
    ],

    // Error handling
    'error_handling' => [
        'log_errors' => env('BNPL_LOG_ERRORS', true),
        'show_user_friendly_errors' => env('BNPL_SHOW_USER_FRIENDLY_ERRORS', true),
        'fallback_payment_method' => env('BNPL_FALLBACK_PAYMENT_METHOD', 'credit_card'),
    ],

    // Testing settings
    'testing' => [
        'enabled' => env('BNPL_TESTING_ENABLED', false),
        'test_user_ids' => explode(',', env('BNPL_TEST_USER_IDS', '')),
        'mock_providers' => env('BNPL_MOCK_PROVIDERS', false),
    ],
];
