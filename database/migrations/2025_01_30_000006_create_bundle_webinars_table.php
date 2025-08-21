<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bundle_webinars', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('bundle_id');
            $table->unsignedInteger('webinar_id');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            
            $table->unique(['bundle_id', 'webinar_id']);
            $table->index(['bundle_id', 'sort_order']);
            $table->index('webinar_id');
            
            $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('cascade');
            $table->foreign('webinar_id')->references('id')->on('webinars')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bundle_webinars');
    }
};

