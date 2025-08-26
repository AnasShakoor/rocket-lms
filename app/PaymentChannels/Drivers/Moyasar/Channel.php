<?php

namespace App\PaymentChannels\Drivers\Moyasar;

use App\Models\Order;
use App\Models\PaymentChannel;
use App\PaymentChannels\BasePaymentChannel;
use App\PaymentChannels\IChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class Channel extends BasePaymentChannel implements IChannel
{
    protected $currency;
    protected $test_mode;
    protected $api_key;
    protected $api_secret;

    protected array $credentialItems = [
        'api_key',
        'api_secret',
    ];

    /**
     * Channel constructor.
     * @param PaymentChannel $paymentChannel
     */
    public function __construct(PaymentChannel $paymentChannel)
    {
        // Moyasar only supports SAR currency, so we force it to SAR
        $this->currency = 'SAR';
        $this->setCredentialItems($paymentChannel);
    }

    public function paymentRequest(Order $order)
    {
        // For Moyasar, we don't redirect - the payment is handled via JavaScript modal
        // Return a response that indicates the payment should be handled client-side
        return response()->json([
            'status' => 'success',
            'message' => 'Payment should be handled via Moyasar modal',
            'order_id' => $order->id
        ]);
    }

    private function makeCallbackUrl(Order $order)
    {
        return url("/payments/verify/Moyasar?order_id={$order->id}");
    }

    /**
     * Check payment status from Moyasar API
     * @param string $paymentId
     * @return array|null
     */
    private function checkPaymentStatus(string $paymentId): ?array
    {
        try {
            $secretKey = getMoyasarSecretKey();

            if (empty($secretKey)) {
                Log::error('🔴 Moyasar: Missing API secret key for payment status check', [
                    'payment_id' => $paymentId
                ]);
                return null;
            }

            Log::info('🔵 Moyasar: Checking payment status', [
                'payment_id' => $paymentId,
                'api_url' => "https://api.moyasar.com/v1/payments/{$paymentId}"
            ]);

            $response = Http::withBasicAuth($secretKey, '')
                ->timeout(30)
                ->get("https://api.moyasar.com/v1/payments/{$paymentId}");

            if ($response->successful()) {
                $paymentData = $response->json();
                Log::info('🟢 Moyasar: Payment status retrieved successfully', [
                    'payment_id' => $paymentId,
                    'status' => $paymentData['status'] ?? 'unknown',
                    'amount' => $paymentData['amount'] ?? 'unknown',
                    'currency' => $paymentData['currency'] ?? 'unknown'
                ]);
                return $paymentData;
            } else {
                Log::error('🔴 Moyasar: Failed to retrieve payment status', [
                    'payment_id' => $paymentId,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('🔴 Moyasar: Exception during payment status check', [
                'payment_id' => $paymentId,
                'exception_message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Capture payment using Moyasar API
     * @param string $paymentId
     * @param float $amount
     * @return array|null
     */
    private function capturePayment(string $paymentId, float $amount): ?array
    {
        try {
            $secretKey = getMoyasarSecretKey();

            if (empty($secretKey)) {
                Log::error('🔴 Moyasar: Missing API secret key for payment capture', [
                    'payment_id' => $paymentId,
                    'amount' => $amount
                ]);
                return null;
            }

            // Validate payment ID format (should be a UUID)
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $paymentId)) {
                Log::error('🔴 Moyasar: Invalid payment ID format', [
                    'payment_id' => $paymentId,
                    'amount' => $amount
                ]);
                return null;
            }

            // Convert amount to smallest currency unit (halaala for SAR)
            $amountInHalaala = (int)($amount * 100);

            Log::info('🔵 Moyasar: Attempting to capture payment', [
                'payment_id' => $paymentId,
                'amount_sar' => $amount,
                'amount_halaala' => $amountInHalaala,
                'api_url' => "https://api.moyasar.com/v1/payments/{$paymentId}/capture"
            ]);

            $response = Http::withBasicAuth($secretKey, '')
                ->timeout(30) // 30 second timeout
                ->post("https://api.moyasar.com/v1/payments/{$paymentId}/capture", [
                    'amount' => $amountInHalaala
                ]);

            if ($response->successful()) {
                $captureData = $response->json();
                Log::info('🟢 Moyasar: Payment captured successfully', [
                    'payment_id' => $paymentId,
                    'capture_response' => $captureData,
                    'amount_captured' => $amountInHalaala,
                    'response_status' => $response->status()
                ]);
                return $captureData;
            } else {
                $errorBody = $response->body();
                $errorData = json_decode($errorBody, true);

                Log::error('🔴 Moyasar: Payment capture failed', [
                    'payment_id' => $paymentId,
                    'response_status' => $response->status(),
                    'response_body' => $errorBody,
                    'error_data' => $errorData,
                    'amount' => $amountInHalaala
                ]);

                // Check if it's a specific error that we can handle
                if (isset($errorData['errors'])) {
                    foreach ($errorData['errors'] as $error) {
                        Log::error('🔴 Moyasar: Capture error detail', [
                            'payment_id' => $paymentId,
                            'error' => $error
                        ]);
                    }
                }

                return null;
            }
        } catch (\Exception $e) {
            Log::error('🔴 Moyasar: Exception during payment capture', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'exception_message' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function verify(Request $request)
    {
        $input = $request->all();

        Log::info('🔵 Moyasar: Payment verification started', [
            'request_data' => $input,
            'timestamp' => now()->toISOString(),
            'user_agent' => $request->header('User-Agent'),
            'ip_address' => $request->ip()
        ]);

        $user = auth()->user();
        $orderId = $request->input('order_id');
        $paymentId = $request->input('payment_id') ?? $request->input('id');
        $status = $request->input('status');
        $message = $request->input('message');

        Log::info('🔵 Moyasar: Payment verification parameters', [
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'status' => $status,
            'message' => $message,
            'user_id' => $user?->id,
            'all_inputs' => $input
        ]);

        if (empty($orderId)) {
            Log::error('🔴 Moyasar: Missing order_id in verification request');
            return null;
        }

        if (empty($paymentId)) {
            Log::warning('🟡 Moyasar: Missing payment_id in verification request', [
                'order_id' => $orderId,
                'available_inputs' => array_keys($input)
            ]);
        }

        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->with('user')
            ->first();

        if (empty($order)) {
            Log::error('🔴 Moyasar: Order not found or unauthorized', [
                'order_id' => $orderId,
                'user_id' => $user?->id,
                'searched_user_id' => $user?->id
            ]);
            return null;
        }

        Log::info('🔵 Moyasar: Order found for verification', [
            'order_id' => $order->id,
            'order_amount' => $order->total_amount,
            'order_currency' => $order->currency,
            'order_status' => $order->status,
            'payment_method' => $order->payment_method
        ]);

        // If we have a status but no payment ID, try to get it from the order's payment data
        if ($status && empty($paymentId) && !empty($order->payment_data)) {
            $paymentData = json_decode($order->payment_data, true);
            if (isset($paymentData['moyasar_payment_id'])) {
                $paymentId = $paymentData['moyasar_payment_id'];
                Log::info('🔵 Moyasar: Retrieved payment_id from order payment data', [
                    'payment_id' => $paymentId,
                    'order_id' => $order->id
                ]);
            }
        }

        // Check payment status from Moyasar callback
        if ($status === 'paid' && $paymentId) {
            Log::info('🟢 Moyasar: Payment confirmed as paid, attempting capture', [
                'payment_id' => $paymentId,
                'status' => $status,
                'message' => $message,
                'order_id' => $order->id
            ]);

            // First, verify the payment status from Moyasar API
            $apiPaymentStatus = $this->checkPaymentStatus($paymentId);

            if ($apiPaymentStatus && $apiPaymentStatus['status'] === 'paid') {
                Log::info('🟢 Moyasar: Payment status confirmed from API, proceeding with capture', [
                    'payment_id' => $paymentId,
                    'api_status' => $apiPaymentStatus['status'],
                    'order_id' => $order->id
                ]);

                // Convert order amount to SAR if needed
                $amountInSAR = $this->convertAmountToSAR($order->total_amount);

                // Capture the payment using Moyasar API
                $captureResult = $this->capturePayment($paymentId, $amountInSAR);

                if ($captureResult) {
                    // Payment captured successfully
                    $order->update([
                        'status' => Order::$paying,
                        'payment_method' => Order::$paymentChannel,
                        'payment_data' => json_encode([
                            'moyasar_payment_id' => $paymentId,
                            'status' => $status,
                            'message' => $message,
                            'verified_at' => now()->toISOString(),
                            'payment_source' => 'moyasar',
                            'captured_at' => now()->toISOString(),
                            'capture_response' => $captureResult,
                            'amount_captured_sar' => $amountInSAR,
                            'api_status_check' => $apiPaymentStatus
                        ])
                    ]);

                    Log::info('🟢 Moyasar: Order updated successfully after payment capture', [
                        'order_id' => $order->id,
                        'new_status' => $order->status,
                        'payment_data_updated' => true,
                        'capture_successful' => true
                    ]);
                } else {
                    // Payment capture failed, but status is paid
                    Log::warning('🟡 Moyasar: Payment status is paid but capture failed', [
                        'payment_id' => $paymentId,
                        'status' => $status,
                        'message' => $message,
                        'order_id' => $order->id,
                        'amount_sar' => $amountInSAR
                    ]);

                    $order->update([
                        'status' => Order::$paying,
                        'payment_method' => Order::$paymentChannel,
                        'payment_data' => json_encode([
                            'moyasar_payment_id' => $paymentId,
                            'status' => $status,
                            'message' => $message,
                            'verified_at' => now()->toISOString(),
                            'payment_source' => 'moyasar',
                            'warning' => 'Payment status is paid but capture failed',
                            'amount_sar' => $amountInSAR,
                            'api_status_check' => $apiPaymentStatus
                        ])
                    ]);
                }
            } else {
                Log::warning('🟡 Moyasar: Payment status mismatch - callback says paid but API says different', [
                    'payment_id' => $paymentId,
                    'callback_status' => $status,
                    'api_status' => $apiPaymentStatus['status'] ?? 'unknown',
                    'order_id' => $order->id
                ]);

                $order->update([
                    'status' => Order::$paying,
                    'payment_method' => Order::$paymentChannel,
                    'payment_data' => json_encode([
                        'moyasar_payment_id' => $paymentId,
                        'status' => $status,
                        'message' => $message,
                        'verified_at' => now()->toISOString(),
                        'payment_source' => 'moyasar',
                        'warning' => 'Payment status mismatch between callback and API',
                        'api_status_check' => $apiPaymentStatus
                    ])
                ]);
            }
        } elseif ($status === 'paid' && empty($paymentId)) {
            // Status is paid but no payment ID - this is a problem
            Log::error('🔴 Moyasar: Payment status is paid but missing payment_id', [
                'order_id' => $order->id,
                'status' => $status,
                'message' => $message,
                'available_inputs' => array_keys($input)
            ]);

            $order->update([
                'status' => Order::$paying,
                'payment_method' => Order::$paymentChannel,
                'payment_data' => json_encode([
                    'status' => $status,
                    'message' => $message,
                    'verified_at' => now()->toISOString(),
                    'payment_source' => 'moyasar',
                    'error' => 'Payment status is paid but missing payment_id',
                    'available_inputs' => array_keys($input)
                ])
            ]);
        } else {
            Log::warning('🟡 Moyasar: Payment status not confirmed as paid', [
                'payment_id' => $paymentId,
                'status' => $status,
                'message' => $message,
                'order_id' => $order->id
            ]);

            // Still mark as paying for now, but log the warning
            $order->update([
                'status' => Order::$paying,
                'payment_method' => Order::$paymentChannel,
                'payment_data' => json_encode([
                    'moyasar_payment_id' => $paymentId,
                    'status' => $status,
                    'message' => $message,
                    'verified_at' => now()->toISOString(),
                    'payment_source' => 'moyasar',
                    'warning' => 'Payment status not confirmed as paid'
                ])
            ]);
        }

        return $order;
    }

    /**
     * Convert amount to SAR currency using the project's currency conversion system
     * @param float $amount
     * @return float
     */
    public function convertAmountToSAR($amount)
    {
        // Get the user's current currency item
        $userCurrencyItem = getUserCurrencyItem();

        // If user is already using SAR, no conversion needed
        if ($userCurrencyItem->currency === 'SAR') {
            return $amount;
        }

        // Convert from user's currency to default currency (usually USD)
        $amountInDefaultCurrency = convertPriceToDefaultCurrency($amount, $userCurrencyItem);

        // Get SAR currency item for conversion
        $sarCurrencyItem = getUserCurrencyItem(null, 'SAR');

        // Convert from default currency to SAR
        $amountInSAR = convertPriceToUserCurrency($amountInDefaultCurrency, $sarCurrencyItem);

        return $amountInSAR;
    }
}
