<?php

namespace App\PaymentChannels\Drivers\Moyasar;

use App\Models\Order;
use App\Models\PaymentChannel;
use App\PaymentChannels\BasePaymentChannel;
use App\PaymentChannels\IChannel;
use App\Services\PaymentLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Channel extends BasePaymentChannel implements IChannel
{
    protected $currency;
    protected $test_mode;
    protected $secret_key;
    protected $publishable_key;
    protected $order_session_key;

    protected array $credentialItems = [
        'secret_key',
        'publishable_key',
        'test_mode',
    ];

    public function __construct(PaymentChannel $paymentChannel)
    {
        $this->currency = 'SAR';
        $this->order_session_key = 'moyasar.payments.order_id';
        $this->setCredentialItems($paymentChannel);

        Log::info('Moyasar Channel initialized', [
            'currency' => $this->currency,
            'test_mode' => $this->test_mode,
            'has_secret_key' => !empty($this->secret_key),
            'has_publishable_key' => !empty($this->publishable_key),
            'payment_channel_id' => $paymentChannel->id
        ]);
    }

    public function paymentRequest(Order $order)
    {
        Log::info('Moyasar payment form request started', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'amount' => $order->total_amount,
            'currency' => $this->currency,
            'test_mode' => $this->test_mode
        ]);

        $user = $order->user;
        $amount = $this->makeAmountByCurrency($order->total_amount, $this->currency);
        $convertedAmount = $this->convertToSmallestUnit($amount, $this->currency);

        Log::info('Moyasar amount conversion', [
            'original_amount' => $order->total_amount,
            'converted_amount' => $amount,
            'smallest_unit_amount' => $convertedAmount
        ]);

        // Return payment form configuration instead of making direct API call
        $paymentFormData = [
            'amount' => $convertedAmount,
            'currency' => $this->currency,
            'description' => 'Payment for Order #' . $order->id,
            'publishable_api_key' => $this->publishable_key,
            'callback_url' => $this->makeCallbackUrl('success'),
            'back_url' => $this->makeCallbackUrl('cancel'),
            'supported_networks' => ['visa', 'mastercard', 'mada'],
            'methods' => ['creditcard'],
                'order_id' => $order->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
            'user_name' => $user->full_name,
        ];

        Log::info('Moyasar payment form data prepared', [
            'payment_form_data' => $paymentFormData,
            'order_id' => $order->id
        ]);

        // Store order in session for later verification
        session()->put($this->order_session_key, $order->id);

        return $paymentFormData;
    }

    /**
     * Verify payment from frontend form callback
     */
    public function verifyPayment($paymentId, $orderId = null)
    {
        Log::info('Moyasar payment verification started', [
            'payment_id' => $paymentId,
            'order_id' => $orderId
        ]);

        try {
            // Fetch payment details from Moyasar
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->secret_key . ':'),
                'Content-Type' => 'application/json',
            ])->get($this->getApiUrl() . '/payments/' . $paymentId);

            Log::info('Moyasar payment fetch response', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'payment_id' => $paymentId
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch payment: ' . ($response->body() ?? 'Unknown error'));
            }

                $payment = $response->json();

            // Verify payment status
            if ($payment['status'] !== 'paid') {
                throw new \Exception('Payment not completed. Status: ' . $payment['status']);
            }

            // Verify amount and currency
            $expectedAmount = $this->convertToSmallestUnit(
                $this->makeAmountByCurrency($orderId ? Order::find($orderId)->total_amount : 0, $this->currency),
                $this->currency
            );

            if ($payment['amount'] != $expectedAmount) {
                throw new \Exception('Amount mismatch. Expected: ' . $expectedAmount . ', Got: ' . $payment['amount']);
            }

            if ($payment['currency'] !== $this->currency) {
                throw new \Exception('Currency mismatch. Expected: ' . $this->currency . ', Got: ' . $payment['currency']);
            }

            Log::info('Moyasar payment verification successful', [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'amount' => $payment['amount'],
                'currency' => $payment['currency'],
                'status' => $payment['status']
            ]);

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'amount' => $payment['amount'],
                'currency' => $payment['currency'],
                'status' => $payment['status'],
                'metadata' => $payment['metadata'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Moyasar payment verification failed', [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error_message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate card data before sending to Moyasar
     */
    private function validateCardData($order)
    {
        $paymentMethod = $this->getPaymentMethod($order);

        // Only validate card data for card-based payment methods
        if (in_array($paymentMethod, ['creditcard', 'debitcard', 'mada'])) {
            $errors = [];

            // Check card number
            $cardNumber = $this->getCardNumber($order);
            if (empty($cardNumber) || strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
                $errors[] = 'Invalid card number';
            }

            // Check expiry month
            $month = $this->getCardMonth($order);
            if (empty($month) || !is_numeric($month) || $month < 1 || $month > 12) {
                $errors[] = 'Invalid expiry month (must be 1-12)';
            }

            // Check expiry year
            $year = $this->getCardYear($order);
            if (empty($year) || !is_numeric($year) || $year < date('y') || $year > date('y') + 20) {
                $errors[] = 'Invalid expiry year';
            }

            // Check CVC
            $cvc = $this->getCardCvc($order);
            if (empty($cvc) || !is_numeric($cvc) || strlen($cvc) < 3 || strlen($cvc) > 4) {
                $errors[] = 'Invalid CVC (must be 3-4 digits)';
            }

            if (!empty($errors)) {
                throw new \Exception('Card validation failed: ' . implode(', ', $errors));
            }

            Log::info('Card data validation passed', [
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'card_number_length' => strlen($cardNumber),
                'expiry_month' => $month,
                'expiry_year' => $year,
                'cvc_length' => strlen($cvc)
            ]);
        }
    }

    /**
     * Build dynamic payment source based on user and order data
     */
    private function buildPaymentSource($user, $order)
    {
        // Get payment method from order or user preferences
        $paymentMethod = $this->getPaymentMethod($order);

        // Get card type if it's a card payment
        $cardType = $this->getCardType($order);

        // Build source object based on payment method
        switch ($paymentMethod) {
            case 'creditcard':
            case 'debitcard':
                return [
                    'type' => $paymentMethod,
                    'company' => $cardType,
                    'name' => $user->full_name,
                    'number' => $this->getCardNumber($order),
                    'month' => $this->getCardMonth($order),
                    'year' => $this->getCardYear($order),
                    'cvc' => $this->getCardCvc($order),
                    'gateway_id' => $this->generateGatewayId($order),
                    'reference_number' => $this->getReferenceNumber($order),
                    'token' => $this->getCardToken($order),
                    'message' => $this->getPaymentMessage($order),
                    'transaction_url' => $this->getTransactionUrl($order)
                ];

            case 'mada':
                return [
                    'type' => 'mada',
                    'company' => 'mada',
                    'name' => $user->full_name,
                    'number' => $this->getCardNumber($order),
                    'month' => $this->getCardMonth($order),
                    'year' => $this->getCardYear($order),
                    'cvc' => $this->getCardCvc($order),
                    'gateway_id' => $this->generateGatewayId($order),
                    'reference_number' => $this->getReferenceNumber($order),
                    'token' => $this->getCardToken($order),
                    'message' => $this->getPaymentMessage($order),
                    'transaction_url' => $this->getTransactionUrl($order)
                ];

            case 'applepay':
                return [
                    'type' => 'applepay',
                    'company' => 'apple',
                    'name' => $user->full_name,
                    'gateway_id' => $this->generateGatewayId($order),
                    'reference_number' => $this->getReferenceNumber($order),
                    'token' => $this->getCardToken($order),
                    'message' => $this->getPaymentMessage($order),
                    'transaction_url' => $this->getTransactionUrl($order)
                ];

            case 'stcpay':
                return [
                    'type' => 'stcpay',
                    'company' => 'stc',
                    'name' => $user->full_name,
                    'gateway_id' => $this->generateGatewayId($order),
                    'reference_number' => $this->getReferenceNumber($order),
                    'token' => $this->getCardToken($order),
                    'message' => $this->getPaymentMessage($order),
                    'transaction_url' => $this->getTransactionUrl($order)
                ];

            default:
                // Default to credit card if no specific method is set
                return [
                    'type' => 'creditcard',
                    'company' => $cardType ?: 'visa',
                    'name' => $user->full_name,
                    'number' => $this->getCardNumber($order),
                    'month' => $this->getCardMonth($order),
                    'year' => $this->getCardYear($order),
                    'cvc' => $this->getCardCvc($order),
                    'gateway_id' => $this->generateGatewayId($order),
                    'reference_number' => $this->getReferenceNumber($order),
                    'token' => $this->getCardToken($order),
                    'message' => $this->getPaymentMessage($order),
                    'transaction_url' => $this->getTransactionUrl($order)
                ];
        }
    }

    /**
     * Get payment method from order or user preferences
     */
    private function getPaymentMethod($order)
    {
        // Check if order has payment method specified
        if (!empty($order->payment_method)) {
            return $order->payment_method;
        }

        // Check user preferences
        if (!empty($order->user->preferred_payment_method)) {
            return $order->user->preferred_payment_method;
        }

        // Check order metadata
        if (!empty($order->metadata) && is_array($order->metadata)) {
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
            if (!empty($metadata['payment_method'])) {
                return $metadata['payment_method'];
            }
        }

        // Default payment method
        return 'creditcard';
    }

    /**
     * Get card type from order or user data
     */
    private function getCardType($order)
    {
        // Check if order has card type specified
        if (!empty($order->card_type)) {
            return $order->card_type;
        }

        // Check user preferences
        if (!empty($order->user->preferred_card_type)) {
            return $order->user->preferred_card_type;
        }

        // Check order metadata
        if (!empty($order->metadata) && is_array($order->metadata)) {
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
            if (!empty($metadata['card_type'])) {
                return $metadata['card_type'];
            }
        }

        // Default card type
        return 'visa';
    }

    /**
     * Get actual card number (not masked) for API calls
     */
    private function getCardNumber($order)
    {
        // Check if order has card number
        if (!empty($order->card_number)) {
            return $order->card_number;
        }

        // Check order metadata
        if (!empty($order->metadata) && is_array($order->metadata)) {
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
            if (!empty($metadata['card_number'])) {
                return $metadata['card_number'];
            }
        }

        // For testing purposes, return a valid test card number
        // In production, this should always have a real card number
        if ($this->test_mode) {
            return '4111111111111111'; // Valid test Visa card
        }

        // If no card number and not in test mode, throw error
        throw new \Exception('Card number is required for payment');
    }

    /**
     * Get card expiry month
     */
    private function getCardMonth($order)
    {
        // Check if order has card month
        if (!empty($order->card_month)) {
            return $order->card_month;
        }

        // Check order metadata
        if (!empty($order->metadata) && is_array($order->metadata)) {
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
            if (!empty($metadata['card_month'])) {
                return $metadata['card_month'];
            }
        }

        // For testing purposes, return a valid test month
        if ($this->test_mode) {
            return '12'; // December
        }

        // If no month and not in test mode, throw error
        throw new \Exception('Card expiry month is required for payment');
    }

    /**
     * Get card expiry year
     */
    private function getCardYear($order)
    {
        // Check if order has card year
        if (!empty($order->card_year)) {
            return $order->card_year;
        }

        // Check order metadata
        if (!empty($order->metadata) && is_array($order->metadata)) {
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
            if (!empty($metadata['card_year'])) {
                return $metadata['card_year'];
            }
        }

        // For testing purposes, return a valid test year
        if ($this->test_mode) {
            return '25'; // 2025
        }

        // If no year and not in test mode, throw error
        throw new \Exception('Card expiry year is required for payment');
    }

    /**
     * Get card CVC
     */
    private function getCardCvc($order)
    {
        // Check if order has card CVC
        if (!empty($order->card_cvc)) {
            return $order->card_cvc;
        }

        // Check order metadata
        if (!empty($order->metadata) && is_array($order->metadata)) {
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
            if (!empty($metadata['card_cvc'])) {
                return $metadata['card_cvc'];
            }
        }

        // For testing purposes, return a valid test CVC
        if ($this->test_mode) {
            return '123'; // Valid test CVC
        }

        // If no CVC and not in test mode, throw error
        throw new \Exception('Card CVC is required for payment');
    }

    /**
     * Get masked card number for security
     */
    private function getMaskedCardNumber($order)
    {
        // Check if order has card number
        if (!empty($order->card_number)) {
            return $this->maskCardNumber($order->card_number);
        }

        // Check order metadata
        if (!empty($order->metadata) && is_array($order->metadata)) {
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
            if (!empty($metadata['card_number'])) {
                return $this->maskCardNumber($metadata['card_number']);
            }
        }

        // Return default masked number for testing
        return 'XXXX-XXXX-XXXX-1111';
    }

    /**
     * Mask card number for security
     */
    private function maskCardNumber($cardNumber)
    {
        $cardNumber = preg_replace('/[^0-9]/', '', $cardNumber);

        if (strlen($cardNumber) >= 4) {
            return 'XXXX-XXXX-XXXX-' . substr($cardNumber, -4);
        }

        return 'XXXX-XXXX-XXXX-XXXX';
    }

    /**
     * Generate unique gateway ID
     */
    private function generateGatewayId($order)
    {
        $prefix = 'moyasar_';
        $type = $this->getPaymentMethod($order);
        $hash = substr(md5($order->id . time() . rand(1000, 9999)), 0, 20);

        return $prefix . $type . '_' . $hash;
    }

    /**
     * Get reference number from order
     */
    private function getReferenceNumber($order)
    {
        // Check if order has reference number
        if (!empty($order->reference_number)) {
            return $order->reference_number;
        }

        // Check order metadata
        if (!empty($order->metadata) && is_array($order->metadata)) {
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
            if (!empty($metadata['reference_number'])) {
                return $metadata['reference_number'];
            }
        }

        // Generate reference number
        return 'REF-' . $order->id . '-' . time();
    }

    /**
     * Get card token if available
     */
    private function getCardToken($order)
    {
        // Check if order has card token
        if (!empty($order->card_token)) {
            return $order->card_token;
        }

        // Check order metadata
        if (!empty($order->metadata) && is_array($order->metadata)) {
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
            if (!empty($metadata['card_token'])) {
                return $metadata['card_token'];
            }
        }

        return null;
    }

    /**
     * Get payment message based on environment and status
     */
    private function getPaymentMessage($order)
    {
        $env = $this->test_mode ? 'Test Environment' : 'Production Environment';
        $status = 'Succeeded!';

        // Check if order has custom message
        if (!empty($order->payment_message)) {
            return $order->payment_message . ' (' . $env . ')';
        }

        // Check order metadata
        if (!empty($order->metadata) && is_array($order->metadata)) {
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
            if (!empty($metadata['payment_message'])) {
                return $metadata['payment_message'] . ' (' . $env . ')';
            }
        }

        return $status . ' (' . $env . ')';
    }

    /**
     * Get transaction URL if available
     */
    private function getTransactionUrl($order)
    {
        // Check if order has transaction URL
        if (!empty($order->transaction_url)) {
            return $order->transaction_url;
        }

        // Check order metadata
        if (!empty($order->metadata) && is_array($order->metadata)) {
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
            if (!empty($metadata['transaction_url'])) {
                return $metadata['transaction_url'];
            }
        }

        return null;
    }

    /**
     * Make callback URL for Moyasar
     */
    private function makeCallbackUrl($status)
    {
        $url = url("/payments/verify/Moyasar?status={$status}");
        Log::info('Moyasar callback URL generated', [
            'status' => $status,
            'url' => $url
        ]);
        return $url;
    }

    /**
     * Get Moyasar API URL
     */
    private function getApiUrl()
    {
        // Moyasar uses the same API endpoint for both test and live
        // The difference is in the API keys used
        $url = 'https://api.moyasar.com/v1';
        Log::info('Moyasar API URL', [
            'url' => $url,
            'test_mode' => $this->test_mode
        ]);
        return $url;
    }

    /**
     * Convert amount to smallest unit (halalas for SAR)
     */
    private function convertToSmallestUnit($amount, $currency)
    {
        // Convert amount to smallest unit (halalas for SAR)
        // 1 SAR = 100 halalas
        $converted = (int) ($amount * 100);
        Log::info('Moyasar amount conversion to smallest unit', [
            'original_amount' => $amount,
            'currency' => $currency,
            'converted_amount' => $converted
        ]);
        return $converted;
    }

    /**
     * Verify payment from request (implements IChannel interface)
     */
    public function verify(Request $request)
    {
        $data = $request->all();
        $status = $data['status'] ?? null;
        $paymentId = $data['id'] ?? null;

        Log::info('Moyasar payment verification started', [
            'request_data' => $data,
            'status' => $status,
            'payment_id' => $paymentId
        ]);

        $user = auth()->user();

        if (!$paymentId) {
            Log::warning('Moyasar verification: No payment ID provided');
            return null;
        }

        try {
            // Verify payment with Moyasar API
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->secret_key . ':'),
            ])->get($this->getApiUrl() . '/payments/' . $paymentId);

            Log::info('Moyasar verification API response', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                $payment = $response->json();

                // Find order by reference_id
                $order = Order::where('reference_id', $paymentId)
                    ->where('user_id', $user->id)
                    ->first();

                // If not found by reference_id, try to find by session
                if (!$order) {
                    $orderId = session($this->order_session_key);
                    if ($orderId) {
                        $order = Order::where('id', $orderId)
                            ->where('user_id', $user->id)
                            ->first();
                    }
                }

                if (!empty($order)) {
                    $orderStatus = Order::$fail;

                    // Check if payment is successful
                    if ($payment['status'] === 'paid' && $payment['amount'] == $this->convertToSmallestUnit($order->total_amount, $this->currency)) {
                        $orderStatus = Order::$paying;

                                                // Update order with payment reference and mark as paid
                        $paymentData = $payment;
                        $paymentData['channel_name'] = 'Moyasar';

                        $order->update([
                            'status' => Order::$paid,
                            'reference_id' => $paymentId,
                            'payment_data' => json_encode($paymentData),
                            'payment_method' => 'payment_channel'
                        ]);

                        // Log comprehensive payment data including surcharges
                        try {
                            $paymentLogService = new PaymentLogService();
                            $paymentLog = $paymentLogService->logMoyasarPayment($payment, $order);

                            Log::info('Moyasar payment logged comprehensively', [
                                'order_id' => $order->id,
                                'payment_id' => $paymentId,
                                'payment_log_id' => $paymentLog->id,
                                'surcharges' => $paymentLog->payment_summary
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Moyasar payment logging failed', [
                                'order_id' => $order->id,
                                'payment_id' => $paymentId,
                                'error' => $e->getMessage()
                            ]);
                        }

                        // Process payment accounting
                        try {
                            $this->processPaymentAccounting($order);
                            Log::info('Moyasar payment accounting processed successfully', [
                                'order_id' => $order->id,
                                'payment_id' => $paymentId
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Moyasar payment accounting failed', [
                                'order_id' => $order->id,
                                'payment_id' => $paymentId,
                                'error' => $e->getMessage()
                            ]);
                        }

                        Log::info('Moyasar payment successful - order marked as paid', [
                            'order_id' => $order->id,
                            'payment_id' => $paymentId,
                            'amount' => $payment['amount']
                        ]);
                    } else {
                    $order->update([
                        'status' => $orderStatus,
                        'payment_data' => json_encode($payment)
                        ]);
                    }

                    Log::info('Moyasar payment verification result', [
                        'moyasar_payment_id' => $paymentId,
                        'order_id' => $order->id,
                        'moyasar_status' => $payment['status'],
                        'moyasar_amount' => $payment['amount'],
                        'expected_amount' => $this->convertToSmallestUnit($order->total_amount, $this->currency),
                        'order_status' => $orderStatus
                    ]);

                    return $order;
                } else {
                    Log::warning('Moyasar verification: Order not found', [
                        'moyasar_payment_id' => $paymentId,
                        'user_id' => $user->id
                    ]);
                }
            }

        } catch (\Exception $e) {
            // Log error if needed
            Log::error('Moyasar payment verification error', [
                'error_message' => $e->getMessage(),
                'payment_id' => $paymentId,
                'trace' => $e->getTraceAsString()
            ]);
        }

        return null;
    }

        /**
     * Process payment accounting for successful orders
     */
    private function processPaymentAccounting($order)
    {
        Log::info('Processing Moyasar payment accounting', [
            'order_id' => $order->id,
            'order_type' => $order->type
        ]);

        // Handle different order types
        if ($order->type === Order::$webinar) {
            // Process webinar purchase
            foreach ($order->orderItems as $orderItem) {
                if (!empty($orderItem->webinar_id)) {
                    // Mark webinar as purchased
                    $orderItem->webinar->update(['status' => 'active']);

                    // Send purchase notifications
                    if (method_exists($orderItem->webinar, 'sendPurchaseNotifications')) {
                        $orderItem->webinar->sendPurchaseNotifications($order);
                    }

                    // Remove purchased webinar from cart
                    $this->removeFromCart($order->user_id, 'webinar', $orderItem->webinar_id);
                }
            }
        } elseif ($order->type === Order::$product) {
            // Process product purchase
            foreach ($order->orderItems as $orderItem) {
                if (!empty($orderItem->product_order_id)) {
                    // Mark product order as paid
                    $orderItem->productOrder->update(['status' => 'paid']);

                    // Remove purchased product from cart
                    $this->removeFromCart($order->user_id, 'product', $orderItem->product_order_id);
                }
            }
        }

        Log::info('Moyasar payment accounting completed', [
            'order_id' => $order->id
        ]);
    }

    /**
     * Remove purchased items from cart
     */
    private function removeFromCart($userId, $type, $itemId)
    {
        try {
            $cart = \App\Models\Cart::where('creator_id', $userId);

            if ($type === 'webinar') {
                $cart->where('webinar_id', $itemId);
            } elseif ($type === 'product') {
                $cart->where('product_order_id', $itemId);
            }

            $cartItems = $cart->get();

            if ($cartItems->isNotEmpty()) {
                foreach ($cartItems as $cartItem) {
                    $cartItem->delete();
                }

                Log::info('Items removed from cart after successful purchase', [
                    'user_id' => $userId,
                    'type' => $type,
                    'item_id' => $itemId,
                    'removed_count' => $cartItems->count()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to remove items from cart', [
                'user_id' => $userId,
                'type' => $type,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get available test card numbers for different card types
     */
    public static function getTestCardNumbers()
    {
        return [
            'visa' => [
                '4111111111111111', // Valid test Visa
                '4000000000000002', // Declined test Visa
                '4000000000009995'  // Insufficient funds test Visa
            ],
            'mastercard' => [
                '5555555555554444', // Valid test Mastercard
                '5105105105105100', // Declined test Mastercard
                '5105105105105101'  // Insufficient funds test Mastercard
            ],
            'mada' => [
                '4462030000000000', // Valid test Mada
                '4462030000000001', // Declined test Mada
                '4462030000000002'  // Insufficient funds test Mada
            ],
            'amex' => [
                '378282246310005', // Valid test American Express
                '378282246310006', // Declined test American Express
                '378282246310007'  // Insufficient funds test American Express
            ]
        ];
    }
}


