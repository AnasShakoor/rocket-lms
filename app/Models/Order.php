<?php

namespace App\Models;

use App\Models\Observers\OrderNumberObserver;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //status
    public static $pending = 'pending';
    public static $paying = 'paying';
    public static $paid = 'paid';
    public static $fail = 'fail';

    //types
    public static $webinar = 'webinar';
    public static $meeting = 'meeting';
    public static $charge = 'charge';
    public static $subscribe = 'subscribe';
    public static $promotion = 'promotion';
    public static $registrationPackage = 'registration_package';
    public static $product = 'product';
    public static $bundle = 'bundle';
    public static $installmentPayment = 'installment_payment';
    public static $gift = 'gift';

    public static $addiction = 'addiction';
    public static $deduction = 'deduction';

    public static $income = 'income';
    public static $asset = 'asset';

    //paymentMethod
    public static $credit = 'credit';
    public static $paymentChannel = 'payment_channel';
    public static $bnpl = 'bnpl';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'bnpl_fee' => 'decimal:2',
        'bnpl_fee_percentage' => 'decimal:2',
        'installment_count' => 'integer',
        'bnpl_payment_schedule' => 'array',
        'payment_data' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        Order::observe(OrderNumberObserver::class);
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function orderItems()
    {
        return $this->hasMany('App\Models\OrderItem', 'order_id', 'id');
    }

    /**
     * Check if this order uses BNPL payment method
     */
    public function isBnplPayment(): bool
    {
        return $this->payment_method === self::$bnpl;
    }

    /**
     * Get BNPL provider information
     */
    public function getBnplProvider()
    {
        if (!$this->isBnplPayment()) {
            return null;
        }

        return BnplProvider::where('name', $this->bnpl_provider)->first();
    }

    /**
     * Calculate total amount including BNPL fee
     */
    public function getTotalWithBnplFee(): float
    {
        if (!$this->isBnplPayment() || !$this->bnpl_fee) {
            return $this->total_amount;
        }

        return $this->total_amount + $this->bnpl_fee;
    }

    /**
     * Get installment amount
     */
    public function getInstallmentAmount(): ?float
    {
        if (!$this->isBnplPayment() || !$this->installment_count) {
            return null;
        }

        return round($this->getTotalWithBnplFee() / $this->installment_count, 2);
    }

    /**
     * Get next installment due date
     */
    public function getNextInstallmentDueDate(): ?string
    {
        if (!$this->isBnplPayment() || !$this->bnpl_payment_schedule) {
            return null;
        }

        $pendingInstallments = collect($this->bnpl_payment_schedule)
            ->where('status', 'pending')
            ->sortBy('due_date');

        return $pendingInstallments->first()['due_date'] ?? null;
    }

    /**
     * Get BNPL payment summary
     */
    public function getBnplSummary(): array
    {
        if (!$this->isBnplPayment()) {
            return [];
        }

        $totalInstallments = $this->installment_count ?? 0;
        $paidInstallments = collect($this->bnpl_payment_schedule ?? [])
            ->where('status', 'paid')
            ->count();
        $pendingInstallments = $totalInstallments - $paidInstallments;

        return [
            'total_installments' => $totalInstallments,
            'paid_installments' => $paidInstallments,
            'pending_installments' => $pendingInstallments,
            'installment_amount' => $this->getInstallmentAmount(),
            'next_due_date' => $this->getNextInstallmentDueDate(),
            'total_with_fee' => $this->getTotalWithBnplFee(),
            'bnpl_fee' => $this->bnpl_fee,
            'bnpl_fee_percentage' => $this->bnpl_fee_percentage
        ];
    }
}
