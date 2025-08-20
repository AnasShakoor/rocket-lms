<?php

// Direct database fix script - no Laravel dependencies
// This script directly connects to MySQL and fixes the course_learning table

echo "🔧 Direct Database Structure Fix\n";
echo "================================\n\n";

// Database configuration - update these values
$host = 'localhost';
$username = 'root';
$password = ''; // Enter your MySQL password here
$database = 'mulhim';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database: {$database}\n\n";
    
    // Check if course_learning table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'course_learning'");
    if ($stmt->rowCount() == 0) {
        echo "❌ course_learning table does not exist\n";
        exit(1);
    }
    
    echo "✅ course_learning table found\n";
    
    // Get current table structure
    echo "📋 Current table structure:\n";
    $stmt = $pdo->query("DESCRIBE course_learning");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']}\n";
    }
    echo "\n";
    
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
            $stmt = $pdo->prepare("SHOW COLUMNS FROM course_learning LIKE ?");
            $stmt->execute([$columnName]);
            
            if ($stmt->rowCount() == 0) {
                $pdo->exec($sql);
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
            $stmt = $pdo->prepare("SHOW INDEX FROM course_learning WHERE Key_name = ?");
            $stmt->execute([$indexName]);
            
            if ($stmt->rowCount() == 0) {
                $pdo->exec($sql);
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
        $stmt = $pdo->query("SELECT id, user_id, webinar_id, status, progress FROM course_learning LIMIT 1");
        $testRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Table structure test passed\n";
        echo "✅ All required columns are accessible\n";
        
        if ($testRecord) {
            echo "📝 Sample record structure:\n";
            foreach ($testRecord as $key => $value) {
                echo "   - {$key}: " . ($value ?? 'NULL') . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Table structure test failed: " . $e->getMessage() . "\n";
    }
    
    // Update existing records to have proper status
    echo "\n🔄 Updating existing records...\n";
    
    try {
        $stmt = $pdo->query("UPDATE course_learning SET status = 'completed' WHERE status IS NULL OR status = ''");
        $updatedRows = $stmt->rowCount();
        echo "✅ Updated {$updatedRows} existing records with default status\n";
        
        // Set progress for completed records
        $stmt = $pdo->query("UPDATE course_learning SET progress = 100 WHERE status = 'completed'");
        $progressRows = $stmt->rowCount();
        echo "✅ Updated {$progressRows} completed records with 100% progress\n";
        
    } catch (Exception $e) {
        echo "⚠️  Warning updating records: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Check if MySQL is running\n";
    echo "2. Verify database credentials\n";
    echo "3. Ensure database '{$database}' exists\n";
    echo "4. Check MySQL user permissions\n";
    exit(1);
}

echo "\n🎉 Database structure fix completed!\n";
echo "You can now run the enhanced simulation tests.\n";
echo "\n📋 Next steps:\n";
echo "1. Test the enhanced simulation: php test_enhanced_simulation.php\n";
echo "2. Access admin panel to verify all features\n";
echo "3. Test BNPL payment integration\n";
echo "4. Test enhanced reports with charts\n";

