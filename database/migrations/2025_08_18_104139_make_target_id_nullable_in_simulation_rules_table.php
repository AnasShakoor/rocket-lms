<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeTargetIdNullableInSimulationRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('simulation_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('target_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('simulation_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('target_id')->nullable(false)->change();
        });
    }
}
