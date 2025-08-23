<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add BNPL fields to cart table
        Schema::table('cart', function (Blueprint $table) {
            $table->string('bnpl_provider')->nullable()->comment('BNPL provider name if using BNPL');
            $table->integer('bnpl_installments')->nullable()->comment('Number of installments for BNPL');
            $table->decimal('bnpl_fee', 10, 2)->nullable()->comment('BNPL fee amount');
        });

        // Add BNPL fields to product_orders table
        Schema::table('product_orders', function (Blueprint $table) {
            $table->string('bnpl_provider')->nullable()->comment('BNPL provider name if using BNPL');
            $table->integer('bnpl_installments')->nullable()->comment('Number of installments for BNPL');
            $table->decimal('bnpl_fee', 10, 2)->nullable()->comment('BNPL fee amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove BNPL fields from cart table
        Schema::table('cart', function (Blueprint $table) {
            $table->dropColumn(['bnpl_provider', 'bnpl_installments', 'bnpl_fee']);
        });

        // Remove BNPL fields from product_orders table
        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropColumn(['bnpl_provider', 'bnpl_installments', 'bnpl_fee']);
        });
    }
};
