<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bnpl_providers', function (Blueprint $table) {
            // BNPL Fee fields
            $table->decimal('surcharge_percentage', 5, 2)->default(8.00)->comment('Surcharge percentage, minimum 8%');
            $table->text('fee_description')->nullable()->comment('Fee added on top of (course price + VAT)');
            $table->decimal('bnpl_fee', 10, 2)->default(0.00)->comment('Fixed BNPL fee amount');

            // API Keys
            $table->string('public_api_key')->nullable()->comment('Public API key for the provider');
            $table->string('secret_api_key')->nullable()->comment('Secret API key for the provider');

            // Merchant Details
            $table->string('merchant_code')->nullable()->comment('Merchant code from provider');
            $table->string('merchant_id')->nullable()->comment('Merchant ID from provider');

            // MISpay Plug-In Details
            $table->string('app_id')->nullable()->comment('APP ID for MISpay integration');
            $table->string('app_secret_key')->nullable()->comment('APP Secret Key for MISpay');
            $table->string('widget_access_key')->nullable()->comment('Widget access key for MISpay');

            // Add indexes for better performance
            $table->index('surcharge_percentage');
            $table->index('merchant_code');
        });
    }

    public function down(): void
    {
        Schema::table('bnpl_providers', function (Blueprint $table) {
            $table->dropIndex(['surcharge_percentage']);
            $table->dropIndex(['merchant_code']);

            $table->dropColumn([
                'surcharge_percentage',
                'fee_description',
                'bnpl_fee',
                'public_api_key',
                'secret_api_key',
                'merchant_code',
                'merchant_id',
                'app_id',
                'app_secret_key',
                'widget_access_key'
            ]);
        });
    }
};
