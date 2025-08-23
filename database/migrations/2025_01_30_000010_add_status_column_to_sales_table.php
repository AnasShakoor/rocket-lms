<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('completed')->after('total_amount');
        });

        // Update existing records to set status based on refund_at
        DB::statement("UPDATE sales SET status = 'refunded' WHERE refund_at IS NOT NULL");
        DB::statement("UPDATE sales SET status = 'completed' WHERE refund_at IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
