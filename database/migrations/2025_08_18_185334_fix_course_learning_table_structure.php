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
            if (!Schema::hasColumn('course_learning', 'webinar_id')) {
                $table->integer('webinar_id')->unsigned()->nullable()->after('user_id');
            }
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
        });

        // Add indexes for performance (with proper checks)
        $this->addIndexes();
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

            $table->dropColumn(['webinar_id', 'status', 'progress', 'enrolled_at', 'started_at', 'completed_at', 'notes']);
        });
    }

    /**
     * Add indexes with proper checks to avoid duplicates
     */
    private function addIndexes()
    {
        $connection = Schema::getConnection();
        $tableName = 'course_learning';

        // Check if indexes exist using SHOW INDEX and add them if they don't
        $this->createIndexIfNotExists($connection, $tableName, 'course_learning_user_id_status_index', ['user_id', 'status']);
        $this->createIndexIfNotExists($connection, $tableName, 'course_learning_webinar_id_status_index', ['webinar_id', 'status']);
        $this->createIndexIfNotExists($connection, $tableName, 'course_learning_status_index', ['status']);
    }

    /**
     * Create index if it doesn't exist
     */
    private function createIndexIfNotExists($connection, $tableName, $indexName, $columns)
    {
        $result = $connection->select("SHOW INDEX FROM {$tableName} WHERE Key_name = ?", [$indexName]);

        if (empty($result)) {
            $columnList = implode(', ', $columns);
            $connection->statement("CREATE INDEX {$indexName} ON {$tableName} ({$columnList})");
        }
    }
};
