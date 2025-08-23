<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('certificates', function (Blueprint $table) {
            DB::statement("ALTER TABLE `certificates` MODIFY COLUMN `type` ENUM('quiz', 'course', 'bundle') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `user_grade`");

            $table->integer('bundle_id')->unsigned()->nullable()->after('webinar_id');
            $table->foreign('bundle_id')->on('bundles')->references('id')->cascadeOnDelete();
        });

        // Modify certificates_templates.type
        if (Schema::hasColumn('certificates_templates', 'type')) {
            DB::statement("ALTER TABLE `certificates_templates` MODIFY COLUMN `type` ENUM('quiz', 'course', 'bundle') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `image`");
        }
    }
};
