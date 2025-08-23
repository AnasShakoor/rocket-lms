<?php

namespace App\PaymentChannels\Drivers\Bnpl;

use App\Models\Order;
use App\Models\PaymentChannel;
use App\PaymentChannels\Channel;
use App\Services\BnplPaymentService;
use Illuminate\Http\Request;

class BnplChannel extends Channel
{
    protected $bnplService;

    public function __construct(PaymentChannel $paymentChannel)
    {
        parent::__construct($paymentChannel);
        $this->bnplService = new BnplPaymentService();
    }

    public function paymentRequest(Order $order)
    {
        try {
            // Validate that this is a BNPL order
            if ($order->payment_method !== Order::$bnpl) {
                throw new \Exception('Invalid payment method for BNPL channel');
            }

            // Get BNPL provider from order
            $providerName = $order->bnpl_provider;
            if (!$providerName) {
                throw new \Exception('BNPL provider not specified in order');
            }

            // Validate eligibility
            $eligibility = $this->bnplService->validateEligibility(
                $order->user_id,
                $order->total_amount,
                $providerName
            );

            if (!$eligibility['eligible']) {
                throw new \Exception('BNPL eligibility check failed: ' . $eligibility['reason']);
            }

            // Process BNPL payment
            $result = $this->bnplService->processBnplPayment(
                $order->user_id,
                $order->orderItems->where('webinar_id', '!=', null)->first()?->webinar_id,
                $order->orderItems->where('bundle_id', '!=', null)->first()?->bundle_id,
                $order->total_amount,
                $providerName,
                $order->installment_count
            );

            if (!$result['success']) {
                throw new \Exception($result['error']);
            }

            // Update order status to paid
            $order->update([
                'status' => Order::$paid
            ]);

            // Return success response
            return [
                'status' => 'success',
                'message' => 'BNPL payment processed successfully',
                'order_id' => $order->id,
                'payment_details' => $result['payment_breakdown'] ?? []
            ];

        } catch (\Exception $e) {
            // Log error
            \Log::error('BNPL payment request failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'error' => $e->getMessage()
            ]);

            // Update order status to failed
            $order->update([
                'status' => Order::$fail
            ]);

            throw $e;
        }
    }

    public function verify(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $order = Order::findOrFail($orderId);

            if ($order->payment_method !== Order::$bnpl) {
                throw new \Exception('Invalid payment method for verification');
            }

            // Check if order is already paid
            if ($order->status === Order::$paid) {
                return [
                    'status' => 'success',
                    'message' => 'BNPL payment already processed',
                    'order_id' => $order->id
                ];
            }

            // For BNPL, we consider the order as verified if it exists and has BNPL details
            if ($order->bnpl_provider && $order->bnpl_fee) {
                return [
                    'status' => 'success',
                    'message' => 'BNPL order verified',
                    'order_id' => $order->id,
                    'bnpl_details' => $order->getBnplSummary()
                ];
            }

            throw new \Exception('Invalid BNPL order details');

        } catch (\Exception $e) {
            \Log::error('BNPL verification failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function getPaymentUrl(Order $order): string
    {
        // For BNPL, we don't redirect to external payment gateway
        // Instead, we process the payment directly
        return route('payments.bnpl.process', ['order_id' => $order->id]);
    }

    public function getPaymentStatus(Order $order): string
    {
        return $order->status;
    }

    public function getPaymentDetails(Order $order): array
    {
        if ($order->payment_method !== Order::$bnpl) {
            return [];
        }

        return [
            'provider' => $order->bnpl_provider,
            'fee' => $order->bnpl_fee,
            'fee_percentage' => $order->bnpl_fee_percentage,
            'installment_count' => $order->installment_count,
            'installment_amount' => $order->getInstallmentAmount(),
            'total_with_fee' => $order->getTotalWithBnplFee(),
            'payment_schedule' => $order->bnpl_payment_schedule,
            'next_due_date' => $order->getNextInstallmentDueDate(),
            'summary' => $order->getBnplSummary()
        ];
    }
}
