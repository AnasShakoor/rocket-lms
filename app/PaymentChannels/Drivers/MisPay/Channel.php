<?php

namespace App\PaymentChannels\Drivers\MisPay;

use App\Models\Order;
use App\Models\PaymentChannel;
use App\PaymentChannels\IChannel;
use App\Services\MisPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Channel implements IChannel
{
    protected $mispayService;
    protected $paymentChannel;

    public function __construct(PaymentChannel $paymentChannel)
    {
        $this->paymentChannel = $paymentChannel;
        $this->mispayService = new MisPayService();
    }

    public function paymentRequest(Order $order)
    {
        try {
            // Get customer data from order
            $customerData = [
                'email' => $order->user->email,
                'phone' => $order->user->mobile,
                'name' => $order->user->full_name,
            ];

            // Skip eligibility check and go directly to checkout creation

            // Create checkout session
            $checkoutResult = $this->mispayService->createCheckoutSession($order, $customerData);

            if (!$checkoutResult['success']) {
                Log::error('MisPay checkout session creation failed', [
                    'order_id' => $order->id,
                    'error' => $checkoutResult['error'] ?? 'Unknown error'
                ]);

                throw new \Exception('MisPay checkout session creation failed: ' . ($checkoutResult['error'] ?? 'Unknown error'));
            }

            // Store payment ID in order for verification
            $order->update([
                'payment_data' => array_merge($order->payment_data ?? [], [
                    'mispay_checkout_id' => $checkoutResult['checkout_id'],
                    'mispay_checkout_created_at' => now(),
                ])
            ]);

            // Return the web URL for redirection
            return $checkoutResult['web_url'];

        } catch (\Exception $e) {
            Log::error('MisPay payment request failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function verify(Request $request)
    {
        try {
            $checkoutId = $request->get('checkout_id') ?? $request->get('id');

            if (!$checkoutId) {
                Log::error('MisPay verification failed: Missing checkout_id', [
                    'request_data' => $request->all()
                ]);
                throw new \Exception('Missing checkout ID for verification');
            }

            // Verify payment with MisPay
            $verificationResult = $this->mispayService->verifyPayment($checkoutId);

            if (!$verificationResult['success']) {
                Log::error('MisPay payment verification failed', [
                    'checkout_id' => $checkoutId,
                    'error' => $verificationResult['error']
                ]);
                throw new \Exception('Payment verification failed: ' . $verificationResult['error']);
            }

            // Check payment status
            $status = $verificationResult['status'];

            if ($status === 'completed' || $status === 'success') {
                return [
                    'status' => 'success',
                    'message' => 'Payment completed successfully',
                    'data' => $verificationResult
                ];
            } elseif ($status === 'pending') {
                return [
                    'status' => 'pending',
                    'message' => 'Payment is pending',
                    'data' => $verificationResult
                ];
            } elseif ($status === 'failed' || $status === 'cancelled') {
                return [
                    'status' => 'failed',
                    'message' => 'Payment failed or was cancelled',
                    'data' => $verificationResult
                ];
            } else {
                return [
                    'status' => 'unknown',
                    'message' => 'Payment status is unknown',
                    'data' => $verificationResult
                ];
            }

        } catch (\Exception $e) {
            Log::error('MisPay payment verification exception', [
                'checkout_id' => $checkoutId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function getStatus()
    {
        return $this->paymentChannel->status;
    }

    public function getTitle()
    {
        return $this->paymentChannel->title;
    }

    public function getIcon()
    {
        return $this->paymentChannel->icon;
    }

    public function getDescription()
    {
        return $this->paymentChannel->description;
    }

    public function getCredentialItems(): array
    {
        return [
            'app_id' => [
                'type' => 'text',
                'label' => 'App ID',
                'required' => true,
                'description' => 'Your MisPay App ID'
            ],
            'app_secret' => [
                'type' => 'password',
                'label' => 'App Secret',
                'required' => true,
                'description' => 'Your MisPay App Secret'
            ],
            'api_endpoint' => [
                'type' => 'text',
                'label' => 'API Endpoint',
                'required' => false,
                'description' => 'MisPay API endpoint (default: https://api.mispay.co/sandbox/v1/api)',
                'default' => 'https://api.mispay.co/sandbox/v1/api'
            ],
            'test_mode' => [
                'type' => 'boolean',
                'label' => 'Test Mode',
                'required' => false,
                'description' => 'Enable test mode for sandbox testing',
                'default' => true
            ]
        ];
    }

    public function getShowTestModeToggle(): bool
    {
        return true;
    }
}
