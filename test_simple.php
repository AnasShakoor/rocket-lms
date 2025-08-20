<?php

echo "🧪 Simple Database Test\n";
echo "=======================\n\n";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'mulhim';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database: {$database}\n\n";
    
    // Test course_learning table
    echo "1. Testing course_learning table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM course_learning");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Found {$count} records\n";
    
    // Test simulation_rules table
    echo "\n2. Testing simulation_rules table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM simulation_rules");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Found {$count} simulation rules\n";
    
    // Test bnpl_providers table
    echo "\n3. Testing bnpl_providers table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bnpl_providers");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Found {$count} BNPL providers\n";
    
    // Test sales table
    echo "\n4. Testing sales table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sales");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Found {$count} sales records\n";
    
    echo "\n🎉 All database tests passed!\n";
    echo "The enhanced simulation system is ready.\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}

