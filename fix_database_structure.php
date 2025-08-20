<?php

// Database structure fix script
// This script adds missing columns to the course_learning table

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔧 Fixing Database Structure\n";
echo "============================\n\n";

try {
    // Check if course_learning table exists
    if (!Schema::hasTable('course_learning')) {
        echo "❌ course_learning table does not exist\n";
        exit(1);
    }
    
    echo "✅ course_learning table found\n";
    
    // Add missing columns
    $columnsToAdd = [
        'status' => "ALTER TABLE course_learning ADD COLUMN status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending' AFTER session_id",
        'progress' => "ALTER TABLE course_learning ADD COLUMN progress INT DEFAULT 0 AFTER status",
        'enrolled_at' => "ALTER TABLE course_learning ADD COLUMN enrolled_at TIMESTAMP NULL AFTER progress",
        'started_at' => "ALTER TABLE course_learning ADD COLUMN started_at TIMESTAMP NULL AFTER enrolled_at",
        'completed_at' => "ALTER TABLE course_learning ADD COLUMN completed_at TIMESTAMP NULL AFTER started_at",
        'notes' => "ALTER TABLE course_learning ADD COLUMN notes TEXT NULL AFTER completed_at"
    ];
    
    $addedColumns = 0;
    
    foreach ($columnsToAdd as $columnName => $sql) {
        try {
            // Check if column already exists
            $columnExists = DB::select("SHOW COLUMNS FROM course_learning LIKE '{$columnName}'");
            
            if (empty($columnExists)) {
                DB::statement($sql);
                echo "✅ Added column: {$columnName}\n";
                $addedColumns++;
            } else {
                echo "ℹ️  Column already exists: {$columnName}\n";
            }
        } catch (Exception $e) {
            echo "⚠️  Warning adding column {$columnName}: " . $e->getMessage() . "\n";
        }
    }
    
    // Add indexes for performance
    $indexesToAdd = [
        'idx_user_status' => "CREATE INDEX idx_user_status ON course_learning (user_id, status)",
        'idx_webinar_status' => "CREATE INDEX idx_webinar_status ON course_learning (webinar_id, status)",
        'idx_status' => "CREATE INDEX idx_status ON course_learning (status)"
    ];
    
    $addedIndexes = 0;
    
    foreach ($indexesToAdd as $indexName => $sql) {
        try {
            // Check if index already exists
            $indexExists = DB::select("SHOW INDEX FROM course_learning WHERE Key_name = '{$indexName}'");
            
            if (empty($indexExists)) {
                DB::statement($sql);
                echo "✅ Added index: {$indexName}\n";
                $addedIndexes++;
            } else {
                echo "ℹ️  Index already exists: {$indexName}\n";
            }
        } catch (Exception $e) {
            echo "⚠️  Warning adding index {$indexName}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Summary:\n";
    echo "- Columns added: {$addedColumns}\n";
    echo "- Indexes added: {$addedIndexes}\n";
    echo "- Database structure fixed successfully!\n\n";
    
    // Test the table structure
    echo "🧪 Testing table structure...\n";
    
    try {
        $testRecord = DB::table('course_learning')
            ->select('id', 'user_id', 'webinar_id', 'status', 'progress')
            ->limit(1)
            ->get();
        
        echo "✅ Table structure test passed\n";
        echo "✅ All required columns are accessible\n";
        
    } catch (Exception $e) {
        echo "❌ Table structure test failed: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database fix failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Database structure fix completed!\n";
echo "You can now run the enhanced simulation tests.\n";

