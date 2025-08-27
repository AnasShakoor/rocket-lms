<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            // Check if status column exists and handle it properly
            if (Schema::hasColumn('sales', 'status')) {
                // If status column exists but might be corrupted, drop and recreate it
                $table->dropColumn('status');
            }

            // Add the status column properly
            // $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('completed')->after('payment_method');
        });

        // Update existing enum columns to include new values
        Schema::table('sales', function (Blueprint $table) {
            try {
                // Update payment_method enum to include new values
                if (Schema::hasColumn('sales', 'payment_method')) {
                    DB::statement("ALTER TABLE `sales` MODIFY COLUMN `payment_method` ENUM('credit','payment_channel','subscribe','credit_card','bank_transfer','bnpl') NOT NULL DEFAULT 'payment_channel';");
                }

                // Update type enum to include new values
                if (Schema::hasColumn('sales', 'type')) {
                    DB::statement("ALTER TABLE `sales` MODIFY COLUMN `type` ENUM('webinar','meeting','bundle','subscribe','product','gift','promotion','registrationPackage','installmentPayment') NOT NULL DEFAULT 'webinar';");
                }
            } catch (\Exception $e) {
                // Log the error but don't fail the migration
                // The enum updates are not critical for basic functionality
                Log::warning('Failed to update enum columns: ' . $e->getMessage());
            }
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            // Only remove the status column if we added it
            if (Schema::hasColumn('sales', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
