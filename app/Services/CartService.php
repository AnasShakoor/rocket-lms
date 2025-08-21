<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\BnplProvider;
use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CartService
{
    protected $bnplService;
    
    public function __construct(BnplPaymentService $bnplService)
    {
        $this->bnplService = $bnplService;
    }
    
    /**
     * Add item to cart with BNPL option
     */
    public function addToCart($userId, $itemType, $itemId, $quantity = 1, $bnplProvider = null)
    {
        try {
            DB::beginTransaction();
            
            $item = $this->getItem($itemType, $itemId);
            if (!$item) {
                throw new \Exception('Item not found');
            }
            
            $price = $this->getItemPrice($item);
            
            // Check BNPL eligibility if provider specified
            if ($bnplProvider) {
                $eligibility = $this->bnplService->validateEligibility($userId, $price, $bnplProvider);
                if (!$eligibility['eligible']) {
                    throw new \Exception('BNPL not eligible: ' . $eligibility['reason']);
                }
            }
            
            // Add to cart (you can customize this based on your cart table structure)
            $cartItem = DB::table('cart')->insertGetId([
                'user_id' => $userId,
                'item_type' => $itemType,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'price' => $price,
                'bnpl_provider' => $bnplProvider,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'cart_item_id' => $cartItem,
                'price' => $price,
                'bnpl_available' => $bnplProvider ? true : false
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to add item to cart: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process cart checkout with BNPL
     */
    public function checkout($userId, $bnplProvider = null, $installments = null)
    {
        try {
            DB::beginTransaction();
            
            $cartItems = $this->getCartItems($userId);
            if ($cartItems->isEmpty()) {
                throw new \Exception('Cart is empty');
            }
            
            $totalAmount = $cartItems->sum(function($item) {
                return $item->price * $item->quantity;
            });
            
            if ($bnplProvider) {
                // Process BNPL payment
                $result = $this->bnplService->processBnplPayment(
                    $userId, 
                    null, // course_id - will be set per item
                    null, // bundle_id - will be set per item
                    $totalAmount, 
                    $bnplProvider, 
                    $installments
                );
                
                if (!$result['success']) {
                    throw new \Exception($result['error']);
                }
                
                // Create individual sales records for each cart item
                foreach ($cartItems as $item) {
                    $this->createSaleRecord($userId, $item, $bnplProvider, $result['payment_breakdown']);
                }
                
                // Clear cart
                $this->clearCart($userId);
                
                DB::commit();
                
                return [
                    'success' => true,
                    'order_number' => $result['order_number'],
                    'total_amount' => $totalAmount,
                    'payment_method' => 'bnpl',
                    'installments' => $result['payment_breakdown']['installment_count']
                ];
            } else {
                // Process regular payment (implement your existing payment logic here)
                // For now, just create sales records
                foreach ($cartItems as $item) {
                    $this->createSaleRecord($userId, $item, null, null);
                }
                
                $this->clearCart($userId);
                
                DB::commit();
                
                return [
                    'success' => true,
                    'total_amount' => $totalAmount,
                    'payment_method' => 'credit_card'
                ];
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Checkout failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get cart items for user
     */
    private function getCartItems($userId)
    {
        return DB::table('cart')
            ->where('user_id', $userId)
            ->get();
    }
    
    /**
     * Clear user's cart
     */
    private function clearCart($userId)
    {
        DB::table('cart')->where('user_id', $userId)->delete();
    }
    
    /**
     * Create sale record
     */
    private function createSaleRecord($userId, $cartItem, $bnplProvider, $paymentBreakdown)
    {
        $saleData = [
            'buyer_id' => $userId,
            'order_number' => $this->generateOrderNumber(),
            'amount' => $cartItem->price * $cartItem->quantity,
            'payment_method' => $bnplProvider ? 'bnpl' : 'credit_card',
            'status' => 'pending',
            'purchased_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        // Set item-specific fields
        if ($cartItem->item_type === 'course') {
            $saleData['webinar_id'] = $cartItem->item_id;
        } elseif ($cartItem->item_type === 'bundle') {
            $saleData['bundle_id'] = $cartItem->item_id;
        }
        
        // Add BNPL-specific fields
        if ($bnplProvider) {
            $saleData['bnpl_provider'] = $bnplProvider;
            $saleData['bnpl_fee'] = $paymentBreakdown['bnpl_fee'] ?? 0;
            $saleData['installments'] = $paymentBreakdown['installment_count'] ?? 1;
            $saleData['payment_details'] = $paymentBreakdown;
        }
        
        return Sale::create($saleData);
    }
    
    /**
     * Get item details
     */
    private function getItem($type, $id)
    {
        switch ($type) {
            case 'course':
                return \App\Models\Api\Webinar::find($id);
            case 'bundle':
                return \App\Models\Bundle::find($id);
            default:
                return null;
        }
    }
    
    /**
     * Get item price
     */
    private function getItemPrice($item)
    {
        return $item->price ?? $item->amount ?? 0;
    }
    
    /**
     * Generate order number
     */
    private function generateOrderNumber()
    {
        $prefix = 'CART';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return "{$prefix}{$timestamp}{$random}";
    }
    
    /**
     * Get available BNPL providers for cart
     */
    public function getAvailableBnplProviders($totalAmount)
    {
        $providers = BnplProvider::where('is_active', true)->get();
        
        return $providers->filter(function($provider) use ($totalAmount) {
            $minAmount = $provider->config['min_amount'] ?? 0;
            $maxAmount = $provider->config['max_amount'] ?? 999999;
            
            return $totalAmount >= $minAmount && $totalAmount <= $maxAmount;
        });
    }
}

