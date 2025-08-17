<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Rename 'description' to 'subtitle'
        DB::statement("ALTER TABLE `subscribe_translations` CHANGE `description` `subtitle` TEXT NULL");

        // Add new 'description' column
        Schema::table('subscribe_translations', function (Blueprint $table) {
            $table->text('description')->nullable();
        });
    }
};
