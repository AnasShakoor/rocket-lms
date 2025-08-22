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
use App\Services\PaymentLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
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

            // Remove purchased items from cart after successful credit payment
            $this->removePurchasedItemsFromCart($order);

            session()->put($this->order_session_key, $order->id);

            return redirect('/payments/status');
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

            // If Moyasar, send form data back to the cart payment page instead of redirecting
            if ($paymentChannel->class_name === 'Moyasar' && is_array($redirect_url)) {
                // Keep current order in session for status
                session()->put($this->order_session_key, $order->id);

                return redirect('/cart')->with([
                    'moyasar' => true,
                    'moyasar_form_data' => $redirect_url,
                ]);
            }

            if (in_array($paymentChannel->class_name, PaymentChannel::$gatewayIgnoreRedirect)) {
                return $redirect_url;
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
        $paymentChannel = PaymentChannel::where('class_name', $gateway)
            ->where('status', 'active')
            ->first();

        try {
            $channelManager = ChannelManager::makeChannel($paymentChannel);
            $order = $channelManager->verify($request);

            // For frontend Moyasar payments, return JSON response
            if ($request->expectsJson() && $gateway === 'Moyasar') {
                if ($order && $order->status === Order::$paid) {
                    // Log payment data if not already logged
                    try {
                        $paymentLogService = new PaymentLogService();
                        $existingLogs = $paymentLogService->getOrderPaymentLogs($order->id);

                                                if ($existingLogs->isEmpty()) {
                            // Create a basic payment log entry for frontend payments
                            $paymentData = [
                                'id' => $request->input('id'),
                                'status' => $request->input('status'),
                                'message' => $request->input('message'),
                                'amount' => $order->total_amount,
                                'currency' => 'SAR',
                                'source' => ['type' => 'creditcard']
                            ];

                            $paymentLog = $paymentLogService->logMoyasarPayment($paymentData, $order, $request);

                            Log::info('Frontend Moyasar payment logged', [
                                'order_id' => $order->id,
                                'payment_log_id' => $paymentLog->id
                            ]);

                            // Remove purchased items from cart for frontend payments
                            $this->removePurchasedItemsFromCart($order);
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to log frontend Moyasar payment', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment verified successfully',
                        'order_id' => $order->id,
                        'status' => 'paid'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment verification failed',
                        'order_id' => $order ? $order->id : null,
                        'status' => $order ? $order->status : 'unknown'
                    ], 400);
                }
            }

            return $this->paymentOrderAfterVerify($order);

        } catch (\Exception $exception) {
            if ($request->expectsJson() && $gateway === 'Moyasar') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification error: ' . $exception->getMessage()
                ], 500);
            }

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
            $toastData = [
                'title' => trans('cart.fail_purchase'),
                'msg' => trans('cart.gateway_error'),
                'status' => 'error'
            ];

                    return redirect('cart')->with($toastData);
        }
    }

    /**
     * Remove purchased items from cart after successful payment
     */
    private function removePurchasedItemsFromCart($order)
    {
        try {
            $userId = $order->user_id;
            $removedCount = 0;

            foreach ($order->orderItems as $orderItem) {
                $cart = \App\Models\Cart::where('creator_id', $userId);

                if (!empty($orderItem->webinar_id)) {
                    // Remove webinar from cart
                    $cartItems = $cart->where('webinar_id', $orderItem->webinar_id)->get();
                    foreach ($cartItems as $cartItem) {
                        $cartItem->delete();
                        $removedCount++;
                    }
                } elseif (!empty($orderItem->product_order_id)) {
                    // Remove product from cart
                    $cartItems = $cart->where('product_order_id', $orderItem->product_order_id)->get();
                    foreach ($cartItems as $cartItem) {
                        $cartItem->delete();
                        $removedCount++;
                    }
                }
            }

            if ($removedCount > 0) {
                Log::info('Items removed from cart after frontend payment', [
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'removed_count' => $removedCount
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to remove items from cart after frontend payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
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
     * Get Moyasar payment form data
     */
    public function getMoyasarFormData(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $orderId = $request->input('order_id');
        $gatewayId = $request->input('gateway');

        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $paymentChannel = PaymentChannel::where('id', $gatewayId)
            ->where('class_name', 'Moyasar')
            ->first();

        if (!$paymentChannel) {
            return response()->json([
                'success' => false,
                'message' => 'Moyasar payment channel not found'
            ], 404);
        }

        try {
            $channel = ChannelManager::makeChannel($paymentChannel);
            $paymentFormData = $channel->paymentRequest($order);

            return response()->json([
                'success' => true,
                'payment_form_data' => $paymentFormData
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting Moyasar form data: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'gateway_id' => $gatewayId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error preparing payment form: ' . $e->getMessage()
            ], 500);
        }
    }

}
