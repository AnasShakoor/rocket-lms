<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('archive_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name')->comment('Name of the table being archived');
            $table->unsignedBigInteger('record_id')->comment('ID of the archived record');
            $table->json('record_data')->comment('Complete record data before archiving');
            $table->enum('action', ['archive', 'restore']);
            $table->unsignedInteger('admin_id')->comment('Admin who performed the action'); // Changed to match users.id type
            $table->string('archive_reason')->nullable()->comment('Optional reason for archiving');
            $table->timestamps();
            
            $table->index(['table_name', 'record_id']);
            $table->index('action');
            $table->index('admin_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('archive_logs');
    }
};
