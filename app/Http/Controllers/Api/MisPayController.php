<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MisPayService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MisPayController extends Controller
{
    protected $mispayService;

    public function __construct()
    {
        $this->mispayService = new MisPayService();
    }

    /**
     * Check MisPay eligibility for a customer
     */
    public function checkEligibility(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|integer',
                'amount' => 'required|numeric|min:0',
                'currency' => 'required|string|max:3'
            ]);

            $orderId = $request->input('order_id');
            $amount = $request->input('amount');
            $currency = $request->input('currency');

            // Get order details
            $order = Order::find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Order not found'
                ], 404);
            }

            // Get customer data
            $customerData = [
                'email' => $order->user->email ?? '',
                'phone' => $order->user->mobile ?? '',
                'name' => $order->user->full_name ?? '',
            ];

            // Check eligibility
            $eligibilityResult = $this->mispayService->checkEligibility($order, $customerData);

            if (!$eligibilityResult['success']) {
                return response()->json([
                    'success' => false,
                    'eligible' => false,
                    'error' => $eligibilityResult['error']
                ]);
            }

            return response()->json([
                'success' => true,
                'eligible' => $eligibilityResult['eligible'],
                'message' => $eligibilityResult['message'],
                'rejection_reason' => $eligibilityResult['rejection_reason'] ?? null,
                'installment_options' => $eligibilityResult['installment_options'] ?? []
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('MisPay eligibility check API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'An error occurred while checking eligibility'
            ], 500);
        }
    }
}
