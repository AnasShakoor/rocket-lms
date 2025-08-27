<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentChannel;
use App\PaymentChannels\ChannelManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MoyasarWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Moyasar-Signature');

        // Verify webhook signature
        if (!$this->verifyWebhookSignature($request, $signature)) {
            Log::warning('Moyasar webhook signature verification failed', [
                'payload' => $payload,
                'signature' => $signature
            ]);
            return response('Unauthorized', 401);
        }

        try {
            $paymentId = $payload['id'] ?? null;
            $status = $payload['status'] ?? null;
            $amount = $payload['amount'] ?? null;
            $currency = $payload['currency'] ?? null;

            if (!$paymentId || !$status) {
                Log::warning('Moyasar webhook missing required fields', ['payload' => $payload]);
                return response('Bad Request', 400);
            }

            // Find order by reference_id (Moyasar payment ID)
            $order = Order::where('reference_id', $paymentId)->first();

            if (!$order) {
                Log::warning('Moyasar webhook: Order not found', ['payment_id' => $paymentId]);
                return response('Order not found', 404);
            }

            // Update order status based on payment status
            if ($status === 'paid') {
                // Verify amount matches
                $expectedAmount = (int) ($order->total_amount * 100); // Convert to smallest unit

                if ($amount == $expectedAmount && $currency === $order->currency) {
                    $order->update([
                        'status' => Order::$paying,
                        'payment_data' => json_encode($payload)
                    ]);

                    Log::info('Moyasar webhook: Payment successful', [
                        'order_id' => $order->id,
                        'payment_id' => $paymentId
                    ]);
                } else {
                    Log::warning('Moyasar webhook: Amount/currency mismatch', [
                        'order_id' => $order->id,
                        'expected_amount' => $expectedAmount,
                        'received_amount' => $amount,
                        'expected_currency' => $order->currency,
                        'received_currency' => $currency
                    ]);
                }
            } elseif (in_array($status, ['failed', 'cancelled'])) {
                $order->update([
                    'status' => Order::$fail,
                    'payment_data' => json_encode($payload)
                ]);

                Log::info('Moyasar webhook: Payment failed/cancelled', [
                    'order_id' => $order->id,
                    'payment_id' => $paymentId,
                    'status' => $status
                ]);
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Moyasar webhook error', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return response('Internal Server Error', 500);
        }
    }

    private function verifyWebhookSignature(Request $request, $signature)
    {
        $webhookSecret = config('moyasar.webhook_secret');

        if (empty($webhookSecret)) {
            Log::warning('Moyasar webhook secret not configured');
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
