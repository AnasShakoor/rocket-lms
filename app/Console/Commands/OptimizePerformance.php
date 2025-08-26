<?php

namespace App\Console\Commands;

use App\Services\PerformanceOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:optimize-performance {--force : Force optimization without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize application performance by clearing caches, optimizing database, and setting optimal configurations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('This will clear all caches and optimize the application. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Starting performance optimization...');
        
        try {
            $service = new PerformanceOptimizationService();
            
            // Run comprehensive optimization
            $results = $service->optimizeApplication();
            
            if ($results['success']) {
                $this->info('✅ ' . $results['message']);
                $this->displayOptimizationResults($results);
            } else {
                $this->error('❌ ' . $results['message']);
                return 1;
            }
            
            // Additional optimizations
            $this->runAdditionalOptimizations();
            
            // Display performance metrics
            $this->displayPerformanceMetrics($service);
            
            $this->info('Performance optimization completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('Performance optimization failed: ' . $e->getMessage());
            Log::error('Performance optimization command failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }

    /**
     * Display optimization results
     */
    protected function displayOptimizationResults(array $results): void
    {
        $this->newLine();
        $this->info('Optimization Results:');
        $this->newLine();
        
        foreach ($results as $key => $result) {
            if ($key === 'success' || $key === 'message') {
                continue;
            }
            
            if (is_array($result)) {
                if (isset($result['success'])) {
                    $icon = $result['success'] ? '✅' : '❌';
                    $this->line("  {$icon} {$result['message']}");
                } else {
                    $this->line("  📊 {$key}: " . json_encode($result));
                }
            } else {
                $this->line("  ✅ {$result}");
            }
        }
    }

    /**
     * Run additional optimizations
     */
    protected function runAdditionalOptimizations(): void
    {
        $this->newLine();
        $this->info('Running additional optimizations...');
        
        // Clear route cache
        $this->info('  🔄 Clearing route cache...');
        Artisan::call('route:clear');
        
        // Clear view cache
        $this->info('  🔄 Clearing view cache...');
        Artisan::call('view:clear');
        
        // Clear config cache
        $this->info('  🔄 Clearing config cache...');
        Artisan::call('config:clear');
        
        // Clear application cache
        $this->info('  🔄 Clearing application cache...');
        Artisan::call('cache:clear');
        
        // Optimize autoloader
        $this->info('  🔄 Optimizing autoloader...');
        $this->optimizeAutoloader();
        
        // Cache routes and config for production
        if (app()->environment('production')) {
            $this->info('  🔄 Caching routes and config for production...');
            Artisan::call('route:cache');
            Artisan::call('config:cache');
        }
        
        $this->info('  ✅ Additional optimizations completed');
    }

    /**
     * Optimize Composer autoloader
     */
    protected function optimizeAutoloader(): void
    {
        try {
            $composerPath = base_path('composer.json');
            if (file_exists($composerPath)) {
                $this->info('    📦 Running composer dump-autoload --optimize...');
                exec('composer dump-autoload --optimize --no-dev', $output, $returnCode);
                
                if ($returnCode === 0) {
                    $this->info('    ✅ Autoloader optimized successfully');
                } else {
                    $this->warn('    ⚠️  Autoloader optimization failed');
                }
            }
        } catch (\Exception $e) {
            $this->warn('    ⚠️  Could not optimize autoloader: ' . $e->getMessage());
        }
    }

    /**
     * Display performance metrics
     */
    protected function displayPerformanceMetrics(PerformanceOptimizationService $service): void
    {
        $this->newLine();
        $this->info('Performance Metrics:');
        $this->newLine();
        
        $metrics = $service->getPerformanceMetrics();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Memory Usage', $this->formatBytes($metrics['memory_usage'])],
                ['Peak Memory', $this->formatBytes($metrics['peak_memory'])],
                ['Cache Hits', $metrics['cache_hits']],
                ['Cache Misses', $metrics['cache_misses']],
                ['DB Connections', $metrics['database_connections']],
            ]
        );
        
        // Display cache statistics
        $this->displayCacheStatistics();
        
        // Display database statistics
        $this->displayDatabaseStatistics();
    }

    /**
     * Display cache statistics
     */
    protected function displayCacheStatistics(): void
    {
        $this->newLine();
        $this->info('Cache Statistics:');
        
        try {
            $cacheStats = [
                'Total Keys' => Cache::get('cache_stats_total', 0),
                'Hit Rate' => Cache::get('cache_stats_hit_rate', 'N/A'),
                'Memory Usage' => Cache::get('cache_stats_memory', 'N/A'),
            ];
            
            $this->table(
                ['Statistic', 'Value'],
                collect($cacheStats)->map(fn($value, $key) => [$key, $value])->toArray()
            );
        } catch (\Exception $e) {
            $this->warn('Could not retrieve cache statistics');
        }
    }

    /**
     * Display database statistics
     */
    protected function displayDatabaseStatistics(): void
    {
        $this->newLine();
        $this->info('Database Statistics:');
        
        try {
            $dbStats = [
                'Connection Status' => DB::connection()->getPdo() ? 'Connected' : 'Disconnected',
                'Database Name' => DB::connection()->getDatabaseName(),
                'Driver' => DB::connection()->getDriverName(),
            ];
            
            // Get table sizes if possible
            try {
                $tables = DB::select("
                    SELECT 
                        table_name,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
                    FROM information_schema.tables 
                    WHERE table_schema = ? 
                    ORDER BY (data_length + index_length) DESC
                    LIMIT 5
                ", [DB::connection()->getDatabaseName()]);
                
                if (!empty($tables)) {
                    $this->newLine();
                    $this->info('Top 5 Largest Tables:');
                    $this->table(
                        ['Table', 'Size (MB)'],
                        collect($tables)->map(fn($table) => [$table->table_name, $table->{'Size (MB)'}])->toArray()
                    );
                }
            } catch (\Exception $e) {
                // Ignore table size queries if they fail
            }
            
            $this->table(
                ['Statistic', 'Value'],
                collect($dbStats)->map(fn($value, $key) => [$key, $value])->toArray()
            );
            
        } catch (\Exception $e) {
            $this->warn('Could not retrieve database statistics');
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
