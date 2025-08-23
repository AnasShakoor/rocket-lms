<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddNewStatusInReserveMeetingsTable extends Migration
{
    public function up()
    {
        // Modify ENUM column
        DB::statement("
            ALTER TABLE `reserve_meetings` 
            MODIFY COLUMN `status` ENUM('pending','open','finished','canceled') 
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
        ");

        Schema::table('reserve_meetings', function (Blueprint $table) {
            $table->integer('sale_id')->unsigned()->nullable();
            $table->integer('date')->unsigned();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('reserve_meetings', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn(['sale_id', 'date']);
        });

        DB::statement("
            ALTER TABLE `reserve_meetings` 
            MODIFY COLUMN `status` ENUM('pending','open','finished') 
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
        ");
    }
}
