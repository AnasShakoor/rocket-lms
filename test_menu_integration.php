<?php

echo "🧪 Testing Admin Menu Integration\n";
echo "=================================\n\n";

// Test if the AdminMenuServiceProvider is working
echo "1. Testing AdminMenuServiceProvider registration...\n";

// Check if the provider is registered in config
$configPath = 'config/app.php';
if (file_exists($configPath)) {
    $configContent = file_get_contents($configPath);
    if (strpos($configContent, 'App\\Providers\\AdminMenuServiceProvider::class') !== false) {
        echo "   ✅ AdminMenuServiceProvider is registered in config/app.php\n";
    } else {
        echo "   ❌ AdminMenuServiceProvider is NOT registered in config/app.php\n";
    }
} else {
    echo "   ❌ config/app.php not found\n";
}

// Test if the sidebar files have been updated
echo "\n2. Testing sidebar file updates...\n";

$sidebarFiles = [
    'resources/views/admin/includes/sidebar/education.blade.php',
    'resources/views/admin/includes/sidebar/financial.blade.php',
    'resources/views/admin/includes/sidebar/marketing.blade.php'
];

foreach ($sidebarFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'LMS Operational') !== false || 
            strpos($content, 'Enhanced Reports') !== false || 
            strpos($content, 'Email Automation') !== false) {
            echo "   ✅ {$file} has been updated with new menu items\n";
        } else {
            echo "   ⚠️  {$file} exists but may not have new menu items\n";
        }
    } else {
        echo "   ❌ {$file} not found\n";
    }
}

// Test if the custom admin routes are accessible
echo "\n3. Testing custom admin routes...\n";

$routeFiles = [
    'routes/custom_admin.php'
];

foreach ($routeFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'admin.simulation.index') !== false &&
            strpos($content, 'admin.bnpl-providers.index') !== false &&
            strpos($content, 'admin.enhanced-reports.index') !== false) {
            echo "   ✅ {$file} contains all required admin routes\n";
        } else {
            echo "   ⚠️  {$file} exists but may be missing some routes\n";
        }
    } else {
        echo "   ❌ {$file} not found\n";
    }
}

// Test if the controllers exist
echo "\n4. Testing controller files...\n";

$controllers = [
    'app/Http/Controllers/Admin/SimulationController.php',
    'app/Http/Controllers/Admin/BnplProviderController.php',
    'app/Http/Controllers/Admin/EnhancedReportController.php'
];

foreach ($controllers as $controller) {
    if (file_exists($controller)) {
        echo "   ✅ {$controller} exists\n";
    } else {
        echo "   ❌ {$controller} not found\n";
    }
}

// Test if the models exist
echo "\n5. Testing model files...\n";

$models = [
    'app/Models/SimulationRule.php',
    'app/Models/SimulationLog.php',
    'app/Models/BnplProvider.php',
    'app/Models/CourseLearning.php',
    'app/Models/Sale.php'
];

foreach ($models as $model) {
    if (file_exists($model)) {
        echo "   ✅ {$model} exists\n";
    } else {
        echo "   ❌ {$model} not found\n";
    }
}

echo "\n🎉 Admin Menu Integration Test Complete!\n";
echo "\n📋 Summary of what should be visible in admin sidebar:\n";
echo "   - Education → LMS Operational → Simulation\n";
echo "   - Education → LMS Operational → BNPL Providers\n";
echo "   - Financial → Enhanced Reports → Reports List\n";
echo "   - Financial → Enhanced Reports → Charts & Analytics\n";
echo "   - Marketing → Email Automation → CME Emails\n";
echo "   - Marketing → Email Automation → Email Analytics\n";
echo "\n🌐 Access your admin panel at: http://127.0.0.1:8000/admin/\n";
echo "   Make sure you're logged in as an admin user to see the menu items.\n";

