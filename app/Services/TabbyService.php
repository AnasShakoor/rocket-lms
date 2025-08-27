<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\BnplProvider;

class TabbyService
{
    public $apiKey;
    public $apiEndpoint;
    public $merchantCode;
    public $isTest;
    public $publicApiKey;

    public function __construct()
    {
        // Get Tabby configuration from database (normalize provider name: remove spaces, lowercase)
        $tabbyProvider = \App\Models\BnplProvider::query()
            ->whereRaw("REPLACE(LOWER(name), ' ', '') = ?", ['tabby'])
            ->first();

        if ($tabbyProvider) {
            $this->apiKey = $tabbyProvider->secret_api_key ?? 'sk_test_019890d8-6d73-9f99-f50c-05504e1c8756';
            $this->merchantCode = $tabbyProvider->merchant_code ?? 'Riyadhsau';
            $this->publicApiKey = $tabbyProvider->public_api_key ?? 'pk_test_019890d8-6d73-9f99-f50c-05500080d876';

            // Get additional config from the config field
            $config = $tabbyProvider->config ?? [];
            $this->apiEndpoint = $config['api_endpoint'] ?? 'https://api.tabby.ai';
            $this->isTest = $config['test_mode'] ?? true;

            // Log configuration for debugging
            Log::info('Tabby configuration loaded from database', [
                'provider_id' => $tabbyProvider->id,
                'has_api_key' => !empty($this->apiKey),
                'has_merchant_code' => !empty($this->merchantCode),
                'api_endpoint' => $this->apiEndpoint,
                'test_mode' => $this->isTest
            ]);
        } else {
            // Fallback to config file if no database record found
            $this->apiKey = config('services.tabby.secret_key');
            $this->apiEndpoint = config('services.tabby.api_endpoint', 'https://api.tabby.ai');
            $this->merchantCode = config('services.tabby.merchant_code');
            $this->isTest = config('services.tabby.test_mode', true);

            Log::info('Tabby configuration loaded from config files', [
                'has_api_key' => !empty($this->apiKey),
                'has_merchant_code' => !empty($this->merchantCode),
                'api_endpoint' => $this->apiEndpoint,
                'test_mode' => $this->isTest
            ]);
        }
    }

    /**
     * Check if Tabby is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->merchantCode);
    }

    /**
     * Get configuration status
     */
    public function getConfigurationStatus(): array
    {
        $tabbyProvider = \App\Models\BnplProvider::query()
            ->whereRaw("REPLACE(LOWER(name), ' ', '') = ?", ['tabby'])
            ->first();

        if (!$tabbyProvider) {
            return [
                'configured' => false,
                'message' => 'Tabby provider not found in database',
                'missing_fields' => ['provider_record']
            ];
        }

        $missingFields = [];

        if (empty($tabbyProvider->secret_api_key)) {
            $missingFields[] = 'secret_api_key';
        }

        if (empty($tabbyProvider->merchant_code)) {
            $missingFields[] = 'merchant_code';
        }

        return [
            'configured' => empty($missingFields),
            'message' => empty($missingFields) ? 'Tabby is properly configured' : 'Missing required configuration fields',
            'missing_fields' => $missingFields,
            'provider' => $tabbyProvider
        ];
    }

