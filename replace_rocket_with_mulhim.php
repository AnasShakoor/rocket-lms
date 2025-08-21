<?php

echo "🚀 Replacing MULHIM with MULHIM\n";
echo "====================================\n\n";

// Define the directory to process
$projectDir = __DIR__;

// Define patterns to replace
$replacements = [
    'MULHIM' => 'MULHIM',
    'MULHIM' => 'MULHIM',
    'mulhim' => 'mulhim',
    'MULHIM' => 'MULHIM',
    'MULHIM' => 'MULHIM',
    'MULHIM V2.0' => 'MULHIM V2.0',
    'MULHIM Version : 2.0' => 'MULHIM Version : 2.0',
    'MULHIM Platform' => 'MULHIM Platform',
    'MULHIM Theme and Landing Builder' => 'MULHIM Theme and Landing Builder',
    'MULHIM Theme Builder' => 'MULHIM Theme Builder',
    'MULHIM Plugins Bundle' => 'MULHIM Plugins Bundle',
    'MULHIM Mobile App' => 'MULHIM Mobile App',
    'MULHIM Team' => 'MULHIM Team',
    'MULHIM Theme & Landing Builder' => 'MULHIM Theme & Landing Builder',
    'MULHIM Theme and Landing Page Builder' => 'MULHIM Theme and Landing Page Builder',
    'MULHIM Learning Management Academy Script' => 'MULHIM Learning Management Academy Script',
    'MULHIM Mobile App Android iOS' => 'MULHIM Mobile App Android iOS',
    'Universal Plugins Bundle for MULHIM' => 'Universal Plugins Bundle for MULHIM',
    'MULHIM Theme and Landing Page Builder' => 'MULHIM Theme and Landing Page Builder',
    'Universal Plugins Bundle for MULHIM' => 'Universal Plugins Bundle for MULHIM'
];

// Directories to exclude (vendor files, etc.)
$excludeDirs = [
    'vendor',
    'node_modules',
    'public/assets/vendors/fontawesome',
    'public/assets/vendors',
    '.git',
    'storage',
    'bootstrap/cache'
];

// File extensions to process
$includeExtensions = [
    'php', 'blade.php', 'js', 'css', 'html', 'md', 'txt', 'json', 'yml', 'yaml', 'xml', 'sql'
];

// File extensions to exclude
$excludeExtensions = [
    'min.js', 'min.css', 'woff', 'woff2', 'ttf', 'eot', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'ico'
];

// Function to check if file should be processed
function shouldProcessFile($filePath, $excludeDirs, $includeExtensions, $excludeExtensions) {
    // Check if file is in excluded directory
    foreach ($excludeDirs as $excludeDir) {
        if (strpos($filePath, $excludeDir) !== false) {
            return false;
        }
    }
    
    // Check file extension
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    
    // Check if extension should be included
    if (!in_array($extension, $includeExtensions)) {
        return false;
    }
    
    // Check if extension should be excluded
    foreach ($excludeExtensions as $excludeExt) {
        if (strpos($extension, $excludeExt) !== false) {
            return false;
        }
    }
    
    return true;
}

// Function to process a single file
function processFile($filePath, $replacements) {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $modified = false;
    
    foreach ($replacements as $search => $replace) {
        if (strpos($content, $search) !== false) {
            $content = str_replace($search, $replace, $content);
            $modified = true;
        }
    }
    
    if ($modified) {
        if (file_put_contents($filePath, $content)) {
            return true;
        } else {
            return false;
        }
    }
    
    return null; // No changes needed
}

// Function to recursively process directories
function processDirectory($dir, $excludeDirs, $includeExtensions, $excludeExtensions, $replacements, &$stats, $projectDir) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filePath = $dir . '/' . $file;
        
        if (is_dir($filePath)) {
            // Skip excluded directories
            $shouldSkip = false;
            foreach ($excludeDirs as $excludeDir) {
                if (strpos($filePath, $excludeDir) !== false) {
                    $shouldSkip = true;
                    break;
                }
            }
            
            if (!$shouldSkip) {
                processDirectory($filePath, $excludeDirs, $includeExtensions, $excludeExtensions, $replacements, $stats, $projectDir);
            }
        } elseif (is_file($filePath)) {
            if (shouldProcessFile($filePath, $excludeDirs, $includeExtensions, $excludeExtensions)) {
                $result = processFile($filePath, $replacements);
                
                if ($result === true) {
                    $stats['modified']++;
                    echo "✅ Modified: " . str_replace($projectDir . '/', '', $filePath) . "\n";
                } elseif ($result === false) {
                    $stats['errors']++;
                    echo "❌ Error: " . str_replace($projectDir . '/', '', $filePath) . "\n";
                }
                
                $stats['processed']++;
            }
        }
    }
}

// Initialize statistics
$stats = [
    'processed' => 0,
    'modified' => 0,
    'errors' => 0
];

echo "🔍 Starting replacement process...\n";
echo "📁 Project directory: {$projectDir}\n\n";

// Process the project directory
processDirectory($projectDir, $excludeDirs, $includeExtensions, $excludeExtensions, $replacements, $stats, $projectDir);

echo "\n📊 Replacement Statistics:\n";
echo "   📁 Files processed: {$stats['processed']}\n";
echo "   ✅ Files modified: {$stats['modified']}\n";
echo "   ❌ Errors: {$stats['errors']}\n";

// Now let's also update the database name in test files
echo "\n🔧 Updating database references...\n";

$testFiles = [
    'test_simple.php',
    'test_database_functionality.php',
    'fix_webinar_id_column.php',
    'fix_database_direct.php'
];

foreach ($testFiles as $testFile) {
    if (file_exists($testFile)) {
        $content = file_get_contents($testFile);
        if (strpos($content, 'mulhim') !== false) {
            $content = str_replace('mulhim', 'mulhim', $content);
            if (file_put_contents($testFile, $content)) {
                echo "✅ Updated database name in: {$testFile}\n";
            } else {
                echo "❌ Failed to update: {$testFile}\n";
            }
        }
    }
}

// Update package.json if it exists
if (file_exists('package.json')) {
    $packageJson = file_get_contents('package.json');
    if (strpos($packageJson, 'mulhim') !== false) {
        $packageJson = str_replace('mulhim', 'mulhim', $packageJson);
        if (file_put_contents('package.json', $packageJson)) {
            echo "✅ Updated package.json\n";
        } else {
            echo "❌ Failed to update package.json\n";
        }
    }
}

// Update README.md if it exists
if (file_exists('README.md')) {
    $readme = file_get_contents('README.md');
    if (strpos($readme, 'MULHIM') !== false) {
        $readme = str_replace('MULHIM', 'MULHIM', $readme);
        if (file_put_contents('README.md', $readme)) {
            echo "✅ Updated README.md\n";
        } else {
            echo "❌ Failed to update README.md\n";
        }
    }
}

echo "\n🎉 Replacement process completed!\n";
echo "\n📋 Summary of changes:\n";
echo "   - All 'MULHIM' references → 'MULHIM'\n";
echo "   - All 'MULHIM' references → 'MULHIM'\n";
echo "   - All 'mulhim' references → 'mulhim'\n";
echo "   - Database name in test files → 'mulhim'\n";
echo "   - Package name → 'mulhim'\n";
echo "\n⚠️  Important Notes:\n";
echo "   - FontAwesome and vendor files were excluded to prevent breaking functionality\n";
echo "   - You may need to manually update your database name if you want to change it\n";
echo "   - Update your .env file database name if needed\n";
echo "   - Clear any caches after making these changes\n";
echo "\n🚀 Your project is now branded as MULHIM!\n";
