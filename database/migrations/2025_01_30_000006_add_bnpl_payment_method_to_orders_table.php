<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_method` ENUM('credit','payment_channel','bnpl') null");
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_method` ENUM('credit','payment_channel') null");
        });
    }
};
