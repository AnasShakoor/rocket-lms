<?php

namespace App\Services;

use App\Models\BnplProvider;
use App\Models\Order;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BnplPaymentService
{
    /**
     * Calculate BNPL payment breakdown with validation
     */
    public function calculateBnplPayment($basePrice, $vatPercentage = 15, $providerName = null)
    {
        try {
            // Validate input parameters
            $validator = Validator::make([
                'base_price' => $basePrice,
                'vat_percentage' => $vatPercentage,
                'provider_name' => $providerName
            ], [
                'base_price' => 'required|numeric|min:0.01',
                'vat_percentage' => 'required|numeric|min:0|max:100',
                'provider_name' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

        $provider = $this->getProvider($providerName);
        
        if (!$provider || !$provider->is_active) {
            throw new \Exception('BNPL provider not available or inactive');
        }

        $priceWithVat = $basePrice * (1 + ($vatPercentage / 100));
        $bnplFee = $priceWithVat * ($provider->fee_percentage / 100);
        $totalAmount = $priceWithVat + $bnplFee;
        $installmentAmount = round($totalAmount / $provider->installment_count, 2);

            // Validate calculated amounts
            if ($totalAmount <= 0) {
                throw new \Exception('Invalid total amount calculated');
            }

            if ($installmentAmount <= 0) {
                throw new \Exception('Invalid installment amount calculated');
            }

        return [
            'base_price' => round($basePrice, 2),
            'vat_percentage' => $vatPercentage,
            'vat_amount' => round($basePrice * ($vatPercentage / 100), 2),
            'price_with_vat' => round($priceWithVat, 2),
            'bnpl_provider' => $provider->name,
            'bnpl_fee_percentage' => $provider->fee_percentage,
            'bnpl_fee' => round($bnplFee, 2),
            'installment_count' => $provider->installment_count,
            'total_amount' => round($totalAmount, 2),
            'installment_amount' => $installmentAmount,
            'payment_schedule' => $this->generatePaymentSchedule($totalAmount, $provider->installment_count)
        ];

        } catch (\Exception $e) {
            Log::error('BNPL payment calculation failed: ' . $e->getMessage(), [
                'base_price' => $basePrice,
                'vat_percentage' => $vatPercentage,
                'provider_name' => $providerName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process BNPL payment with comprehensive validation
     */
    public function processBnplPayment($userId, $courseId, $bundleId, $amount, $providerName, $installments = null)
    {
        try {
            // Validate input parameters
            $validator = Validator::make([
                'user_id' => $userId,
                'course_id' => $courseId,
                'bundle_id' => $bundleId,
                'amount' => $amount,
                'provider_name' => $providerName,
                'installments' => $installments
            ], [
                'user_id' => 'required|integer|exists:users,id',
                'course_id' => 'nullable|integer|exists:webinars,id',
                'bundle_id' => 'nullable|integer|exists:bundles,id',
                'amount' => 'required|numeric|min:0.01',
                'provider_name' => 'required|string',
                'installments' => 'nullable|integer|min:2|max:12'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Check eligibility
            $eligibility = $this->validateEligibility($userId, $amount, $providerName);
            if (!$eligibility['eligible']) {
                throw new \Exception('BNPL eligibility check failed: ' . $eligibility['reason']);
            }

            DB::beginTransaction();

            $provider = $this->getProvider($providerName);
            if (!$provider) {
                throw new \Exception('Invalid BNPL provider');
            }

            $installmentCount = $installments ?? $provider->installment_count;
            
            // Calculate payment breakdown
            $paymentBreakdown = $this->calculateBnplPayment($amount, 15, $providerName);
            
            // Generate order number
            $orderNumber = $this->generateOrderNumber();
            
            // Create sale record
            $sale = Sale::create([
                'buyer_id' => $userId,
                'webinar_id' => $courseId,
                'bundle_id' => $bundleId,
                'order_number' => $orderNumber,
                'amount' => $amount,
                'vat_amount' => $paymentBreakdown['vat_amount'],
                'bnpl_fee' => $paymentBreakdown['bnpl_fee'],
                'bnpl_provider' => $provider->name,
                'installments' => $installmentCount,
                'payment_method' => 'bnpl',
                'status' => 'pending',
                'purchased_at' => now(),
                'payment_details' => [
                    'provider' => $provider->name,
                    'installment_count' => $installmentCount,
                    'installment_amount' => $paymentBreakdown['installment_amount'],
                    'payment_schedule' => $paymentBreakdown['payment_schedule'],
                    'bnpl_fee_percentage' => $provider->fee_percentage
                ]
            ]);

            // Create installment records
            $this->createInstallmentRecords($sale, $paymentBreakdown);

            DB::commit();

            Log::info("BNPL payment processed successfully for order: {$orderNumber}", [
                'sale_id' => $sale->id,
                'user_id' => $userId,
                'amount' => $amount,
                'provider' => $provider->name
            ]);
            
            return [
                'success' => true,
                'sale_id' => $sale->id,
                'order_number' => $orderNumber,
                'payment_breakdown' => $paymentBreakdown
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("BNPL payment processing failed: " . $e->getMessage(), [
                'user_id' => $userId,
                'amount' => $amount,
                'provider' => $providerName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create BNPL order with proper validation
     */
    public function createBnplOrder($userId, $amount, $providerName, $items = [], $vatPercentage = 15)
    {
        try {
            // Validate input parameters
            $validator = Validator::make([
                'user_id' => $userId,
                'amount' => $amount,
                'provider_name' => $providerName,
                'items' => $items,
                'vat_percentage' => $vatPercentage
            ], [
                'user_id' => 'required|integer|exists:users,id',
                'amount' => 'required|numeric|min:0.01',
                'provider_name' => 'required|string',
                'items' => 'required|array|min:1',
                'vat_percentage' => 'required|numeric|min:0|max:100'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Check eligibility
            $eligibility = $this->validateEligibility($userId, $amount, $providerName);
            if (!$eligibility['eligible']) {
                throw new \Exception('BNPL eligibility check failed: ' . $eligibility['reason']);
            }

            DB::beginTransaction();

            $provider = $this->getProvider($providerName);
            if (!$provider) {
                throw new \Exception('Invalid BNPL provider');
            }

            // Calculate payment breakdown
            $paymentBreakdown = $this->calculateBnplPayment($amount, $vatPercentage, $providerName);

            // Create order
            $order = Order::create([
                'user_id' => $userId,
                'status' => Order::$pending,
                'payment_method' => Order::$bnpl,
                'amount' => $amount,
                'tax' => $paymentBreakdown['vat_amount'],
                'total_amount' => $amount + $paymentBreakdown['vat_amount'],
                'bnpl_provider' => $provider->name,
                'bnpl_fee' => $paymentBreakdown['bnpl_fee'],
                'bnpl_fee_percentage' => $provider->fee_percentage,
                'installment_count' => $provider->installment_count,
                'bnpl_payment_schedule' => $paymentBreakdown['payment_schedule'],
                'created_at' => time()
            ]);

            // Create order items
            foreach ($items as $item) {
                $order->orderItems()->create([
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'webinar_id' => $item['webinar_id'] ?? null,
                    'bundle_id' => $item['bundle_id'] ?? null,
                    'product_id' => $item['product_id'] ?? null,
                    'reserve_meeting_id' => $item['reserve_meeting_id'] ?? null,
                    'ticket_id' => $item['ticket_id'] ?? null,
                    'discount' => $item['discount'] ?? 0,
                    'tax' => $item['tax'] ?? 0,
                    'amount' => $item['amount'] ?? 0,
                    'total_amount' => $item['total_amount'] ?? 0,
                    'created_at' => time()
                ]);
            }

            DB::commit();

            Log::info("BNPL order created successfully", [
                'order_id' => $order->id,
                'user_id' => $userId,
                'amount' => $amount,
                'provider' => $provider->name
            ]);

            return [
                'success' => true,
                'order' => $order,
                'payment_breakdown' => $paymentBreakdown
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("BNPL order creation failed: " . $e->getMessage(), [
                'user_id' => $userId,
                'amount' => $amount,
                'provider' => $providerName,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available BNPL providers
     */
    public function getAvailableProviders()
    {
        try {
            return BnplProvider::where('is_active', true)
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get available BNPL providers: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get provider by name with error handling
     */
    private function getProvider($providerName)
    {
        try {
        if (!$providerName) {
            return BnplProvider::where('is_active', true)->first();
        }

        return BnplProvider::where('name', $providerName)
                          ->where('is_active', true)
                          ->first();
        } catch (\Exception $e) {
            Log::error('Failed to get BNPL provider: ' . $e->getMessage(), [
                'provider_name' => $providerName
            ]);
            return null;
        }
    }

    /**
     * Generate payment schedule with validation
     */
    private function generatePaymentSchedule($totalAmount, $installmentCount)
    {
        try {
            if ($totalAmount <= 0 || $installmentCount <= 0) {
                throw new \Exception('Invalid amount or installment count for payment schedule');
            }

        $schedule = [];
        $installmentAmount = round($totalAmount / $installmentCount, 2);
        $remainingAmount = $totalAmount;
        
        for ($i = 1; $i <= $installmentCount; $i++) {
            if ($i === $installmentCount) {
                // Last installment gets any remaining amount due to rounding
                $amount = $remainingAmount;
            } else {
                $amount = $installmentAmount;
            }
            
            $schedule[] = [
                'installment_number' => $i,
                'amount' => round($amount, 2),
                'due_date' => now()->addDays(($i - 1) * 30)->format('Y-m-d'),
                    'status' => 'pending',
                    'created_at' => now()->toISOString()
            ];
            
            $remainingAmount -= $amount;
        }
        
        return $schedule;

        } catch (\Exception $e) {
            Log::error('Failed to generate payment schedule: ' . $e->getMessage(), [
                'total_amount' => $totalAmount,
                'installment_count' => $installmentCount
            ]);
            throw $e;
        }
    }

    /**
     * Create installment records with error handling
     */
    private function createInstallmentRecords(Sale $sale, $paymentBreakdown)
    {
        try {
        // This would create installment records in your installment table
        // For now, just logging the schedule
        Log::info("Installment schedule created for sale {$sale->id}:", $paymentBreakdown['payment_schedule']);

            // You can implement actual installment record creation here
            // based on your existing installment system

        } catch (\Exception $e) {
            Log::error('Failed to create installment records: ' . $e->getMessage(), [
                'sale_id' => $sale->id
            ]);
            throw $e;
        }
    }

    /**
     * Generate unique order number with validation
     */
    private function generateOrderNumber()
    {
        try {
        $prefix = 'BNPL';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
            $orderNumber = "{$prefix}{$timestamp}{$random}";

            // Ensure uniqueness
            $attempts = 0;
            while (Sale::where('order_number', $orderNumber)->exists() && $attempts < 10) {
                $random = strtoupper(substr(md5(uniqid() . $attempts), 0, 4));
                $orderNumber = "{$prefix}{$timestamp}{$random}";
                $attempts++;
            }

            return $orderNumber;

        } catch (\Exception $e) {
            Log::error('Failed to generate order number: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get BNPL payment summary for a user with error handling
     */
    public function getUserBnplPayments($userId)
    {
        try {
        return Sale::where('buyer_id', $userId)
                   ->where('payment_method', 'bnpl')
                   ->with(['course', 'bundle'])
                   ->latest()
                   ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get user BNPL payments: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return collect();
        }
    }

    /**
     * Get BNPL payment statistics with error handling
     */
    public function getBnplStatistics($dateFrom = null, $dateTo = null)
    {
        try {
        $query = Sale::where('payment_method', 'bnpl');
        
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        
        $totalPayments = $query->count();
        $totalAmount = $query->sum('amount');
        $totalFees = $query->sum('bnpl_fee');
        
        $providers = $query->select('bnpl_provider', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
                          ->groupBy('bnpl_provider')
                          ->get();
        
        return [
            'total_payments' => $totalPayments,
            'total_amount' => $totalAmount,
            'total_fees' => $totalFees,
            'providers' => $providers
        ];

        } catch (\Exception $e) {
            Log::error('Failed to get BNPL statistics: ' . $e->getMessage());
            return [
                'total_payments' => 0,
                'total_amount' => 0,
                'total_fees' => 0,
                'providers' => collect()
            ];
        }
    }

    /**
     * Validate BNPL eligibility with comprehensive checks
     */
    public function validateEligibility($userId, $amount, $providerName = null)
    {
        try {
        $provider = $this->getProvider($providerName);
        if (!$provider) {
            return ['eligible' => false, 'reason' => 'Provider not available'];
        }

        // Check minimum amount
            $minAmount = $provider->config['min_amount'] ?? 0;
            if ($amount < $minAmount) {
                return ['eligible' => false, 'reason' => "Amount below minimum threshold (${minAmount})"];
        }

        // Check maximum amount
            $maxAmount = $provider->config['max_amount'] ?? 999999;
            if ($amount > $maxAmount) {
                return ['eligible' => false, 'reason' => "Amount above maximum threshold (${maxAmount})"];
        }

        // Check user's existing BNPL payments
        $existingPayments = Sale::where('buyer_id', $userId)
                               ->where('payment_method', 'bnpl')
                               ->where('status', '!=', 'refunded')
                               ->count();
        
            $maxConcurrent = $provider->config['max_concurrent_payments'] ?? 3;
            if ($existingPayments >= $maxConcurrent) {
                return ['eligible' => false, 'reason' => "Maximum concurrent BNPL payments reached (${maxConcurrent})"];
            }

            // Check user's payment history
            $overduePayments = Sale::where('buyer_id', $userId)
                                  ->where('payment_method', 'bnpl')
                                  ->where('status', 'overdue')
                                  ->count();

            if ($overduePayments > 0) {
                return ['eligible' => false, 'reason' => 'User has overdue BNPL payments'];
        }

        return ['eligible' => true, 'provider' => $provider];

        } catch (\Exception $e) {
            Log::error('BNPL eligibility validation failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'amount' => $amount,
                'provider' => $providerName
            ]);
            return ['eligible' => false, 'reason' => 'Validation error occurred'];
        }
    }

    /**
     * Process installment payment with error handling
     */
    public function processInstallmentPayment($saleId, $installmentNumber)
    {
        try {
            $sale = Sale::findOrFail($saleId);
            
            if ($sale->payment_method !== 'bnpl') {
                throw new \Exception('Not a BNPL payment');
            }

            // Update installment status
            $paymentDetails = $sale->payment_details;
            if (isset($paymentDetails['payment_schedule'][$installmentNumber - 1])) {
                $paymentDetails['payment_schedule'][$installmentNumber - 1]['status'] = 'paid';
                $paymentDetails['payment_schedule'][$installmentNumber - 1]['paid_at'] = now()->toISOString();
            }

            $sale->update([
                'payment_details' => $paymentDetails
            ]);

            // Check if all installments are paid
            $allPaid = collect($paymentDetails['payment_schedule'])->every(function ($installment) {
                return $installment['status'] === 'paid';
            });

            if ($allPaid) {
                $sale->markAsPaid();
            }

            Log::info("Installment payment processed successfully", [
                'sale_id' => $saleId,
                'installment_number' => $installmentNumber,
                'all_paid' => $allPaid
            ]);

            return ['success' => true, 'all_paid' => $allPaid];

        } catch (\Exception $e) {
            Log::error("Failed to process installment payment: " . $e->getMessage(), [
                'sale_id' => $saleId,
                'installment_number' => $installmentNumber
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

