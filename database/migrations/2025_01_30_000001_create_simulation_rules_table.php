<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('simulation_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('target_type', ['course', 'student', 'bundle']);
            $table->unsignedBigInteger('target_id')->nullable()->comment('course_id, user_id, or bundle_id');
            $table->integer('enrollment_offset_days')->default(-12)->comment('Days from purchase date (negative for past dates)');
            $table->integer('completion_offset_days')->default(2)->comment('Days from fake enrollment');
            $table->integer('inter_course_gap_days')->default(1)->comment('Gap between courses in sequence');
            $table->json('course_order')->nullable()->comment('Custom course order for student/bundle');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('created_by')->comment('Admin who created the rule');
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('status');
            $table->index('created_by');

            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('simulation_rules');
    }
};
