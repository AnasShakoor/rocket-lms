<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\TabbyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TabbyController extends Controller
{
    protected $tabbyService;

    public function __construct()
    {
        $this->tabbyService = new TabbyService();
    }

    /**
     * Check Tabby eligibility for an order
     */
    public function checkEligibility(Request $request)
    {
        dd($request->all());
        try {
            $request->validate([
                'order_id' => 'required|integer',
                'amount' => 'required|numeric|min:0',
                'currency' => 'required|string|size:3'
            ]);

            // Get the order
            $order = Order::where('id', $request->order_id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Order not found'
                ], 404);
            }

            // Get customer data
            $customerData = [
                'email' => $order->user->email,
                'phone' => $order->user->mobile,
                'name' => $order->user->full_name,
            ];

            // Check eligibility using Tabby service
            $eligibilityResult = $this->tabbyService->checkEligibility($order, $customerData);

            return response()->json($eligibilityResult);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Eligibility check failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
