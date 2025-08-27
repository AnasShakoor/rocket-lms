<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
<<<<<<< HEAD:database/migrations/2025_01_30_000006_create_bundle_webinars_table.php
        // Schema::create('bundle_webinars', function (Blueprint $table) {
            // $table->id();
            // $table->unsignedInteger('bundle_id');
            // $table->unsignedInteger('webinar_id');
            // $table->integer('sort_order')->default(0);
            // $table->boolean('is_required')->default(true);
            // $table->timestamps();
=======
        if(!Schema::hasTable('bundle_webinars')) {     
        Schema::create('bundle_webinars', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('bundle_id');
            $table->unsignedInteger('webinar_id');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
>>>>>>> origin/new_branch_2nd_merge_adding_moysar:database/migrations/migrations/2025_01_30_000006_create_bundle_webinars_table.php
            
            // $table->unique(['bundle_id', 'webinar_id']);
            // $table->index(['bundle_id', 'sort_order']);
            // $table->index('webinar_id');
            
            // $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('cascade');
            // $table->foreign('webinar_id')->references('id')->on('webinars')->onDelete('cascade');
        // });
    }
}

    public function down()
    {
        Schema::dropIfExists('bundle_webinars');
    }
};

