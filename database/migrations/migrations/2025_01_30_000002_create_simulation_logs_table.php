<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('simulation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id');
            $table->unsignedInteger('user_id'); // Changed to match users.id type
            $table->unsignedBigInteger('course_id');
            $table->date('purchase_date');
            $table->date('fake_enroll_date');
            $table->date('fake_completion_date');
            $table->enum('status', ['success', 'skipped', 'error']);
            $table->text('notes')->nullable()->comment('Reason for skip or error');
            $table->unsignedInteger('triggered_by_admin_id'); // Changed to match users.id type
            $table->timestamps();
            
            $table->index(['rule_id', 'user_id', 'course_id']);
            $table->index('status');
            $table->index('triggered_by_admin_id');
            
            // Add foreign key constraints
            $table->foreign('rule_id')->references('id')->on('simulation_rules')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('course_id')->references('id')->on('webinars')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('simulation_logs');
    }
};
