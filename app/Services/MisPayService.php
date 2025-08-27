<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\BnplProvider;

class MisPayService
{
    protected $appId;
    protected $appSecret;
    protected $baseUrl;
    protected $isTest;
    protected $accessToken;

    public function __construct()
    {
        // Get MisPay configuration from database (normalize provider name: remove spaces, lowercase)
        $mispayProvider = \App\Models\BnplProvider::query()
            ->whereRaw("REPLACE(LOWER(name), ' ', '') = ?", ['mispay'])
            ->first();

        if ($mispayProvider) {
            // MIS Pay uses app_id and app_secret_key fields
            $this->appId = $mispayProvider->app_id;
            $this->appSecret = $mispayProvider->app_secret_key;

            // Get additional config from the config field
            $config = $mispayProvider->config ?? [];
            $this->baseUrl = $config['api_endpoint'] ?? 'https://api.mispay.co/sandbox/v1/api';
            $this->isTest = $config['test_mode'] ?? true;

            // Log configuration for debugging
            Log::info('MisPay configuration loaded from database', [
                'provider_id' => $mispayProvider->id,
                'has_app_id' => !empty($this->appId),
                'has_app_secret' => !empty($this->appSecret),
                'base_url' => $this->baseUrl,
                'test_mode' => $this->isTest
            ]);
        } else {
            // Fallback to config file if no database record found
            $this->appId = config('services.mispay.app_id');
            $this->baseUrl = config('services.mispay.base_url', 'https://api.mispay.co/sandbox/v1/api');
            $this->appSecret = config('services.mispay.app_secret');
            $this->isTest = config('services.mispay.test_mode', true);

            Log::info('MisPay configuration loaded from config files', [
                'has_app_id' => !empty($this->appId),
                'has_app_secret' => !empty($this->appSecret),
                'base_url' => $this->baseUrl,
                'test_mode' => $this->isTest
            ]);
        }
    }

    /**
     * Check if MisPay is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->appId) && !empty($this->appSecret);
    }

    /**
     * Get configuration status
     */
    public function getConfigurationStatus(): array
    {
        $mispayProvider = \App\Models\BnplProvider::query()
            ->whereRaw("REPLACE(LOWER(name), ' ', '') = ?", ['mispay'])
            ->first();

        if (!$mispayProvider) {
            return [
                'configured' => false,
                'message' => 'MisPay provider not found in database',
                'missing_fields' => ['provider_record']
            ];
        }

        $missingFields = [];

        if (empty($mispayProvider->app_id)) {
            $missingFields[] = 'app_id';
        }

        if (empty($mispayProvider->app_secret_key)) {
            $missingFields[] = 'app_secret_key';
        }

        return [
            'configured' => empty($missingFields),
            'message' => empty($missingFields) ? 'MisPay is properly configured' : 'Missing required configuration fields',
            'missing_fields' => $missingFields,
            'provider' => $mispayProvider
        ];
    }

