<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'payment_gateway',
        'gateway_payment_id',
        'status',
        'amount',
        'currency_amount',
        'currency',
        'gateway_fee',
        'tax_amount',
        'discount_amount',
        'surcharge_amount',
        'total_amount',
        'payment_method',
        'card_type',
        'card_last4',
        'card_brand',
        'card_country',
        'gateway_response',
        'metadata',
        'description',
        'error_message',
        'ip_address',
        'user_agent',
        'payment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'currency_amount' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'surcharge_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'payment_date' => 'datetime',
    ];

    /**
     * Get the order that this payment log belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user that made this payment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class);
    }

    /**
     * Scope to filter by payment gateway
     */
    public function scopeByGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get formatted total amount with currency
     */
    public function getFormattedTotalAmountAttribute()
    {
        return number_format($this->total_amount, 2) . ' ' . $this->currency;
    }

    /**
     * Check if payment has surcharges
     */
    public function hasSurcharges(): bool
    {
        return $this->surcharge_amount > 0 || $this->gateway_fee > 0 || $this->tax_amount > 0;
    }

    /**
     * Get total surcharges
     */
    public function getTotalSurchargesAttribute()
    {
        return ($this->surcharge_amount ?? 0) + ($this->gateway_fee ?? 0) + ($this->tax_amount ?? 0);
    }

    /**
     * Get payment summary
     */
    public function getPaymentSummaryAttribute()
    {
        $summary = [
            'base_amount' => $this->amount,
            'currency' => $this->currency,
            'total_amount' => $this->total_amount,
        ];

        if ($this->hasSurcharges()) {
            $summary['surcharges'] = [
                'gateway_fee' => $this->gateway_fee,
                'tax_amount' => $this->tax_amount,
                'surcharge_amount' => $this->surcharge_amount,
                'total_surcharges' => $this->total_surcharges,
            ];
        }

        if ($this->discount_amount > 0) {
            $summary['discount'] = $this->discount_amount;
        }

        return $summary;
    }
}
