<?php

namespace App\PaymentChannels\Drivers\Tabby;

use App\Models\Order;
use App\Models\PaymentChannel;
use App\PaymentChannels\IChannel;
use App\Services\TabbyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Channel implements IChannel
{
    protected $tabbyService;
    protected $paymentChannel;

    public function __construct(PaymentChannel $paymentChannel)
    {
        $this->paymentChannel = $paymentChannel;
        $this->tabbyService = new TabbyService();
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

            // First, check eligibility
            $eligibilityCheck = $this->tabbyService->checkEligibility($order, $customerData);

            if (!$eligibilityCheck['success']) {
                Log::error('Tabby eligibility check failed', [
                    'order_id' => $order->id,
                    'error' => $eligibilityCheck['error'] ?? 'Unknown error'
                ]);

                throw new \Exception('Tabby eligibility check failed: ' . ($eligibilityCheck['error'] ?? 'Unknown error'));
            }

            if (!$eligibilityCheck['eligible']) {
                $rejectionReason = $eligibilityCheck['rejection_reason'] ?? 'not_available';
                $message = $this->tabbyService->getRejectionMessage($rejectionReason, app()->getLocale());

                Log::warning('Tabby customer not eligible', [
                    'order_id' => $order->id,
                    'rejection_reason' => $rejectionReason,
                    'message' => $message
                ]);

                throw new \Exception($message);
            }

            // Create checkout session
            $checkoutResult = $this->tabbyService->createCheckoutSession($order, $customerData);

            if (!$checkoutResult['success']) {
                Log::error('Tabby checkout session creation failed', [
                    'order_id' => $order->id,
                    'error' => $checkoutResult['error'] ?? 'Unknown error'
                ]);

                throw new \Exception('Tabby checkout session creation failed: ' . ($checkoutResult['error'] ?? 'Unknown error'));
            }

            // Store payment ID in order for verification
            $order->update([
                'payment_data' => array_merge($order->payment_data ?? [], [
                    'tabby_payment_id' => $checkoutResult['payment_id'],
                    'tabby_checkout_created_at' => now(),
                ])
            ]);

            // Return the web URL for redirection
            return $checkoutResult['web_url'];

        } catch (\Exception $e) {
            Log::error('Tabby payment request failed', [
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
            $paymentId = $request->get('payment_id');

            if (!$paymentId) {
                Log::error('Tabby verification failed: Missing payment_id', [
                    'request_data' => $request->all()
                ]);

                return [
                    'success' => false,
                    'error' => 'Missing payment_id parameter'
                ];
            }

            // Verify payment with Tabby
            $verificationResult = $this->tabbyService->verifyPayment($paymentId);

            if (!$verificationResult['success']) {
                Log::error('Tabby payment verification failed', [
                    'payment_id' => $paymentId,
                    'error' => $verificationResult['error'] ?? 'Unknown error'
                ]);

                return [
                    'success' => false,
                    'error' => $verificationResult['error'] ?? 'Payment verification failed'
                ];
            }

            $paymentStatus = $verificationResult['status'] ?? 'unknown';

            // Map Tabby status to our system status
            $statusMapping = [
                'AUTHORIZED' => 'success',
                'REJECTED' => 'failed',
                'EXPIRED' => 'failed',
                'CANCELLED' => 'cancelled'
            ];

            $status = $statusMapping[$paymentStatus] ?? 'pending';

            Log::info('Tabby payment verification completed', [
                'payment_id' => $paymentId,
                'tabby_status' => $paymentStatus,
                'mapped_status' => $status
            ]);

            return [
                'success' => $status === 'success',
                'status' => $status,
                'tabby_status' => $paymentStatus,
                'payment_id' => $paymentId,
                'data' => $verificationResult['data'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Tabby verification exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getCredentialItems(): array
    {
        return [
            'secret_key' => [
                'label' => 'Secret API Key',
                'type' => 'text',
                'required' => true,
            ],
            'merchant_code' => [
                'label' => 'Merchant Code',
                'type' => 'text',
                'required' => true,
            ],
            'test_mode' => [
                'label' => 'Test Mode',
                'type' => 'boolean',
                'required' => false,
                'default' => true,
            ],
        ];
    }
}
