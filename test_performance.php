<?php

/**
 * Performance Test Script
 * 
 * This script tests the performance optimizations implemented
 * Run this from your project root: php test_performance.php
 */

require_once 'vendor/autoload.php';

use App\Services\PerformanceOptimizationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

echo "🚀 Performance Optimization Test\n";
echo "================================\n\n";

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "1. Testing Performance Service...\n";
    $service = new PerformanceOptimizationService();
    
    // Test performance metrics
    $metrics = $service->getPerformanceMetrics();
    echo "   ✅ Memory Usage: " . formatBytes($metrics['memory_usage']) . "\n";
    echo "   ✅ Peak Memory: " . formatBytes($metrics['peak_memory']) . "\n";
    echo "   ✅ Cache Hits: " . $metrics['cache_hits'] . "\n";
    echo "   ✅ Cache Misses: " . $metrics['cache_misses'] . "\n";
    
    echo "\n2. Testing Database Connection...\n";
    $connection = DB::connection();
    echo "   ✅ Database: " . $connection->getDatabaseName() . "\n";
    echo "   ✅ Driver: " . $connection->getDriverName() . "\n";
    echo "   ✅ Status: " . ($connection->getPdo() ? 'Connected' : 'Disconnected') . "\n";
    
    echo "\n3. Testing Cache System...\n";
    $cacheDriver = config('cache.default');
    echo "   ✅ Cache Driver: " . $cacheDriver . "\n";
    
    // Test cache functionality
    $testKey = 'performance_test_' . time();
    $testValue = 'test_value_' . rand(1000, 9999);
    
    Cache::put($testKey, $testValue, 60);
    $retrieved = Cache::get($testKey);
    
    if ($retrieved === $testValue) {
        echo "   ✅ Cache Read/Write: Working\n";
    } else {
        echo "   ❌ Cache Read/Write: Failed\n";
    }
    
    // Clean up test cache
    Cache::forget($testKey);
    
    echo "\n4. Testing Configuration...\n";
    $performanceConfig = config('performance');
    if ($performanceConfig) {
        echo "   ✅ Performance Config: Loaded\n";
        echo "   ✅ Cache TTL: " . ($performanceConfig['cache']['default_ttl'] ?? 'Not set') . " seconds\n";
        echo "   ✅ Database Timeout: " . ($performanceConfig['database']['query_timeout'] ?? 'Not set') . " seconds\n";
    } else {
        echo "   ❌ Performance Config: Not found\n";
    }
    
    echo "\n5. Testing User Model Optimization...\n";
    try {
        $user = new \App\User();
        if (method_exists($user, 'getCacheKeyPrefix')) {
            echo "   ✅ OptimizedQueries Trait: Loaded\n";
        } else {
            echo "   ❌ OptimizedQueries Trait: Not loaded\n";
        }
    } catch (Exception $e) {
        echo "   ❌ User Model Test: " . $e->getMessage() . "\n";
    }
    
    echo "\n6. Performance Recommendations...\n";
    echo "   📊 Run full optimization: php artisan app:optimize-performance --force\n";
    echo "   📊 Access dashboard: /admin/performance\n";
    echo "   📊 Monitor logs: tail -f storage/logs/laravel.log\n";
    
    if (config('cache.default') === 'file') {
        echo "   ⚠️  Consider switching to Redis for better performance\n";
    }
    
    if (config('app.debug')) {
        echo "   ⚠️  Debug mode is enabled - disable in production\n";
    }
    
    echo "\n✅ Performance test completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Performance test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

echo "\n";
