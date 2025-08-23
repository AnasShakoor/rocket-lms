<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('bnpl_provider')->nullable()->comment('BNPL provider name if using BNPL');
            $table->decimal('bnpl_fee', 10, 2)->nullable()->comment('BNPL fee amount');
            $table->decimal('bnpl_fee_percentage', 5, 2)->nullable()->comment('BNPL fee percentage');
            $table->integer('installment_count')->nullable()->comment('Number of installments');
            $table->json('bnpl_payment_schedule')->nullable()->comment('Installment payment schedule');
        });

        // Modify the enum using raw SQL to avoid compatibility issues
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_method` ENUM('credit','payment_channel','bnpl') null");
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'bnpl_provider',
                'bnpl_fee',
                'bnpl_fee_percentage',
                'installment_count',
                'bnpl_payment_schedule'
            ]);
        });

        // Revert the enum back to original values
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_method` ENUM('credit','payment_channel') null");
    }
};
