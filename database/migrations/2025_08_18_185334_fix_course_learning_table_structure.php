<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('course_learning', function (Blueprint $table) {
            // Add missing columns that the model expects
            if (!Schema::hasColumn('course_learning', 'status')) {
                $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending')->after('session_id');
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
            
            // Add indexes for performance
            // $table->index(['user_id', 'status']);
            // $table->index(['webinar_id', 'status']);
            // $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_learning', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['webinar_id', 'status']);
            $table->dropIndex(['status']);
            
            $table->dropColumn(['status', 'progress', 'enrolled_at', 'started_at', 'completed_at', 'notes']);
        });
    }
};