    /**
     * Get access token from MisPay
     */
    private function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            $response = Http::withHeaders([
                'x-app-secret' => $this->appSecret,
                'x-app-id' => $this->appId,
            ])->get($this->baseUrl . '/token');

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['result']['token'])) {
                    $decryptedToken = $this->decrypt($responseData['result']['token'], $this->appSecret);
                    $tokenData = json_decode($decryptedToken, true);

                    if (isset($tokenData['token'])) {
                        $this->accessToken = $tokenData['token'];
                        return $this->accessToken;
                    }
                }
            }

            Log::error('MisPay token request failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('MisPay token request exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Background pre-scoring check to determine if customer is eligible
     */
    public function checkEligibility(Order $order, array $customerData): array
    {
        // Check if MisPay is configured
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'eligible' => false,
                'error' => 'MisPay is not properly configured. Please contact administrator.',
                'config_status' => $this->getConfigurationStatus()
            ];
        }

        try {
            // For MisPay, we'll assume eligibility based on basic criteria
            // You can implement more sophisticated eligibility logic here
            $eligible = $this->isCustomerEligible($order, $customerData);

            return [
                'success' => true,
                'eligible' => $eligible,
                'message' => $eligible ? 'Customer is eligible for MisPay installments' : 'Customer is not eligible for MisPay installments',
                'rejection_reason' => $eligible ? null : 'basic_criteria_not_met',
                'installment_options' => $eligible ? $this->getInstallmentOptions($order) : []
            ];

        } catch (\Exception $e) {
            Log::error('MisPay eligibility check failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'eligible' => false,
                'error' => 'Eligibility check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if customer meets basic eligibility criteria
     */
    private function isCustomerEligible(Order $order, array $customerData): bool
    {
        // Basic eligibility checks
        $minAmount = 50; // Minimum order amount
        $maxAmount = 50000; // Maximum order amount

        if ($order->total_amount < $minAmount || $order->total_amount > $maxAmount) {
            return false;
        }

        // Check if customer has valid email and phone
        if (empty($customerData['email']) || empty($customerData['phone'])) {
            return false;
        }

        // Add more eligibility criteria as needed
        return true;
    }

    /**
     * Get available installment options
     */
    private function getInstallmentOptions(Order $order): array
    {
        $amount = $order->total_amount;

        // MisPay typically offers 3-12 month installments
        $options = [];

        if ($amount >= 100) {
            $options[] = [
                'months' => 3,
                'monthly_payment' => round($amount / 3, 2),
                'total_amount' => $amount,
                'fees' => 0
            ];
        }

        if ($amount >= 200) {
            $options[] = [
                'months' => 6,
                'monthly_payment' => round($amount / 6, 2),
                'total_amount' => $amount,
                'fees' => 0
            ];
        }

        if ($amount >= 500) {
            $options[] = [
                'months' => 12,
                'monthly_payment' => round($amount / 12, 2),
                'total_amount' => $amount,
                'fees' => 0
            ];
        }

        return $options;
    }

    /**
     * Create checkout session with MisPay
     */
    public function createCheckoutSession(Order $order, array $customerData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'MisPay is not properly configured'
            ];
        }

        try {
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to obtain MisPay access token'
                ];
            }

            $payload = [
                'orderId' => (string) $order->id,
                'purchaseAmount' => $order->total_amount,
                'purchaseCurrency' => $order->currency ?? 'SAR'
            ];

            $response = Http::withHeaders([
                'x-app-secret' => $this->appSecret,
                'x-app-id' => $this->appId,
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/start-checkout', $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['result']['url'])) {
                    return [
                        'success' => true,
                        'checkout_id' => $responseData['result']['id'] ?? null,
                        'web_url' => $responseData['result']['url'],
                        'payment_id' => $responseData['result']['id'] ?? null
                    ];
                }
            }

            Log::error('MisPay checkout session creation failed', [
                'order_id' => $order->id,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create MisPay checkout session'
            ];

        } catch (\Exception $e) {
            Log::error('MisPay checkout session creation exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Checkout session creation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $checkoutId): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'MisPay is not properly configured'
            ];
        }

        try {
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to obtain MisPay access token'
                ];
            }

            $response = Http::withHeaders([
                'x-app-secret' => $this->appSecret,
                'x-app-id' => $this->appId,
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/checkout/' . $checkoutId);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['result'])) {
                    $result = $responseData['result'];

                    return [
                        'success' => true,
                        'status' => $result['status'] ?? 'unknown',
                        'payment_id' => $result['id'] ?? $checkoutId,
                        'order_id' => $result['orderId'] ?? null,
                        'amount' => $result['purchaseAmount'] ?? null,
                        'currency' => $result['purchaseCurrency'] ?? null,
                        'created_at' => $result['createdAt'] ?? null,
                        'updated_at' => $result['updatedAt'] ?? null
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Failed to verify payment status'
            ];

        } catch (\Exception $e) {
            Log::error('MisPay payment verification exception', [
                'checkout_id' => $checkoutId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Payment verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Track checkout status
     */
    public function trackCheckout(string $checkoutId): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'MisPay is not properly configured'
            ];
        }

        try {
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to obtain MisPay access token'
                ];
            }

            $response = Http::withHeaders([
                'x-app-secret' => $this->appSecret,
                'x-app-id' => $this->appId,
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/track-checkout/' . $checkoutId);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['result'])) {
                    $result = $responseData['result'];

                    return [
                        'success' => true,
                        'status' => $result['status'] ?? 'unknown',
                        'checkout_id' => $result['id'] ?? $checkoutId,
                        'order_id' => $result['orderId'] ?? null,
                        'amount' => $result['purchaseAmount'] ?? null,
                        'currency' => $result['purchaseCurrency'] ?? null,
                        'created_at' => $result['createdAt'] ?? null,
                        'updated_at' => $result['updatedAt'] ?? null
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Failed to track checkout status'
            ];

        } catch (\Exception $e) {
            Log::error('MisPay checkout tracking exception', [
                'checkout_id' => $checkoutId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Checkout tracking failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * End checkout session
     */
    public function endCheckoutSession(string $checkoutId): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'MisPay is not properly configured'
            ];
        }

        try {
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to obtain MisPay access token'
                ];
            }

            $response = Http::withHeaders([
                'x-app-secret' => $this->appSecret,
                'x-app-id' => $this->appId,
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->put($this->baseUrl . '/checkout/' . $checkoutId . '/end');

            if ($response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'message' => 'Checkout session ended successfully',
                    'response' => $responseData
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to end checkout session'
            ];

        } catch (\Exception $e) {
            Log::error('MisPay end checkout session exception', [
                'checkout_id' => $checkoutId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'End checkout session failed: ' . $e->getMessage()
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
                'basic_criteria_not_met' => 'Sorry, you do not meet the basic eligibility criteria for MisPay installments.',
                'amount_too_low' => 'Order amount is too low for MisPay installments. Minimum amount required: SAR 50.',
                'amount_too_high' => 'Order amount is too high for MisPay installments. Maximum amount allowed: SAR 50,000.',
                'missing_contact_info' => 'Please provide valid email and phone number to use MisPay installments.',
                'not_available' => 'MisPay installments are not available for this order.',
                'default' => 'Sorry, you are not eligible for MisPay installments at this time.'
            ],
            'ar' => [
                'basic_criteria_not_met' => 'عذراً، لا تستوفي المعايير الأساسية للأهلية لقسط MisPay.',
                'amount_too_low' => 'مبلغ الطلب منخفض جداً لقسط MisPay. الحد الأدنى المطلوب: 50 ريال.',
                'amount_too_high' => 'مبلغ الطلب مرتفع جداً لقسط MisPay. الحد الأقصى المسموح: 50,000 ريال.',
                'missing_contact_info' => 'يرجى تقديم بريد إلكتروني ورقم هاتف صحيحين لاستخدام قسط MisPay.',
                'not_available' => 'قسط MisPay غير متاح لهذا الطلب.',
                'default' => 'عذراً، لست مؤهلاً لقسط MisPay في هذا الوقت.'
            ]
        ];

        $locale = $locale === 'ar' ? 'ar' : 'en';
        return $messages[$locale][$reason] ?? $messages[$locale]['default'];
    }

    /**
     * Encrypt data using MisPay encryption method
     */
    private function encrypt(string $plaintext, string $passphrase): string
    {
        $salt = openssl_random_pseudo_bytes(16);
        $nonce = openssl_random_pseudo_bytes(12);
        $key = hash_pbkdf2("sha256", $passphrase, $salt, 40000, 32, true);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, 1, $nonce, $tag);

        return base64_encode($salt . $nonce . $ciphertext . $tag);
    }

    /**
     * Decrypt data using MisPay decryption method
     */
    private function decrypt(string $ciphertext, string $passphrase): string
    {
        $input = base64_decode($ciphertext);
        $salt = substr($input, 0, 16);
        $nonce = substr($input, 16, 12);
        $ciphertext = substr($input, 28, -16);
        $tag = substr($input, -16);
        $key = hash_pbkdf2("sha256", $passphrase, $salt, 40000, 32, true);

        return openssl_decrypt($ciphertext, 'aes-256-gcm', $key, 1, $nonce, $tag);
    }
}
