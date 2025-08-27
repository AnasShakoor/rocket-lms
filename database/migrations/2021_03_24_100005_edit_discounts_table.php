<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class EditDiscountsTable extends Migration
{
    public function up()
    {
        // Drop columns safely if they exist
        if (Schema::hasColumn('discounts', 'name')) {
            DB::statement("ALTER TABLE `discounts` DROP COLUMN `name`");
        }

        if (Schema::hasColumn('discount_users', 'count')) {
            DB::statement("ALTER TABLE `discount_users` DROP COLUMN `count`");
        }

        if (Schema::hasColumn('discounts', 'started_at')) {
            DB::statement("ALTER TABLE `discounts` DROP COLUMN `started_at`");
        }

        // Modify created_at column using raw SQL (no doctrine/dbal)
        DB::statement("ALTER TABLE `discounts` MODIFY COLUMN `created_at` INT UNSIGNED NOT NULL AFTER `expired_at`");

        // Add new columns
        Schema::table('discounts', function (Blueprint $table) {
            $table->string('title')->after('creator_id');
            $table->string('code', 64)->unique()->after('title');
            $table->enum('type', ['all_users', 'special_users'])->after('count');
        });
    }
}
