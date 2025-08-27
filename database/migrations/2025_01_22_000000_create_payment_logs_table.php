<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->string('payment_gateway'); // Moyasar, PayPal, etc.
            $table->string('gateway_payment_id'); // External payment ID from gateway
            $table->string('status'); // paid, failed, pending, etc.
            $table->decimal('amount', 13, 2); // Base amount
            $table->decimal('currency_amount', 13, 2); // Amount in gateway currency
            $table->string('currency', 3); // SAR, USD, etc.
            $table->decimal('gateway_fee', 13, 2)->nullable(); // Gateway processing fee
            $table->decimal('tax_amount', 13, 2)->nullable(); // Tax amount
            $table->decimal('discount_amount', 13, 2)->nullable(); // Discount amount
            $table->decimal('surcharge_amount', 13, 2)->nullable(); // Surcharge amount
            $table->decimal('total_amount', 13, 2); // Final total after all fees
            $table->string('payment_method')->nullable(); // creditcard, applepay, etc.
            $table->string('card_type')->nullable(); // visa, mastercard, mada, etc.
            $table->string('card_last4')->nullable(); // Last 4 digits of card
            $table->string('card_brand')->nullable(); // Card brand
            $table->string('card_country')->nullable(); // Card issuing country
            $table->json('gateway_response')->nullable(); // Full response from gateway
            $table->json('metadata')->nullable(); // Additional payment metadata
            $table->text('description')->nullable(); // Payment description
            $table->string('error_message')->nullable(); // Error message if failed
            $table->string('ip_address')->nullable(); // Customer IP address
            $table->string('user_agent')->nullable(); // Customer user agent
            $table->timestamp('payment_date')->nullable(); // When payment was processed
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();

            // Indexes for better performance
            $table->index(['order_id']);
            $table->index(['user_id']);
            $table->index(['gateway_payment_id']);
            $table->index(['status']);
            $table->index(['payment_date']);
            $table->index(['payment_gateway']);

            // Foreign key constraints
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_logs');
    }
}
