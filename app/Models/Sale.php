<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    // Static properties for backward compatibility
    public static $webinar = 'webinar';
    public static $bundle = 'bundle';
    public static $meeting = 'meeting';
    public static $subscribe = 'subscribe';
    public static $credit = 'credit';
    public static $product = 'product';
    public static $gift = 'gift';
    public static $promotion = 'promotion';
    public static $registrationPackage = 'registrationPackage';
    public static $installmentPayment = 'installmentPayment';
    
    protected $table = 'sales';
    
    protected $fillable = [
        'buyer_id',
        'seller_id',
        'webinar_id',
        'bundle_id',
        'meeting_id',
        'subscribe_id',
        'promotion_id',
        'registration_package_id',
        'gift_id',
        'installment_payment_id',
        'product_order_id',
        'order_number',
        'amount',
        'vat_amount',
        'bnpl_fee',
        'bnpl_provider',
        'installments',
        'payment_method',
        'status',
        'type',
        'purchased_at',
        'paid_at',
        'payment_details'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'bnpl_fee' => 'decimal:2',
        'installments' => 'integer',
        'purchased_at' => 'datetime',
        'paid_at' => 'datetime',
        'payment_details' => 'array'
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'seller_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Api\Webinar::class, 'webinar_id');
    }

    // Backward compatibility method
    public function webinar(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Api\Webinar::class, 'webinar_id');
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function bnplProvider(): BelongsTo
    {
        return $this->belongsTo(BnplProvider::class, 'bnpl_provider', 'name');
    }

    // Backward compatibility relationship
    public function saleLog()
    {
        return $this->hasOne(SaleLog::class, 'sale_id', 'id');
    }

    // Additional relationships for backward compatibility
    public function meeting()
    {
        return $this->belongsTo(\App\Models\Meeting::class, 'meeting_id');
    }

    public function subscribe()
    {
        return $this->belongsTo(\App\Models\Subscribe::class, 'subscribe_id');
    }

    public function promotion()
    {
        return $this->belongsTo(\App\Models\Promotion::class, 'promotion_id');
    }

    public function registrationPackage()
    {
        return $this->belongsTo(\App\Models\RegistrationPackage::class, 'registration_package_id');
    }

    public function gift()
    {
        return $this->belongsTo(\App\Models\Gift::class, 'gift_id');
    }

    public function installmentOrderPayment()
    {
        return $this->belongsTo(\App\Models\InstallmentOrderPayment::class, 'installment_payment_id');
    }

    public function productOrder()
    {
        return $this->belongsTo(\App\Models\ProductOrder::class, 'product_order_id');
    }

    // Backward compatibility method for subscribe handling
    public function getUsedSubscribe($buyerId, $itemId, $itemType = 'webinar_id')
    {
        return self::where('buyer_id', $buyerId)
            ->where($itemType, $itemId)
            ->where('payment_method', self::$subscribe)
            ->where('status', 'completed')
            ->first();
    }

    public function getTotalAmountAttribute()
    {
        return $this->amount + $this->vat_amount + $this->bnpl_fee;
    }

    public function getInstallmentAmountAttribute()
    {
        if ($this->installments > 1) {
            return round($this->total_amount / $this->installments, 2);
        }
        return $this->total_amount;
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('buyer_id', $userId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('webinar_id', $courseId);
    }

    public function scopeByBundle($query, $bundleId)
    {
        return $query->where('bundle_id', $bundleId);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('purchased_at', [$startDate, $endDate]);
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now()
        ]);
    }

    public function isBnpl()
    {
        return $this->payment_method === 'bnpl';
    }

    public function hasVat()
    {
        return $this->vat_amount > 0;
    }

    public function hasBnplFee()
    {
        return $this->bnpl_fee > 0;
    }

    // Backward compatibility method
    public function getIncomeItem()
    {
        return $this->amount;
    }

    // Backward compatibility method for subscribe purchases
    public function checkExpiredPurchaseWithSubscribe($buyerId, $itemId, $itemType)
    {
        // Check if the purchase is expired based on subscribe rules
        // This is a simplified implementation - you may need to adjust based on your business logic
        $subscribeSale = self::where('buyer_id', $buyerId)
            ->where('payment_method', self::$subscribe)
            ->where('type', $itemType === 'webinar_id' ? self::$webinar : self::$bundle)
            ->where('status', 'completed')
            ->first();
        
        if ($subscribeSale) {
            // Check if subscribe is still valid (not expired)
            // You may need to implement your own expiration logic here
            return true; // Placeholder - implement actual expiration check
        }
        
        return false;
    }

    // Static method for backward compatibility
    public static function createSales($orderItem, $paymentMethod)
    {
        return self::create([
            'buyer_id' => $orderItem->buyer_id ?? auth()->id(),
            'webinar_id' => $orderItem->webinar_id ?? null,
            'bundle_id' => $orderItem->bundle_id ?? null,
            'order_number' => $orderItem->order_number ?? self::generateOrderNumber(),
            'amount' => $orderItem->amount ?? 0,
            'vat_amount' => $orderItem->vat_amount ?? 0,
            'bnpl_fee' => $orderItem->bnpl_fee ?? 0,
            'bnpl_provider' => $orderItem->bnpl_provider ?? null,
            'installments' => $orderItem->installments ?? 1,
            'payment_method' => $paymentMethod,
            'status' => 'completed',
            'purchased_at' => now(),
            'paid_at' => now(),
            'payment_details' => $orderItem->payment_details ?? []
        ]);
    }

    private static function generateOrderNumber()
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
    }
}
