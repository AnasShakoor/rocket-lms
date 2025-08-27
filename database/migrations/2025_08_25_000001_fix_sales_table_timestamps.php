<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First, let's check if the sales table exists and what columns it has
        if (Schema::hasTable('sales')) {
            // Fix created_at column if it's an integer
            if (Schema::hasColumn('sales', 'created_at')) {
                try {
                    DB::statement("ALTER TABLE `sales` MODIFY COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
                } catch (\Exception $e) {
                    // If the column is already a timestamp, this will fail but that's okay
                    // Log it for debugging
                    \Log::info('created_at column is already a timestamp or could not be modified: ' . $e->getMessage());
                }
            }

            // Fix updated_at column if it's an integer
            if (Schema::hasColumn('sales', 'updated_at')) {
                try {
                    DB::statement("ALTER TABLE `sales` MODIFY COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                } catch (\Exception $e) {
                    // If the column is already a timestamp, this will fail but that's okay
                    // Log it for debugging
                    \Log::info('updated_at column is already a timestamp or could not be modified: ' . $e->getMessage());
                }
            }

            // Ensure we have proper timestamp columns
            if (!Schema::hasColumn('sales', 'created_at')) {
                Schema::table('sales', function (Blueprint $table) {
                    $table->timestamp('created_at')->nullable();
                });
            }

            if (!Schema::hasColumn('sales', 'updated_at')) {
                Schema::table('sales', function (Blueprint $table) {
                    $table->timestamp('updated_at')->nullable();
                });
            }
        }
    }

    public function down()
    {
        // This migration is fixing data types, so we don't need to reverse it
        // The columns will remain as timestamps which is the correct format
    }
};
