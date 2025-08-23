<?php

namespace Database\Seeders;

use App\Models\BnplProvider;
use Illuminate\Database\Seeder;

class BnplProvidersSeeder extends Seeder
{
    public function run()
    {
        $providers = [
            [
                'name' => 'Tabby',
                'logo_path' => 'bnpl/tabby-logo.png',
                'fee_percentage' => 8.00,
                'installment_count' => 4,
                'is_active' => true,
                'config' => [
                    'min_amount' => 50.00,
                    'max_amount' => 5000.00,
                    'max_concurrent_payments' => 3,
                    'description' => 'Split your purchase into 4 interest-free installments',
                    'website' => 'https://tabby.ai',
                    'supported_regions' => ['UAE', 'Saudi Arabia', 'Kuwait', 'Bahrain']
                ]
            ],
            [
                'name' => 'Tamara',
                'logo_path' => 'bnpl/tamara-logo.png',
                'fee_percentage' => 8.50,
                'installment_count' => 3,
                'is_active' => true,
                'config' => [
                    'min_amount' => 100.00,
                    'max_amount' => 3000.00,
                    'max_concurrent_payments' => 2,
                    'description' => 'Pay in 3 installments with no hidden fees',
                    'website' => 'https://tamara.co',
                    'supported_regions' => ['UAE', 'Saudi Arabia', 'Kuwait']
                ]
            ],
            [
                'name' => 'Spotii',
                'logo_path' => 'bnpl/spotii-logo.png',
                'fee_percentage' => 9.00,
                'installment_count' => 4,
                'is_active' => true,
                'config' => [
                    'min_amount' => 75.00,
                    'max_amount' => 4000.00,
                    'max_concurrent_payments' => 3,
                    'description' => 'Split your payment into 4 easy installments',
                    'website' => 'https://spotii.com',
                    'supported_regions' => ['UAE', 'Saudi Arabia']
                ]
            ],
            [
                'name' => 'Cashew',
                'logo_path' => 'bnpl/cashew-logo.png',
                'fee_percentage' => 7.50,
                'installment_count' => 3,
                'is_active' => true,
                'config' => [
                    'min_amount' => 50.00,
                    'max_amount' => 2500.00,
                    'max_concurrent_payments' => 2,
                    'description' => 'Pay in 3 installments with competitive rates',
                    'website' => 'https://cashewpayments.com',
                    'supported_regions' => ['UAE', 'Saudi Arabia', 'Kuwait', 'Bahrain', 'Oman']
                ]
            ],
            [
                'name' => 'Postpay',
                'logo_path' => 'bnpl/postpay-logo.png',
                'fee_percentage' => 8.25,
                'installment_count' => 4,
                'is_active' => true,
                'config' => [
                    'min_amount' => 100.00,
                    'max_amount' => 5000.00,
                    'max_concurrent_payments' => 3,
                    'description' => 'Split your purchase into 4 interest-free installments',
                    'website' => 'https://postpay.io',
                    'supported_regions' => ['UAE', 'Saudi Arabia', 'Kuwait', 'Bahrain', 'Qatar']
                ]
            ]
        ];

        foreach ($providers as $providerData) {
            BnplProvider::updateOrCreate(
                ['name' => $providerData['name']],
                $providerData
            );
        }

        $this->command->info('BNPL providers seeded successfully!');
    }
}

