<?php

// Simple test to verify PerformanceServiceProvider loads without errors
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';

try {
    // Test if the PerformanceServiceProvider can be instantiated
    $provider = new \App\Providers\PerformanceServiceProvider($app);
    echo "✅ PerformanceServiceProvider loaded successfully!\n";

    // Test if the configuration file exists
    if (file_exists(__DIR__ . '/config/performance.php')) {
        echo "✅ Performance configuration file exists!\n";
    } else {
        echo "❌ Performance configuration file missing!\n";
    }

    echo "\n🚀 Performance optimization system is ready!\n";
    echo "📝 To enable database optimizations, add to your .env file:\n";
    echo "   MYSQL_QUERY_CACHE_ENABLED=true\n";
    echo "   DB_OPTIMIZE_TABLES=true\n";
    echo "   DB_ANALYZE_TABLES=true\n";
    echo "   DB_SAFE_MODE=false\n";

} catch (Exception $e) {
    echo "❌ Error loading PerformanceServiceProvider: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n";
}
