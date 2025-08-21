<?php

// Fix webinar_id column issue
// This script checks the actual column structure and fixes the webinar_id reference

echo "🔧 Fixing Webinar ID Column Issue\n";
echo "==================================\n\n";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Enter your MySQL password here
$database = 'mulhim';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database: {$database}\n\n";
    
    // Check current table structure
    echo "📋 Current course_learning table structure:\n";
    $stmt = $pdo->query("DESCRIBE course_learning");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']}\n";
    }
    echo "\n";
    
    // Check if we need to add webinar_id column
    $stmt = $pdo->prepare("SHOW COLUMNS FROM course_learning LIKE 'webinar_id'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "⚠️  webinar_id column not found. Adding it...\n";
        
        // Add webinar_id column
        try {
            $pdo->exec("ALTER TABLE course_learning ADD COLUMN webinar_id INT UNSIGNED NULL AFTER user_id");
            echo "✅ Added webinar_id column\n";
            
            // Add index for webinar_id
            $pdo->exec("CREATE INDEX idx_webinar_status ON course_learning (webinar_id, status)");
            echo "✅ Added webinar_id index\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to add webinar_id column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✅ webinar_id column already exists\n";
    }
    
    // Check if we need to add bundle_id column for bundle support
    $stmt = $pdo->prepare("SHOW COLUMNS FROM course_learning LIKE 'bundle_id'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "⚠️  bundle_id column not found. Adding it...\n";
        
        try {
            $pdo->exec("ALTER TABLE course_learning ADD COLUMN bundle_id INT UNSIGNED NULL AFTER webinar_id");
            echo "✅ Added bundle_id column\n";
            
            // Add index for bundle_id
            $pdo->exec("CREATE INDEX idx_bundle_status ON course_learning (bundle_id, status)");
            echo "✅ Added bundle_id index\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to add bundle_id column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✅ bundle_id column already exists\n";
    }
    
    // Update existing records to link with webinars if possible
    echo "\n🔄 Updating existing records...\n";
    
    try {
        // Check if there are any existing records that need linking
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM course_learning");
        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "📊 Total records in course_learning: {$totalRecords}\n";
        
        if ($totalRecords > 0) {
            // Try to link existing records with webinars based on session_id
            $stmt = $pdo->query("
                UPDATE course_learning cl 
                INNER JOIN sessions s ON cl.session_id = s.id 
                SET cl.webinar_id = s.webinar_id 
                WHERE cl.webinar_id IS NULL AND s.webinar_id IS NOT NULL
            ");
            $linkedRecords = $stmt->rowCount();
            echo "✅ Linked {$linkedRecords} records with webinars via sessions\n";
        }
        
    } catch (Exception $e) {
        echo "⚠️  Warning updating records: " . $e->getMessage() . "\n";
    }
    
    // Final table structure check
    echo "\n📋 Final table structure:\n";
    $stmt = $pdo->query("DESCRIBE course_learning");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']}\n";
    }
    
    // Test the table structure
    echo "\n🧪 Testing table structure...\n";
    
    try {
        $stmt = $pdo->query("SELECT id, user_id, webinar_id, bundle_id, status, progress FROM course_learning LIMIT 1");
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
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Webinar ID column fix completed!\n";
echo "The course_learning table now has all required columns for the enhanced simulation system.\n";