    /**
     * Background pre-scoring check to determine if customer is eligible
     */
    public function checkEligibility(Order $order, array $customerData): array
    {
        // Check if Tabby is configured
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'eligible' => false,
                'error' => 'Tabby is not properly configured. Please contact administrator.',
                'config_status' => $this->getConfigurationStatus()
            ];
        }

        try {
            $payload = [
                'payment' => [
                    'amount' => (string) $order->total_amount,
                    'currency' => $order->currency ?? 'SAR',
                    'description' => 'Order #' . $order->id,
                    'buyer' => [
                        'phone' => $customerData['phone'] ?? $order->user->mobile,
                        'email' => $customerData['email'] ?? $order->user->email,
                        'name' => $customerData['name'] ?? $order->user->full_name,
                        'dob' => optional($order->user->birth_date ?? null, fn($d) =>
                            \Illuminate\Support\Carbon::parse($d)->format('Y-m-d')) ?? '1990-01-01',
                    ],
                    'buyer_history' => [
                        'registered_since' => optional($order->user->created_at ?? now(), fn($d) => \Illuminate\Support\Carbon::parse($d)->toIso8601String()),
                        'loyalty_level' => 0,
                        'wishlist_count' => 0,
                        'is_social_networks_connected' => false,
                        'is_phone_number_verified' => !empty($order->user->mobile),
                        'is_email_verified' => (bool) ($order->user->email_verified_at ?? false),
                    ],
                    'order' => [
                        'tax_amount' => '0.00',
                        'shipping_amount' => '0.00',
                        'discount_amount' => '0.00',
                        'updated_at' => now()->toIso8601String(),
                        'reference_id' => (string) $order->id,
                        'items' => $this->formatOrderItems($order, true),
                    ],
                    'order_history' => [
                        [
                            'purchased_at' => now()->subDays(30)->toIso8601String(),
                            'amount' => (string) $order->total_amount,
                            'payment_method' => 'card',
                            'status' => 'new',
                            'buyer' => [
                                'phone' => $customerData['phone'] ?? $order->user->mobile,
                                'email' => $customerData['email'] ?? $order->user->email,
                                'name' => $customerData['name'] ?? $order->user->full_name,
                                'dob' => optional($order->user->birth_date ?? null, fn($d) => \Illuminate\Support\Carbon::parse($d)->format('Y-m-d')) ?? '1990-01-01',
                            ],
                            'shipping_address' => [
                                'city' => $customerData['city'] ?? 'Riyadh',
                                'address' => $customerData['address'] ?? 'Saudi Arabia',
                                'zip' => $customerData['zip'] ?? '00000',
                            ],
                            'items' => $this->formatOrderItems($order, true),
                        ]
                    ],
                    'shipping_address' => [
                        'city' => $customerData['city'] ?? 'Riyadh',
                        'address' => $customerData['address'] ?? 'Saudi Arabia',
                        'zip' => $customerData['zip'] ?? '00000',
                    ],
                    'meta' => [
                        'order_id' => '#' . (string) $order->id,
                        'customer' => '#user-' . (string) $order->user_id,
                    ],
                    'attachment' => [
                        'body' => json_encode(['flight_reservation_details' => ['pnr' => 'TR9088999', 'itinerary' => [], 'insurance' => [], 'passengers' => [], 'affiliate_name' => 'rocket-lms']]),
                        'content_type' => 'application/vnd.tabby.v1+json',
                    ],
                ],
                'lang' => app()->getLocale() === 'ar' ? 'ar' : 'en',
                'merchant_code' => $this->merchantCode,
                'merchant_urls' => [
                    'success' => route('payments.tabby.success'),
                    'cancel' => route('payments.tabby.cancel'),
                    'failure' => route('payments.tabby.failure'),
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiEndpoint . '/api/v2/checkout', $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Check if response is HTML (authentication/login page)
                if (is_string($data) && str_contains($data, '<!DOCTYPE html>')) {
                    Log::error('Tabby API returned HTML instead of JSON - authentication required for eligibility', [
                        'order_id' => $order->id,
                        'response_preview' => substr($data, 0, 500),
                        'endpoint' => $this->apiEndpoint . '/api/v2/checkout'
                    ]);

                    return [
                        'success' => false,
                        'eligible' => false,
                        'error' => 'Tabby API requires authentication. Please check your API credentials and endpoint configuration.',
                        'details' => 'API returned HTML login page instead of JSON response'
                    ];
                }

                // Validate response data
                if (!is_array($data)) {
                    Log::error('Tabby API returned invalid response format for eligibility', [
                        'order_id' => $order->id,
                        'response' => $response->body()
                    ]);

                    return [
                        'success' => false,
                        'eligible' => false,
                        'error' => 'Invalid API response format'
                    ];
                }

                // Validate response structure
                if (!$this->validateApiResponse($data, 'general')) {
                    Log::warning('Tabby API response structure validation failed for eligibility', [
                        'order_id' => $order->id,
                        'response' => $data
                    ]);
                }

                // Log the response structure for debugging
                Log::info('Tabby API response structure for eligibility', [
                    'order_id' => $order->id,
                    'response_keys' => array_keys($data),
                    'has_configuration' => isset($data['configuration']),
                    'response' => $data
                ]);

                Log::info('Tabby eligibility check successful', [
                    'order_id' => $order->id,
                    'status' => $data['status'] ?? 'unknown',
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'eligible' => $data['status'] === 'created',
                    'status' => $data['status'] ?? 'unknown',
                    'rejection_reason' => $this->getNestedValue($data, 'configuration.products.installments.rejection_reason'),
                    'data' => $data
                ];
            }

            Log::error('Tabby eligibility check failed', [
                'order_id' => $order->id,
                'status_code' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'eligible' => false,
                'error' => 'API request failed',
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Tabby eligibility check exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'eligible' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create checkout session and get payment URL
     */
    public function createCheckoutSession(Order $order, array $customerData): array
    {
        // Check if Tabby is configured
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Tabby is not properly configured. Please contact administrator.',
                'config_status' => $this->getConfigurationStatus()
            ];
        }

        try {
            $payload = [
                'payment' => [
                    'amount' => (string) $order->total_amount,
                    'currency' => $order->currency ?? 'SAR',
                    'description' => 'Order #' . $order->id,
                    'buyer' => [
                        'name' => $customerData['name'] ?? $order->user->full_name,
                        'email' => $customerData['email'] ?? $order->user->email,
                        'phone' => $customerData['phone'] ?? $order->user->mobile,
                    ],
                    'shipping_address' => [
                        'city' => $customerData['city'] ?? 'Riyadh',
                        'address' => $customerData['address'] ?? 'Saudi Arabia',
                        'zip' => $customerData['zip'] ?? '00000',
                    ],
                    'order' => [
                        'reference_id' => (string) $order->id,
                        'updated_at' => now()->toIso8601String(),
                        'tax_amount' => '0.00',
                        'shipping_amount' => '0.00',
                        'discount_amount' => '0.00',
                        'items' => $this->formatOrderItems($order, true),
                    ],
                ],
                'lang' => app()->getLocale() === 'ar' ? 'ar' : 'en',
                'merchant_code' => $this->merchantCode,
                'merchant_urls' => [
                    'success' => route('payments.tabby.success'),
                    'cancel' => route('payments.tabby.cancel'),
                    'failure' => route('payments.tabby.failure'),
                ],
                'token' => null,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiEndpoint . '/api/v2/checkout', $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Check if response is HTML (authentication/login page)
                if (is_string($data) && str_contains($data, '<!DOCTYPE html>')) {
                    Log::error('Tabby API returned HTML instead of JSON - authentication required', [
                        'order_id' => $order->id,
                        'response_preview' => substr($data, 0, 500),
                        'endpoint' => $this->apiEndpoint . '/api/v2/checkout'
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Tabby API requires authentication. Please check your API credentials and endpoint configuration.',
                        'details' => 'API returned HTML login page instead of JSON response'
                    ];
                }

                // Validate response data
                if (!is_array($data)) {
                    Log::error('Tabby API returned invalid response format', [
                        'order_id' => $order->id,
                        'response' => $response->body()
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Invalid API response format'
                    ];
                }

                // Validate response structure
                if (!$this->validateApiResponse($data, 'checkout')) {
                    Log::warning('Tabby API response structure validation failed', [
                        'order_id' => $order->id,
                        'response' => $data
                    ]);
                }

                // Log the response structure for debugging
                Log::info('Tabby API response structure', [
                    'order_id' => $order->id,
                    'response_keys' => array_keys($data),
                    'has_configuration' => isset($data['configuration']),
                    'has_payment' => isset($data['payment']),
                    'response' => $data
                ]);

                if ($data['status'] === 'created') {
                    Log::info('Tabby checkout session created', [
                        'order_id' => $order->id,
                        'payment_id' => $this->getNestedValue($data, 'payment.id'),
                        'web_url' => $this->extractWebUrl($data)
                    ]);

                    $webUrl = $this->extractWebUrl($data);

                    if (!$webUrl) {
                        Log::warning('Tabby checkout created but web_url not found', [
                            'order_id' => $order->id,
                            'response' => $data
                        ]);
                    }

                    // Log the complete response structure for debugging
                    Log::info('Tabby checkout complete response', [
                        'order_id' => $order->id,
                        'payment_id' => $this->getNestedValue($data, 'payment.id'),
                        'web_url' => $webUrl,
                        'merchant_urls' => [
                            'success' => route('payments.tabby.success'),
                            'cancel' => route('payments.tabby.cancel'),
                            'failure' => route('payments.tabby.failure'),
                        ],
                        'full_response' => $data
                    ]);

                    return [
                        'success' => true,
                        'payment_id' => $this->getNestedValue($data, 'payment.id'),
                        'web_url' => $webUrl,
                        'data' => $data
                    ];
                } else {
                    Log::warning('Tabby checkout session not created', [
                        'order_id' => $order->id,
                        'status' => $data['status'] ?? 'unknown',
                        'response' => $data
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Checkout session not created',
                        'status' => $data['status'] ?? 'unknown'
                    ];
                }
            }

            Log::error('Tabby checkout session creation failed', [
                'order_id' => $order->id,
                'status_code' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'API request failed',
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Tabby checkout session creation exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $paymentId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->apiEndpoint . '/api/v2/payments/' . $paymentId);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Tabby payment verification successful', [
                    'payment_id' => $paymentId,
                    'status' => $data['status'] ?? 'unknown',
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'status' => $data['status'] ?? 'unknown',
                    'data' => $data
                ];
            }

            Log::error('Tabby payment verification failed', [
                'payment_id' => $paymentId,
                'status_code' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'API request failed',
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Tabby payment verification exception', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format order items for Tabby API
     */
    protected function formatOrderItems(Order $order, bool $detailed = false): array
    {
        $items = [];

        foreach ($order->orderItems as $item) {
            if ($detailed) {
                $items[] = [
                    'reference_id' => (string) ($item->id ?? $order->id),
                    'title' => $item->title ?? 'Course',
                    'description' => $item->title ?? 'Course',
                    'quantity' => 1,
                    'unit_price' => (string) ($item->amount ?? $order->total_amount),
                    'image_url' => url('/assets/default/img/default/course.png'),
                    'product_url' => url('/'),
                    'category' => 'education',
                    'is_refundable' => true,
                ];
            } else {
                $items[] = [
                    'title' => $item->title ?? 'Course',
                    'quantity' => 1,
                    'unit_price' => $item->amount,
                    'category' => 'education'
                ];
            }
        }

        return $items;
    }

    /**
     * Safely get nested array value
     */
    private function getNestedValue(array $array, string $path, $default = null)
    {
        $keys = explode('.', $path);
        $value = $array;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $default;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Get Tabby redirect URL with proper parameters
     */
    public function getRedirectUrl(string $type, string $paymentId): string
    {
        $baseUrl = url('/payments/tabby/' . $type);

        // Add the payment ID as a query parameter
        $redirectUrl = $baseUrl . '?payment_id=' . $paymentId;

        Log::info('Tabby redirect URL generated', [
            'type' => $type,
            'payment_id' => $paymentId,
            'redirect_url' => $redirectUrl
        ]);

        return $redirectUrl;
    }

    /**
     * Extract web URL from Tabby response with fallback strategies
     */
    private function extractWebUrl(array $data): ?string
    {
        // Try multiple possible paths for web_url
        $possiblePaths = [
            'configuration.available_products.installments.0.web_url',
            'configuration.available_products.0.web_url',
            'configuration.installments.0.web_url',
            'configuration.web_url',
            'web_url'
        ];

        foreach ($possiblePaths as $path) {
            $webUrl = $this->getNestedValue($data, $path);
            if ($webUrl) {
                return $webUrl;
            }
        }

        return null;
    }

    /**
     * Validate Tabby API response structure
     */
    private function validateApiResponse(array $data, string $context = 'general'): bool
    {
        $requiredFields = ['status'];

        if ($context === 'checkout') {
            $requiredFields[] = 'payment';
        }

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                Log::warning("Tabby API response missing required field: {$field}", [
                    'context' => $context,
                    'available_fields' => array_keys($data),
                    'response' => $data
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Test API connection and credentials
     */
    public function testApiConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->apiEndpoint . '/api/v2/payments');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'API connection successful',
                    'status_code' => $response->status()
                ];
            }

            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'error' => 'API authentication failed - invalid API key',
                    'status_code' => $response->status()
                ];
            }

            if ($response->status() === 403) {
                return [
                    'success' => false,
                    'error' => 'API access forbidden - check permissions',
                    'status_code' => $response->status()
                ];
            }

            return [
                'success' => false,
                'error' => 'API connection failed',
                'status_code' => $response->status(),
                'response' => $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'API connection exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get rejection message based on reason
     */
    public function getRejectionMessage(string $reason, string $locale = 'en'): string
    {
        $messages = [
            'en' => [
                'not_available' => 'Sorry, Tabby is unable to approve this purchase. Please use an alternative payment method for your order.',
                'order_amount_too_high' => 'This purchase is above your current spending limit with Tabby, try a smaller cart or use another payment method',
                'order_amount_too_low' => 'The purchase amount is below the minimum amount required to use Tabby, try adding more items or use another payment method'
            ],
            'ar' => [
                'not_available' => 'نأسف، تابي غير قادرة على الموافقة على هذه العملية. الرجاء استخدام طريقة دفع أخرى.',
                'order_amount_too_high' => 'قيمة الطلب تفوق الحد الأقصى المسموح به حاليًا مع تابي. يُرجى تخفيض قيمة السلة أو استخدام وسيلة دفع أخرى.',
                'order_amount_too_low' => 'قيمة الطلب أقل من الحد الأدنى المطلوب لاستخدام خدمة تابي. يُرجى زيادة قيمة الطلب أو استخدام وسيلة دفع أخرى.'
            ]
        ];

        $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';

        return $messages[$locale][$reason] ?? $messages[$locale]['not_available'];
    }
}
