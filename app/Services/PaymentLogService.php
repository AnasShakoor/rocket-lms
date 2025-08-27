<?php

namespace App\Services;

use App\Models\PaymentLog;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentLogService
{
    /**
     * Log Moyasar payment data comprehensively
     */
    public function logMoyasarPayment(array $moyasarPayment, Order $order, Request $request = null): PaymentLog
    {
        try {
            Log::info('PaymentLogService: Starting Moyasar payment logging', [
                'order_id' => $order->id,
                'moyasar_payment_id' => $moyasarPayment['id'] ?? null
            ]);

            // Extract payment data from Moyasar response
            $paymentData = $this->extractMoyasarPaymentData($moyasarPayment, $order);

            // Add request information if available
            if ($request) {
                $paymentData['ip_address'] = $request->ip();
                $paymentData['user_agent'] = $request->userAgent();
            }

            // Create payment log entry
            $paymentLog = PaymentLog::create($paymentData);

            Log::info('PaymentLogService: Moyasar payment logged successfully', [
                'payment_log_id' => $paymentLog->id,
                'order_id' => $order->id,
                'gateway_payment_id' => $paymentLog->gateway_payment_id
            ]);

            return $paymentLog;

        } catch (\Exception $e) {
            Log::error('PaymentLogService: Failed to log Moyasar payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Extract and format Moyasar payment data
     */
    private function extractMoyasarPaymentData(array $moyasarPayment, Order $order): array
    {
        $amount = $moyasarPayment['amount'] ?? 0;
        $currency = $moyasarPayment['currency'] ?? 'SAR';

        // Convert from smallest unit (halalas) to SAR
        $amountInSAR = $currency === 'SAR' ? $amount / 100 : $amount;

        // Calculate surcharges and fees
        $surcharges = $this->calculateMoyasarSurcharges($moyasarPayment, $order);

        return [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'payment_gateway' => 'Moyasar',
            'gateway_payment_id' => $moyasarPayment['id'] ?? null,
            'status' => $moyasarPayment['status'] ?? 'unknown',
            'amount' => $order->total_amount,
            'currency_amount' => $amountInSAR,
            'currency' => $currency,
            'gateway_fee' => $surcharges['gateway_fee'] ?? 0,
            'tax_amount' => $surcharges['tax_amount'] ?? 0,
            'discount_amount' => $order->total_discount ?? 0,
            'surcharge_amount' => $surcharges['surcharge_amount'] ?? 0,
            'total_amount' => $order->total_amount + ($surcharges['total_surcharges'] ?? 0),
            'payment_method' => $this->extractPaymentMethod($moyasarPayment),
            'card_type' => $this->extractCardType($moyasarPayment),
            'card_last4' => $this->extractCardLast4($moyasarPayment),
            'card_brand' => $this->extractCardBrand($moyasarPayment),
            'card_country' => $this->extractCardCountry($moyasarPayment),
            'gateway_response' => $moyasarPayment,
            'metadata' => $this->extractMetadata($moyasarPayment, $order),
            'description' => $moyasarPayment['description'] ?? 'Moyasar payment for Order #' . $order->id,
            'error_message' => $moyasarPayment['error_message'] ?? null,
            'payment_date' => now(),
        ];
    }

    /**
     * Calculate Moyasar surcharges and fees
     */
    private function calculateMoyasarSurcharges(array $moyasarPayment, Order $order): array
    {
        $surcharges = [
            'gateway_fee' => 0,
            'tax_amount' => 0,
            'surcharge_amount' => 0,
            'total_surcharges' => 0
        ];

        // Extract source information for surcharge calculation
        $source = $moyasarPayment['source'] ?? [];

        // Moyasar typically charges 2.5% + 1 SAR for credit card payments
        if (isset($source['type']) && $source['type'] === 'creditcard') {
            $amount = $order->total_amount;
            $gatewayFee = ($amount * 0.025) + 1; // 2.5% + 1 SAR

            $surcharges['gateway_fee'] = round($gatewayFee, 2);
            $surcharges['total_surcharges'] = $surcharges['gateway_fee'];
        }

        // Check if there are any additional fees in the Moyasar response
        if (isset($moyasarPayment['fee'])) {
            $surcharges['gateway_fee'] = $moyasarPayment['fee'];
            $surcharges['total_surcharges'] = $surcharges['gateway_fee'];
        }

        // Check for tax information
        if (isset($moyasarPayment['tax'])) {
            $surcharges['tax_amount'] = $moyasarPayment['tax'];
            $surcharges['total_surcharges'] += $surcharges['tax_amount'];
        }

        // Check for additional surcharges
        if (isset($moyasarPayment['surcharge'])) {
            $surcharges['surcharge_amount'] = $moyasarPayment['surcharge'];
            $surcharges['total_surcharges'] += $surcharges['surcharge_amount'];
        }

        Log::info('PaymentLogService: Moyasar surcharges calculated', [
            'order_id' => $order->id,
            'surcharges' => $surcharges
        ]);

        return $surcharges;
    }

    /**
     * Extract payment method from Moyasar response
     */
    private function extractPaymentMethod(array $moyasarPayment): ?string
    {
        $source = $moyasarPayment['source'] ?? [];
        return $source['type'] ?? null;
    }

    /**
     * Extract card type from Moyasar response
     */
    private function extractCardType(array $moyasarPayment): ?string
    {
        $source = $moyasarPayment['source'] ?? [];
        return $source['brand'] ?? null;
    }

    /**
     * Extract last 4 digits of card from Moyasar response
     */
    private function extractCardLast4(array $moyasarPayment): ?string
    {
        $source = $moyasarPayment['source'] ?? [];
        return $source['last4'] ?? null;
    }

    /**
     * Extract card brand from Moyasar response
     */
    private function extractCardBrand(array $moyasarPayment): ?string
    {
        $source = $moyasarPayment['source'] ?? [];
        return $source['brand'] ?? null;
    }

    /**
     * Extract card country from Moyasar response
     */
    private function extractCardCountry(array $moyasarPayment): ?string
    {
        $source = $moyasarPayment['source'] ?? [];
        return $source['country'] ?? null;
    }

    /**
     * Extract additional metadata from Moyasar response
     */
    private function extractMetadata(array $moyasarPayment, Order $order): array
    {
        $metadata = [
            'order_type' => $order->type ?? null,
            'order_items_count' => $order->orderItems->count() ?? 0,
            'moyasar_created_at' => $moyasarPayment['created_at'] ?? null,
            'moyasar_updated_at' => $moyasarPayment['updated_at'] ?? null,
            'moyasar_metadata' => $moyasarPayment['metadata'] ?? [],
        ];

        // Add order items information
        if ($order->orderItems) {
            $metadata['order_items'] = $order->orderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => $item->webinar_id ? 'webinar' : ($item->product_order_id ? 'product' : 'other'),
                    'amount' => $item->total_amount ?? 0,
                ];
            })->toArray();
        }

        return $metadata;
    }

    /**
     * Get payment logs for a specific order
     */
    public function getOrderPaymentLogs(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentLog::where('order_id', $orderId)->get();
    }

    /**
     * Get payment logs for a specific user
     */
    public function getUserPaymentLogs(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentLog::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get payment logs with surcharges
     */
    public function getPaymentsWithSurcharges(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentLog::where('surcharge_amount', '>', 0)
            ->orWhere('gateway_fee', '>', 0)
            ->orWhere('tax_amount', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get payment summary statistics
     */
    public function getPaymentSummary(): array
    {
        $totalPayments = PaymentLog::where('status', 'paid')->count();
        $totalAmount = PaymentLog::where('status', 'paid')->sum('total_amount');
        $totalSurcharges = PaymentLog::where('status', 'paid')
            ->sum(DB::raw('COALESCE(surcharge_amount, 0) + COALESCE(gateway_fee, 0) + COALESCE(tax_amount, 0)'));

        return [
            'total_payments' => $totalPayments,
            'total_amount' => $totalAmount,
            'total_surcharges' => $totalSurcharges,
            'average_surcharge_per_payment' => $totalPayments > 0 ? $totalSurcharges / $totalPayments : 0
        ];
    }
}
