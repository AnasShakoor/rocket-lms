<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
<<<<<<< HEAD:database/migrations/2025_01_30_000004_create_course_learning_table.php
        // Schema::create('course_learning', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedInteger('user_id');
        //     $table->unsignedInteger('webinar_id');
        //     $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
        //     $table->integer('progress')->default(0);
        //     $table->timestamp('enrolled_at')->nullable();
        //     $table->timestamp('started_at')->nullable();
        //     $table->timestamp('completed_at')->nullable();
        //     $table->text('notes')->nullable();
        //     $table->timestamps();
=======
   if (!Schema::hasTable('course_learning')) {
        Schema::create('course_learning', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('webinar_id');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->integer('progress')->default(0);
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
>>>>>>> origin/new_branch_2nd_merge_adding_moysar:database/migrations/migrations/2025_01_30_000004_create_course_learning_table.php
            
        //     $table->unique(['user_id', 'webinar_id']);
        //     $table->index(['user_id', 'status']);
        //     $table->index(['webinar_id', 'status']);
        //     $table->index('status');
            
        //     $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        //     $table->foreign('webinar_id')->references('id')->on('webinars')->onDelete('cascade');
        // });
    }
}

    public function down()
    {
        Schema::dropIfExists('course_learning');
    }
};

