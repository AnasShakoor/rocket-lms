<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BnplProvider;

class BnplProvidersSeeder extends Seeder
{
    public function run()
    {
        $providers = [
            [
                'name' => 'Tamara',
                'fee_percentage' => 0.00,
                'installment_count' => 4,
                'is_active' => true,
                'config' => [
                    'min_amount' => 50,
                    'max_amount' => 5000,
                    'supported_currencies' => ['SAR'],
                    'api_endpoint' => 'https://api.tamara.co'
                ]
            ],
            [
                'name' => 'Tabby',
                'fee_percentage' => 0.00,
                'installment_count' => 4,
                'is_active' => true,
                'config' => [
                    'min_amount' => 100,
                    'max_amount' => 10000,
                    'supported_currencies' => ['SAR', 'AED'],
                    'api_endpoint' => 'https://api.tabby.ai'
                ]
            ],
            [
                'name' => 'MICB',
                'fee_percentage' => 2.50,
                'installment_count' => 6,
                'is_active' => true,
                'config' => [
                    'min_amount' => 200,
                    'max_amount' => 15000,
                    'supported_currencies' => ['SAR'],
                    'api_endpoint' => 'https://api.micb.com.sa'
                ]
            ],
            [
                'name' => 'Salla Pay',
                'fee_percentage' => 1.99,
                'installment_count' => 3,
                'is_active' => true,
                'config' => [
                    'min_amount' => 50,
                    'max_amount' => 3000,
                    'supported_currencies' => ['SAR'],
                    'api_endpoint' => 'https://api.salla.sa'
                ]
            ],
            [
                'name' => 'STC Pay',
                'fee_percentage' => 0.00,
                'installment_count' => 4,
                'is_active' => true,
                'config' => [
                    'min_amount' => 100,
                    'max_amount' => 8000,
                    'supported_currencies' => ['SAR'],
                    'api_endpoint' => 'https://api.stcpay.com.sa'
                ]
            ]
        ];

        foreach ($providers as $providerData) {
            BnplProvider::updateOrCreate(
                ['name' => $providerData['name']],
                $providerData
            );
        }
    }
}

