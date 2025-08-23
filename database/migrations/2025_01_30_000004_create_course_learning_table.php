<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Check if the table exists first
        if (!Schema::hasTable('course_learning')) {
            Schema::create('course_learning', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('webinar_id');
                $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
                $table->integer('progress')->default(0);
                $table->timestamp('enrolled_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'webinar_id']);
                $table->index(['user_id', 'status']);
                $table->index(['webinar_id', 'status']);
                $table->index('status');

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('webinar_id')->references('id')->on('webinars')->onDelete('cascade');
            });
        } else {
            // If table exists, just add missing columns
            Schema::table('course_learning', function (Blueprint $table) {
                if (!Schema::hasColumn('course_learning', 'webinar_id')) {
                    $table->unsignedInteger('webinar_id')->after('user_id');
                }
                if (!Schema::hasColumn('course_learning', 'status')) {
                    $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending')->after('webinar_id');
                }
                if (!Schema::hasColumn('course_learning', 'progress')) {
                    $table->integer('progress')->default(0)->after('status');
                }
                if (!Schema::hasColumn('course_learning', 'enrolled_at')) {
                    $table->timestamp('enrolled_at')->nullable()->after('progress');
                }
                if (!Schema::hasColumn('course_learning', 'started_at')) {
                    $table->timestamp('started_at')->nullable()->after('enrolled_at');
                }
                if (!Schema::hasColumn('course_learning', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('started_at');
                }
                if (!Schema::hasColumn('course_learning', 'notes')) {
                    $table->text('notes')->nullable()->after('completed_at');
                }
            });
        }
    }

    public function down()
    {
        Schema::table('course_learning', function (Blueprint $table) {
            $table->dropColumn(['webinar_id', 'status', 'progress', 'enrolled_at', 'started_at', 'completed_at', 'notes']);
        });
    }
};

