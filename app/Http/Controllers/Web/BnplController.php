<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BnplProvider;
use App\Models\Cart;
use App\Models\Order;
use App\Services\BnplPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BnplController extends Controller
{
    protected $bnplService;

    public function __construct(BnplPaymentService $bnplService)
    {
        $this->bnplService = $bnplService;
    }

    /**
     * Show BNPL checkout page
     */
    public function checkout(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return redirect()->route('login');
            }

            // Get cart items
            $carts = Cart::where('creator_id', $user->id)
                ->with(['webinar', 'bundle', 'productOrder.product'])
                ->get();

            if ($carts->isEmpty()) {
                return redirect()->route('cart.index')->with('error', 'Your cart is empty');
            }

            // Calculate total
            $subtotal = $carts->sum(function ($cart) {
                if ($cart->webinar) {
                    return $cart->webinar->price;
                } elseif ($cart->bundle) {
                    return $cart->bundle->price;
                } elseif ($cart->productOrder && $cart->productOrder->product) {
                    return $cart->productOrder->product->price;
                }
                return 0;
            });

            $vatPercentage = getFinancialSettings()['tax'] ?? 15;
            $vat = $subtotal * ($vatPercentage / 100);
            $total = $subtotal + $vat;

            // Get available BNPL providers
            $bnplProviders = $this->bnplService->getAvailableProviders();

            if ($bnplProviders->isEmpty()) {
                return redirect()->route('cart.index')->with('error', 'No BNPL providers available');
            }

            return view('cart.bnpl-checkout', compact(
                'carts',
                'subtotal',
                'vat',
                'total',
                'vatPercentage',
                'bnplProviders'
            ));

        } catch (\Exception $e) {
            Log::error('BNPL checkout failed: ' . $e->getMessage());
            return redirect()->route('cart.index')->with('error', 'Unable to load BNPL checkout');
        }
    }

    /**
     * Process BNPL checkout
     */
    public function processCheckout(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated'
                ], 401);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required|in:credit_card,bnpl',
                'bnpl_provider' => 'required_if:payment_method,bnpl|string',
                'carts' => 'required|array|min:1',
                'carts.*.id' => 'required|integer|exists:carts,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            if ($request->input('payment_method') === 'bnpl') {
                return $this->processBnplCheckout($request, $user);
            } else {
                return $this->processRegularCheckout($request, $user);
            }

        } catch (\Exception $e) {
            Log::error('BNPL checkout processing failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Checkout processing failed'
            ], 500);
        }
    }

    /**
     * Process BNPL checkout specifically
     */
    private function processBnplCheckout(Request $request, $user)
    {
        try {
            $providerName = $request->input('bnpl_provider');
            $carts = $request->input('carts');

            // Get cart items
            $cartItems = Cart::whereIn('id', $carts)
                ->where('creator_id', $user->id)
                ->with(['webinar', 'bundle', 'productOrder.product'])
                ->get();

            if ($cartItems->isEmpty()) {
                throw new \Exception('No valid cart items found');
            }

            // Calculate total amount
            $totalAmount = $cartItems->sum(function ($cart) {
                if ($cart->webinar) {
                    return $cart->webinar->price;
                } elseif ($cart->bundle) {
                    return $cart->bundle->price;
                } elseif ($cart->productOrder && $cart->productOrder->product) {
                    return $cart->productOrder->product->price;
                }
                return 0;
            });

            $vatPercentage = getFinancialSettings()['tax'] ?? 15;
            $vatAmount = $totalAmount * ($vatPercentage / 100);
            $amountWithVat = $totalAmount + $vatAmount;

            // Check BNPL eligibility
            $eligibility = $this->bnplService->validateEligibility($user->id, $amountWithVat, $providerName);
            if (!$eligibility['eligible']) {
                throw new \Exception($eligibility['reason']);
            }

            DB::beginTransaction();

            // Prepare order items
            $orderItems = [];
            foreach ($cartItems as $cart) {
                $item = [
                    'amount' => 0,
                    'total_amount' => 0,
                    'tax' => 0,
                    'discount' => 0
                ];

                if ($cart->webinar) {
                    $item['webinar_id'] = $cart->webinar->id;
                    $item['amount'] = $cart->webinar->price;
                    $item['total_amount'] = $cart->webinar->price;
                } elseif ($cart->bundle) {
                    $item['bundle_id'] = $cart->bundle->id;
                    $item['amount'] = $cart->bundle->price;
                    $item['total_amount'] = $cart->bundle->price;
                } elseif ($cart->productOrder && $cart->productOrder->product) {
                    $item['product_id'] = $cart->productOrder->product->id;
                    $item['amount'] = $cart->productOrder->product->price;
                    $item['total_amount'] = $cart->productOrder->product->price;
                }

                $orderItems[] = $item;
            }

            // Create BNPL order
            $result = $this->bnplService->createBnplOrder(
                $user->id,
                $amountWithVat,
                $providerName,
                $orderItems,
                $vatPercentage
            );

            if (!$result['success']) {
                throw new \Exception($result['error']);
            }

            $order = $result['order'];

            // Clear cart items
            Cart::whereIn('id', $carts)->where('creator_id', $user->id)->delete();

            DB::commit();

            Log::info('BNPL checkout completed successfully', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'provider' => $providerName
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'redirect_url' => route('payments.bnpl.success', ['order_id' => $order->id]),
                'message' => 'BNPL order created successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BNPL checkout processing failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'provider' => $request->input('bnpl_provider')
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process regular checkout (non-BNPL)
     */
    private function processRegularCheckout(Request $request, $user)
    {
        // Redirect to regular checkout process
        return response()->json([
            'success' => true,
            'redirect_url' => route('cart.checkout'),
            'message' => 'Redirecting to regular checkout'
        ]);
    }

    /**
     * Show BNPL payment success page
     */
    public function success(Request $request, $orderId)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return redirect()->route('login');
            }

            $order = Order::where('id', $orderId)
                ->where('user_id', $user->id)
                ->where('payment_method', Order::$bnpl)
                ->with(['orderItems.webinar', 'orderItems.bundle', 'orderItems.product'])
                ->first();

            if (!$order) {
                return redirect()->route('cart.index')->with('error', 'Order not found');
            }

            return view('payments.bnpl.success', compact('order'));

        } catch (\Exception $e) {
            Log::error('BNPL success page failed: ' . $e->getMessage());
            return redirect()->route('cart.index')->with('error', 'Unable to load success page');
        }
    }

    /**
     * Get BNPL payment details for an order
     */
    public function getPaymentDetails(Request $request, $orderId)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $order = Order::where('id', $orderId)
                ->where('user_id', $user->id)
                ->where('payment_method', Order::$bnpl)
                ->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            return response()->json([
                'success' => true,
                'payment_details' => $order->getPaymentDetails(),
                'bnpl_summary' => $order->getBnplSummary()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get BNPL payment details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get payment details'], 500);
        }
    }

    /**
     * Get available BNPL providers
     */
    public function getProviders()
    {
        try {
            $providers = $this->bnplService->getAvailableProviders();

            return response()->json([
                'success' => true,
                'providers' => $providers
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get BNPL providers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get providers'], 500);
        }
    }

    /**
     * Calculate BNPL payment breakdown
     */
    public function calculatePayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0.01',
                'provider' => 'required|string',
                'vat_percentage' => 'nullable|numeric|min:0|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            $amount = $request->input('amount');
            $provider = $request->input('provider');
            $vatPercentage = $request->input('vat_percentage', 15);

            $breakdown = $this->bnplService->calculateBnplPayment($amount, $vatPercentage, $provider);

            return response()->json([
                'success' => true,
                'breakdown' => $breakdown
            ]);

        } catch (\Exception $e) {
            Log::error('BNPL payment calculation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check BNPL eligibility
     */
    public function checkEligibility(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0.01',
                'provider' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $amount = $request->input('amount');
            $provider = $request->input('provider');

            $eligibility = $this->bnplService->validateEligibility($user->id, $amount, $provider);

            return response()->json([
                'success' => true,
                'eligibility' => $eligibility
            ]);

        } catch (\Exception $e) {
            Log::error('BNPL eligibility check failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
