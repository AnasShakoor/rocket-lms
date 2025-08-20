<?php

namespace App\Services;

use App\Models\BnplProvider;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BnplPaymentService
{
    /**
     * Calculate BNPL payment breakdown
     */
    public function calculateBnplPayment($basePrice, $vatPercentage = 15, $providerName = null)
    {
        $provider = $this->getProvider($providerName);
        
        if (!$provider || !$provider->is_active) {
            throw new \Exception('BNPL provider not available or inactive');
        }

        $priceWithVat = $basePrice * (1 + ($vatPercentage / 100));
        $bnplFee = $priceWithVat * ($provider->fee_percentage / 100);
        $totalAmount = $priceWithVat + $bnplFee;
        $installmentAmount = round($totalAmount / $provider->installment_count, 2);

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
    }

    /**
     * Process BNPL payment
     */
    public function processBnplPayment($userId, $courseId, $bundleId, $amount, $providerName, $installments = null)
    {
        try {
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

            Log::info("BNPL payment processed successfully for order: {$orderNumber}");
            
            return [
                'success' => true,
                'sale_id' => $sale->id,
                'order_number' => $orderNumber,
                'payment_breakdown' => $paymentBreakdown
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("BNPL payment processing failed: " . $e->getMessage());
            
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
        return BnplProvider::where('is_active', true)->get();
    }

    /**
     * Get provider by name
     */
    private function getProvider($providerName)
    {
        if (!$providerName) {
            return BnplProvider::where('is_active', true)->first();
        }

        return BnplProvider::where('name', $providerName)
                          ->where('is_active', true)
                          ->first();
    }

    /**
     * Generate payment schedule
     */
    private function generatePaymentSchedule($totalAmount, $installmentCount)
    {
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
                'status' => 'pending'
            ];
            
            $remainingAmount -= $amount;
        }
        
        return $schedule;
    }

    /**
     * Create installment records
     */
    private function createInstallmentRecords(Sale $sale, $paymentBreakdown)
    {
        // This would create installment records in your installment table
        // For now, just logging the schedule
        Log::info("Installment schedule created for sale {$sale->id}:", $paymentBreakdown['payment_schedule']);
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber()
    {
        $prefix = 'BNPL';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return "{$prefix}{$timestamp}{$random}";
    }

    /**
     * Get BNPL payment summary for a user
     */
    public function getUserBnplPayments($userId)
    {
        return Sale::where('buyer_id', $userId)
                   ->where('payment_method', 'bnpl')
                   ->with(['course', 'bundle'])
                   ->latest()
                   ->get();
    }

    /**
     * Get BNPL payment statistics
     */
    public function getBnplStatistics($dateFrom = null, $dateTo = null)
    {
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
    }

    /**
     * Validate BNPL eligibility
     */
    public function validateEligibility($userId, $amount, $providerName = null)
    {
        $provider = $this->getProvider($providerName);
        if (!$provider) {
            return ['eligible' => false, 'reason' => 'Provider not available'];
        }

        // Check minimum amount
        if ($amount < $provider->config['min_amount'] ?? 0) {
            return ['eligible' => false, 'reason' => 'Amount below minimum threshold'];
        }

        // Check maximum amount
        if ($amount > $provider->config['max_amount'] ?? 999999) {
            return ['eligible' => false, 'reason' => 'Amount above maximum threshold'];
        }

        // Check user's existing BNPL payments
        $existingPayments = Sale::where('buyer_id', $userId)
                               ->where('payment_method', 'bnpl')
                               ->where('status', '!=', 'refunded')
                               ->count();
        
        if ($existingPayments >= ($provider->config['max_concurrent_payments'] ?? 3)) {
            return ['eligible' => false, 'reason' => 'Maximum concurrent BNPL payments reached'];
        }

        return ['eligible' => true, 'provider' => $provider];
    }

    /**
     * Process installment payment
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

            return ['success' => true, 'all_paid' => $allPaid];

        } catch (\Exception $e) {
            Log::error("Failed to process installment payment: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

