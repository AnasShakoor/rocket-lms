<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\traits\PaymentsTrait;
use App\Mixins\Cashback\CashbackAccounting;
use App\Models\Accounting;
use App\Models\BecomeInstructor;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentChannel;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ReserveMeeting;
use App\Models\Reward;
use App\Models\RewardAccounting;
use App\Models\Sale;
use App\Models\TicketUser;
use App\PaymentChannels\ChannelManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use PaymentsTrait;


    protected $order_session_key = 'payment.order_id';

    public function paymentRequest(Request $request)
    {
        $this->validate($request, [
            'gateway' => 'required'
        ]);

        if ($request->input('gateway') === 'bnpl') {
            $this->validate($request, [
                'bnpl_provider' => 'required|integer|exists:bnpl_providers,id'
            ]);
        }

        $user = auth()->user();
        $gateway = $request->input('gateway');
        $orderId = $request->input('order_id');

        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if ($order->type === Order::$meeting) {
            $orderItem = OrderItem::where('order_id', $order->id)->first();
            $reserveMeeting = ReserveMeeting::where('id', $orderItem->reserve_meeting_id)->first();
            $reserveMeeting->update(['locked_at' => time()]);
        }

        if ($gateway === 'credit') {

            if ($user->getAccountingCharge() < $order->total_amount) {
                $order->update(['status' => Order::$fail]);

                session()->put($this->order_session_key, $order->id);

                return redirect('/payments/status');
            }

            $order->update([
                'payment_method' => Order::$credit
            ]);

            $this->setPaymentAccounting($order, 'credit');

            $order->update([
                'status' => Order::$paid
            ]);

            session()->put($this->order_session_key, $order->id);

            return redirect('/payments/status');
        }

        if ($gateway === 'bnpl') {
            // Validate BNPL provider selection
            $bnplProviderId = $request->input('bnpl_provider');

            if (!$bnplProviderId) {
                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => trans('update.bnpl_select_provider'),
                    'status' => 'error'
                ];
                return back()->with(['toast' => $toastData]);
            }

            // Get BNPL provider details
            $bnplProvider = \App\Models\BnplProvider::find($bnplProviderId);

            if (!$bnplProvider || !$bnplProvider->is_active) {
                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => trans('update.bnpl_not_available'),
                    'status' => 'error'
                ];
                return back()->with(['toast' => $toastData]);
            }

            // Check if it's Tabby and handle accordingly
            if ($bnplProvider->name === 'Tabby') {
                try {
                    // Get customer data
                    $customerData = [
                        'email' => $order->user->email,
                        'phone' => $order->user->mobile,
                        'name' => $order->user->full_name,
                    ];

                    // Create Tabby checkout session
                    $tabbyService = new \App\Services\TabbyService();
                    $checkoutResult = $tabbyService->createCheckoutSession($order, $customerData);

                    if (!$checkoutResult['success']) {
                        $toastData = [
                            'title' => trans('cart.fail_purchase'),
                            'msg' => $checkoutResult['error'] ?? 'Tabby checkout failed',
                            'status' => 'error'
                        ];
                        return back()->with(['toast' => $toastData]);
                    }

                    // Update order with Tabby payment data
                    $order->update([
                        'payment_method' => Order::$bnpl,
                        'payment_data' => [
                            'bnpl_provider' => $bnplProvider->name,
                            'bnpl_provider_id' => $bnplProvider->id,
                            'installment_count' => $bnplProvider->installment_count,
                            'fee_percentage' => $bnplProvider->fee_percentage,
                            'tabby_payment_id' => $checkoutResult['payment_id'],
                            'tabby_checkout_created_at' => now(),
                            'selected_at' => now()
                        ]
                    ]);

                    // Redirect to Tabby hosted payment page
                    return Redirect::away($checkoutResult['web_url']);

                } catch (\Exception $e) {
                    Log::error('Tabby BNPL payment failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $toastData = [
                        'title' => trans('cart.fail_purchase'),
                        'msg' => 'Tabby payment processing failed: ' . $e->getMessage(),
                        'status' => 'error'
                    ];
                    return back()->with(['toast' => $toastData]);
                }
            } elseif ($bnplProvider->name === 'MisPay') {
                try {
                    // Get customer data
                    $customerData = [
                        'email' => $order->user->email,
                        'phone' => $order->user->mobile,
                        'name' => $order->user->full_name,
                    ];

                    // Create MisPay checkout session
                    $mispayService = new \App\Services\MisPayService();
                    $checkoutResult = $mispayService->createCheckoutSession($order, $customerData);

                    if (!$checkoutResult['success']) {
                        $toastData = [
                            'title' => trans('cart.fail_purchase'),
                            'msg' => $checkoutResult['error'] ?? 'MisPay checkout failed',
                            'status' => 'error'
                        ];
                        return back()->with(['toast' => $toastData]);
                    }

                    // Update order with MisPay payment data
                    $order->update([
                        'payment_method' => Order::$bnpl,
                        'payment_data' => [
                            'bnpl_provider' => $bnplProvider->name,
                            'bnpl_provider_id' => $bnplProvider->id,
                            'installment_count' => $bnplProvider->installment_count,
                            'fee_percentage' => $bnplProvider->fee_percentage,
                            'mispay_checkout_id' => $checkoutResult['checkout_id'],
                            'mispay_checkout_created_at' => now(),
                            'selected_at' => now()
                        ]
                    ]);

                    // Redirect to MisPay hosted payment page
                    return Redirect::away($checkoutResult['web_url']);

                } catch (\Exception $e) {
                    Log::error('MisPay BNPL payment failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $toastData = [
                        'title' => trans('cart.fail_purchase'),
                        'msg' => 'MisPay payment processing failed: ' . $e->getMessage(),
                        'status' => 'error'
                    ];
                    return back()->with(['toast' => $toastData]);
                }
            } else {
                // Handle other BNPL providers (existing logic)
                $order->update([
                    'payment_method' => Order::$bnpl,
                    'payment_data' => [
                        'bnpl_provider' => $bnplProvider->name,
                        'bnpl_provider_id' => $bnplProvider->id,
                        'installment_count' => $bnplProvider->installment_count,
                        'fee_percentage' => $bnplProvider->fee_percentage,
                        'selected_at' => now()
                    ]
                ]);

                // Set order status to paying for BNPL processing
                $order->update(['status' => Order::$paying]);

                // Process BNPL payment (this would typically redirect to BNPL provider's checkout)
                // For now, we'll redirect to a success page and handle the actual BNPL processing later
                $this->setPaymentAccounting($order, 'bnpl');

                $order->update([
                    'status' => Order::$paid
                ]);

                session()->put($this->order_session_key, $order->id);

                return redirect('/payments/status');
            }
        }

        $paymentChannel = PaymentChannel::where('id', $gateway)
            ->where('status', 'active')
            ->first();

        if (!$paymentChannel) {
            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => trans('public.channel_payment_disabled'),
                'status' => 'error'
            ];
            return back()->with(['toast' => $toastData]);
        }

        $order->payment_method = Order::$paymentChannel;
        $order->save();


        try {
            $channelManager = ChannelManager::makeChannel($paymentChannel);
            $redirect_url = $channelManager->paymentRequest($order);

            if (in_array($paymentChannel->class_name, PaymentChannel::$gatewayIgnoreRedirect)) {
                return $redirect_url;
            }

            // Check if redirect_url is valid before using it
            if (empty($redirect_url)) {
                Log::error('Payment redirect failed: Empty redirect URL', [
                    'payment_channel' => $paymentChannel->class_name,
                    'order_id' => $order->id,
                    'user_id' => auth()->id()
                ]);

                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => trans('cart.gateway_error'),
                    'status' => 'error'
                ];
                return redirect('cart')->with(['toast' => $toastData]);
            }

            return Redirect::away($redirect_url);

        } catch (\Exception $exception) {
            //dd($exception->getMessage());

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => trans('cart.gateway_error'),
                'status' => 'error'
            ];
            return back()->with(['toast' => $toastData]);
        }
    }

    public function paymentVerify(Request $request, $gateway)
    {
        Log::info('Payment verification started', [
            'gateway' => $gateway,
            'request_data' => $request->all()
        ]);

        // First, check if it's a BNPL provider
        $bnplProvider = \App\Models\BnplProvider::where('name', $gateway)
            ->where('is_active', true)
            ->first();

        if ($bnplProvider) {
            Log::info('BNPL provider found, using BNPL verification', [
                'gateway' => $gateway,
                'provider_id' => $bnplProvider->id
            ]);

            // Route to the appropriate BNPL verification method
            switch (strtolower($gateway)) {
                case 'tabby':
                    return $this->tabbyVerify($request);
                case 'mispay':
                    return $this->mispayVerify($request);
                default:
                    Log::error('Unknown BNPL provider', [
                        'gateway' => $gateway,
                        'provider_id' => $bnplProvider->id
                    ]);

                    $toastData = [
                        'title' => trans('cart.fail_purchase'),
                        'msg' => 'Unknown BNPL provider',
                        'status' => 'error'
                    ];
                    return redirect('cart')->with(['toast' => $toastData]);
            }
        }

        // If not BNPL, check traditional payment channels
        $paymentChannel = PaymentChannel::where('class_name', $gateway)
            ->where('status', 'active')
            ->first();

        if (!$paymentChannel) {
            Log::error('Payment verification failed: Neither BNPL provider nor payment channel found', [
                'gateway' => $gateway,
                'user_id' => auth()->id()
            ]);

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => trans('cart.gateway_error'),
                'status' => 'error'
            ];
            return redirect('cart')->with(['toast' => $toastData]);
        }

        Log::info('Traditional payment channel found, using channel manager', [
            'gateway' => $gateway,
            'channel_id' => $paymentChannel->id
        ]);

        try {
            $channelManager = ChannelManager::makeChannel($paymentChannel);
            $order = $channelManager->verify($request);

            return $this->paymentOrderAfterVerify($order);

        } catch (\Exception $exception) {
            Log::error('Payment verification failed with exception', [
                'gateway' => $gateway,
                'exception_message' => $exception->getMessage(),
                'exception_trace' => $exception->getTraceAsString(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => trans('cart.gateway_error'),
                'status' => 'error'
            ];
            return redirect('cart')->with(['toast' => $toastData]);
        }
    }


        private function paymentOrderAfterVerify($order)
    {
        if (!empty($order)) {

            if ($order->status == Order::$paying) {
                $this->setPaymentAccounting($order);
                $order->update(['status' => Order::$paid]);
            } else {
                if ($order->type === Order::$meeting) {
                    $orderItem = OrderItem::where('order_id', $order->id)->first();

                    if ($orderItem && $orderItem->reserve_meeting_id) {
                        $reserveMeeting = ReserveMeeting::where('id', $orderItem->reserve_meeting_id)->first();

                        if ($reserveMeeting) {
                            $reserveMeeting->update(['locked_at' => null]);
                        }
                    }
                }
            }

            session()->put($this->order_session_key, $order->id);
            return redirect("/payments/status?t={$order->id}");
        } else {
            Log::error('Payment order after verify failed: Order is empty', [
                'user_id' => auth()->id(),
                'timestamp' => now()
            ]);

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => trans('cart.gateway_error'),
                'status' => 'error'
            ];

            return redirect('cart')->with($toastData);
        }
    }

    public function setPaymentAccounting($order, $type = null)
    {
        $cashbackAccounting = new CashbackAccounting();

        if ($order->is_charge_account) {
            Accounting::charge($order);

            $cashbackAccounting->rechargeWallet($order);
        } else {
            foreach ($order->orderItems as $orderItem) {
                $updateInstallmentOrderAfterSale = false;
                $updateProductOrderAfterSale = false;

                if (!empty($orderItem->gift_id)) {
                    $gift = $orderItem->gift;

                    $gift->update([
                        'status' => 'active'
                    ]);

                    $gift->sendNotificationsWhenActivated($orderItem->total_amount);
                }

                if (!empty($orderItem->subscribe_id)) {
                    Accounting::createAccountingForSubscribe($orderItem, $type);
                } elseif (!empty($orderItem->promotion_id)) {
                    Accounting::createAccountingForPromotion($orderItem, $type);
                } elseif (!empty($orderItem->registration_package_id)) {
                    Accounting::createAccountingForRegistrationPackage($orderItem, $type);

                    if (!empty($orderItem->become_instructor_id)) {
                        BecomeInstructor::where('id', $orderItem->become_instructor_id)
                            ->update([
                                'package_id' => $orderItem->registration_package_id
                            ]);
                    }
                } elseif (!empty($orderItem->installment_payment_id)) {
                    Accounting::createAccountingForInstallmentPayment($orderItem, $type);

                    $updateInstallmentOrderAfterSale = true;
                } else {
                    // webinar and meeting and product and bundle

                    Accounting::createAccounting($orderItem, $type);
                    TicketUser::useTicket($orderItem);

                    if (!empty($orderItem->product_id)) {
                        $updateProductOrderAfterSale = true;
                    }
                }

                // Set Sale After All Accounting
                $sale = Sale::createSales($orderItem, $order->payment_method);

                if (!empty($orderItem->reserve_meeting_id)) {
                    $reserveMeeting = ReserveMeeting::where('id', $orderItem->reserve_meeting_id)->first();
                    $reserveMeeting->update([
                        'sale_id' => $sale->id,
                        'reserved_at' => time()
                    ]);

                    $reserver = $reserveMeeting->user;

                    if ($reserver) {
                        $this->handleMeetingReserveReward($reserver);
                    }
                }

                if ($updateInstallmentOrderAfterSale) {
                    $this->updateInstallmentOrder($orderItem, $sale);
                }

                if ($updateProductOrderAfterSale) {
                    $this->updateProductOrder($sale, $orderItem);
                }
            }

            // Set Cashback Accounting For All Order Items
            $cashbackAccounting->setAccountingForOrderItems($order->orderItems);
        }

        Cart::emptyCart($order->user_id);
    }

    public function payStatus(Request $request)
    {
        $orderId = $request->get('t', null);

        if (!empty(session()->get($this->order_session_key, null))) {
            $orderId = session()->get($this->order_session_key, null);
            session()->forget($this->order_session_key);
        }

        $authId = auth()->id();

        $order = Order::where('id', $orderId)
            ->where('user_id', $authId)
            ->first();

        if (!empty($order)) {
            $data = [
                'pageTitle' => trans('public.cart_page_title'),
                'order' => $order,
            ];

            return view('design_1.web.cart.payment.status.index', $data);
        }

        return redirect('/panel');
    }

    /**
     * Apple Pay merchant validation for Moyasar integration
     */
        public function applePayValidateMerchant(Request $request)
    {
        try {
            // Get the validation URL from the request
            $validationUrl = $request->input('validation_url');

            if (!$validationUrl) {
                return response()->json(['error' => 'Validation URL is required'], 400);
            }

            // Make a POST request to Apple's validation URL
            $response = Http::post($validationUrl, [
                'merchantIdentifier' => config('services.apple_pay.merchant_id', 'merchant.com.yourstore'),
                'domainName' => config('services.apple_pay.domain', request()->getHost()),
                'displayName' => config('services.apple_pay.display_name', getGeneralSettings('site_name', 'Your Store'))
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Apple Pay validation failed'], 400);

        } catch (\Exception $e) {
            Log::error('Apple Pay validation error: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            return response()->json(['error' => 'Apple Pay validation error'], 500);
        }
    }

    /**
     * Safely merge payment data arrays, handling both array and JSON string inputs
     */
    private function mergePaymentData($order, array $newData): array
    {
        $existingData = $order->payment_data;

        if (is_array($existingData)) {
            return array_merge($existingData, $newData);
        }

        if (is_string($existingData)) {
            $decoded = json_decode($existingData, true);
            return array_merge($decoded ?: [], $newData);
        }

        return $newData;
    }

    /**
     * Tabby payment verification
     */
    public function tabbyVerify(Request $request)
    {
        try {
            $paymentId = $request->get('payment_id');

            if (!$paymentId) {
                Log::error('Tabby verification: Missing payment_id', [
                    'request_data' => $request->all()
                ]);

                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => 'Missing payment ID',
                    'status' => 'error'
                ];
                return redirect('/cart')->with(['toast' => $toastData]);
            }

            // Find order by Tabby payment ID
            $order = Order::where('payment_data->tabby_payment_id', $paymentId)->first();

            if (!$order) {
                Log::error('Tabby verification: Order not found', [
                    'payment_id' => $paymentId
                ]);

                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => 'Order not found',
                    'status' => 'error'
                ];
                return redirect('/cart')->with(['toast' => $toastData]);
            }

            // Verify payment with Tabby
            $tabbyService = new \App\Services\TabbyService();
            $verificationResult = $tabbyService->verifyPayment($paymentId);

            if (!$verificationResult['success']) {
                Log::error('Tabby verification: API verification failed', [
                    'payment_id' => $paymentId,
                    'order_id' => $order->id,
                    'error' => $verificationResult['error'] ?? 'Unknown error'
                ]);

                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => 'Payment verification failed',
                    'status' => 'error'
                ];
                return redirect('/cart')->with(['toast' => $toastData]);
            }

            $tabbyStatus = $verificationResult['status'] ?? 'unknown';

            // Update order status based on Tabby response
            if ($tabbyStatus === 'AUTHORIZED') {
                // Ensure order status is set to paying before calling setPaymentAccounting
                if ($order->status !== Order::$paying) {
                    $order->update(['status' => Order::$paying]);
                }

                // Set payment accounting (this creates the sale records)
                $this->setPaymentAccounting($order, 'tabby');

                // Update order status to paid after successful accounting
                $order->update([
                    'status' => Order::$paid,
                    'payment_data' => $this->mergePaymentData($order, [
                        'tabby_verified_at' => now(),
                        'tabby_status' => $tabbyStatus
                    ])
                ]);

                session()->put($this->order_session_key, $order->id);
                return redirect('/payments/status');

            } elseif (in_array($tabbyStatus, ['REJECTED', 'EXPIRED', 'CANCELLED'])) {
                $order->update([
                    'status' => Order::$fail,
                    'payment_data' => $this->mergePaymentData($order, [
                        'tabby_verified_at' => now(),
                        'tabby_status' => $tabbyStatus
                    ])
                ]);

                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => 'Payment was not successful',
                    'status' => 'error'
                ];
                return redirect('/cart')->with(['toast' => $toastData]);
            }

            // Unknown status
            Log::warning('Tabby verification: Unknown status', [
                'payment_id' => $paymentId,
                'order_id' => $order->id,
                'status' => $tabbyStatus
            ]);

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => 'Payment status unknown',
                'status' => 'error'
            ];
            return redirect('/cart')->with(['toast' => $toastData]);

        } catch (\Exception $e) {
            Log::error('Tabby verification exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => 'Payment verification failed',
                'status' => 'error'
            ];
            return redirect('/cart')->with(['toast' => $toastData]);
        }
    }

    /**
     * Tabby success callback
     */
    public function tabbySuccess(Request $request)
    {
        Log::info('Tabby success callback received', [
            'request_data' => $request->all(),
            'query_params' => $request->query()
        ]);

        // Tabby might send different parameters, try multiple possibilities
        $paymentId = $request->get('payment_id') ??
                    $request->get('session_id') ??
                    $request->get('id') ??
                    $request->get('sessionId');

        if ($paymentId) {
            Log::info('Tabby success callback: Redirecting to verification', [
                'payment_id' => $paymentId
            ]);
            return redirect()->route('payments.tabby.verify', ['payment_id' => $paymentId]);
        }

        Log::warning('Tabby success callback: No payment ID found', [
            'request_data' => $request->all()
        ]);

        return redirect('/cart');
    }

    /**
     * Tabby cancel callback
     */
    public function tabbyCancel(Request $request)
    {
        Log::info('Tabby cancel callback received', [
            'request_data' => $request->all()
        ]);

        // Tabby might send different parameters, try multiple possibilities
        $paymentId = $request->get('payment_id') ??
                    $request->get('session_id') ??
                    $request->get('id') ??
                    $request->get('sessionId');

        if ($paymentId) {
            // Find and update order status
            $order = Order::where('payment_data->tabby_payment_id', $paymentId)->first();

            if ($order) {
                $order->update([
                    'status' => Order::$fail,
                    'payment_data' => $this->mergePaymentData($order, [
                        'tabby_cancelled_at' => now(),
                        'tabby_status' => 'CANCELLED'
                    ])
                ]);
            }

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => trans('update.tabby_payment_cancelled'),
                'status' => 'error'
            ];
            return redirect('/cart')->with(['toast' => $toastData]);
        }

        Log::warning('Tabby cancel callback: No payment ID found', [
            'request_data' => $request->all()
        ]);

        return redirect('/cart');
    }

    /**
     * Tabby failure callback
     */
    public function tabbyFailure(Request $request)
    {
        Log::info('Tabby failure callback received', [
            'request_data' => $request->all()
        ]);

        // Tabby might send different parameters, try multiple possibilities
        $paymentId = $request->get('payment_id') ??
                    $request->get('session_id') ??
                    $request->get('id') ??
                    $request->get('sessionId');

        if ($paymentId) {
            // Find and update order status
            $order = Order::where('payment_data->tabby_payment_id', $paymentId)->first();

            if ($order) {
                $order->update([
                    'status' => Order::$fail,
                    'payment_data' => $this->mergePaymentData($order, [
                        'tabby_failed_at' => now(),
                        'tabby_status' => 'REJECTED'
                    ])
                ]);
            }

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => trans('update.tabby_payment_failed'),
                'status' => 'error'
            ];
            return redirect('/cart')->with(['toast' => $toastData]);
        }

        Log::warning('Tabby failure callback: No payment ID found', [
            'request_data' => $request->all()
        ]);

        return redirect('/cart');
    }

    /**
     * MisPay verification
     */
    public function mispayVerify(Request $request)
    {
        try {
            $checkoutId = $request->get('checkout_id') ?? $request->get('id');

            if (!$checkoutId) {
                Log::error('MisPay verification: Missing checkout_id', [
                    'request_data' => $request->all()
                ]);

                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => 'Missing checkout ID',
                    'status' => 'error'
                ];
                return redirect('/cart')->with(['toast' => $toastData]);
            }

            // Find order by MisPay checkout ID
            $order = Order::where('payment_data->mispay_checkout_id', $checkoutId)->first();

            if (!$order) {
                Log::error('MisPay verification: Order not found', [
                    'checkout_id' => $checkoutId
                ]);

                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => 'Order not found',
                    'status' => 'error'
                ];
                return redirect('/cart')->with(['toast' => $toastData]);
            }

            // Verify payment with MisPay
            $mispayService = new \App\Services\MisPayService();
            $verificationResult = $mispayService->verifyPayment($checkoutId);

            if (!$verificationResult['success']) {
                Log::error('MisPay verification: API verification failed', [
                    'checkout_id' => $checkoutId,
                    'order_id' => $order->id,
                    'error' => $verificationResult['error'] ?? 'Unknown error'
                ]);

                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => 'Payment verification failed',
                    'status' => 'error'
                ];
                return redirect('/cart')->with(['toast' => $toastData]);
            }

            $mispayStatus = $verificationResult['status'] ?? 'unknown';

            // Update order status based on MisPay response
            if ($mispayStatus === 'completed' || $mispayStatus === 'success') {
                // Ensure order status is set to paying before calling setPaymentAccounting
                if ($order->status !== Order::$paying) {
                    $order->update(['status' => Order::$paying]);
                }

                // Set payment accounting (this creates the sale records)
                $this->setPaymentAccounting($order, 'mispay');

                // Update order status to paid after successful accounting
                $order->update([
                    'status' => Order::$paid,
                    'payment_data' => $this->mergePaymentData($order, [
                        'mispay_verified_at' => now(),
                        'mispay_status' => $mispayStatus
                    ])
                ]);

                session()->put($this->order_session_key, $order->id);
                return redirect('/payments/status');

            } elseif (in_array($mispayStatus, ['failed', 'cancelled', 'expired'])) {
                $order->update([
                    'status' => Order::$fail,
                    'payment_data' => $this->mergePaymentData($order, [
                        'mispay_verified_at' => now(),
                        'mispay_status' => $mispayStatus
                    ])
                ]);

                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => 'Payment was not successful',
                    'status' => 'error'
                ];
                return redirect('/cart')->with(['toast' => $toastData]);
            }

            // Unknown status
            Log::warning('MisPay verification: Unknown status', [
                'checkout_id' => $checkoutId,
                'order_id' => $order->id,
                'status' => $mispayStatus
            ]);

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => 'Payment status unknown',
                'status' => 'error'
            ];
            return redirect('/cart')->with(['toast' => $toastData]);

        } catch (\Exception $e) {
            Log::error('MisPay verification exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => 'Payment verification failed',
                'status' => 'error'
            ];
            return redirect('/cart')->with(['toast' => $toastData]);
        }
    }

    /**
     * MisPay success callback
     */
    public function mispaySuccess(Request $request)
    {
        $checkoutId = $request->get('checkout_id') ?? $request->get('id');

        if ($checkoutId) {
            return redirect()->route('payments.mispay.verify', ['checkout_id' => $checkoutId]);
        }

        return redirect('/cart');
    }

    /**
     * MisPay cancel callback
     */
    public function mispayCancel(Request $request)
    {
        $checkoutId = $request->get('checkout_id') ?? $request->get('id');

        if ($checkoutId) {
            // Find and update order status
            $order = Order::where('payment_data->mispay_checkout_id', $checkoutId)->first();

            if ($order) {
                $order->update([
                    'status' => Order::$fail,
                    'payment_data' => $this->mergePaymentData($order, [
                        'mispay_cancelled_at' => now(),
                        'mispay_status' => 'CANCELLED'
                    ])
                ]);
            }

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => trans('update.mispay_payment_cancelled'),
                'status' => 'error'
            ];
            return redirect('/cart')->with(['toast' => $toastData]);
        }

        return redirect('/cart');
    }

    /**
     * MisPay failure callback
     */
    public function mispayFailure(Request $request)
    {
        $checkoutId = $request->get('checkout_id') ?? $request->get('id');

        if ($checkoutId) {
            // Find and update order status
            $order = Order::where('payment_data->mispay_checkout_id', $checkoutId)->first();

            if ($order) {
                $order->update([
                    'status' => Order::$fail,
                    'payment_data' => $this->mergePaymentData($order, [
                        'mispay_failed_at' => now(),
                        'mispay_status' => 'FAILED'
                    ])
                ]);
            }

            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => trans('update.mispay_payment_failed'),
                'status' => 'error'
            ];
            return redirect('/cart')->with(['toast' => $toastData]);
        }

        return redirect('/cart');
    }
}
