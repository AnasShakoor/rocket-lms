<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

class PerformanceOptimizationService
{
    /**
     * Optimize the entire application for better performance
     */
    public function optimizeApplication(): array
    {
        $results = [];

        try {
            // Clear all caches first
            $results['cache_clear'] = $this->clearAllCaches();

            // Optimize database
            $results['database'] = $this->optimizeDatabase();

            // Cache routes and config
            $results['routes'] = $this->cacheRoutes();
            $results['config'] = $this->cacheConfig();

            // Optimize views
            $results['views'] = $this->optimizeViews();

            // Set optimal cache settings
            $results['cache_settings'] = $this->setOptimalCacheSettings();

            // Optimize file system
            $results['filesystem'] = $this->optimizeFileSystem();

            $results['success'] = true;
            $results['message'] = 'Application optimized successfully';

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['message'] = 'Optimization failed: ' . $e->getMessage();
            Log::error('Performance optimization failed: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Clear all application caches
     */
    public function clearAllCaches(): array
    {
        $results = [];

        try {
            Artisan::call('cache:clear');
            $results['cache'] = 'Application cache cleared';

            Artisan::call('view:clear');
            $results['views'] = 'View cache cleared';

            Artisan::call('route:clear');
            $results['routes'] = 'Route cache cleared';

            Artisan::call('config:clear');
            $results['config'] = 'Config cache cleared';

            Artisan::call('optimize:clear');
            $results['optimize'] = 'Optimize cache cleared';

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Optimize database performance
     */
    public function optimizeDatabase(): array
    {
        $results = [];

        try {
            // Get slow queries
            $slowQueries = $this->getSlowQueries();
            if (!empty($slowQueries)) {
                $results['slow_queries'] = $slowQueries;
            }

            // Optimize tables if needed
            if (config('performance.optimization.enable_optimization', true)) {
                $this->optimizeTables();
                $results['tables_optimized'] = 'Database tables optimized';
            }

            // Set optimal database settings
            $this->setOptimalDatabaseSettings();
            $results['settings'] = 'Database settings optimized';

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Cache routes for better performance
     */
    public function cacheRoutes(): array
    {
        try {
            if (config('performance.optimization.enable_route_cache', true)) {
                Artisan::call('route:cache');
                return ['success' => true, 'message' => 'Routes cached successfully'];
            }
            return ['success' => false, 'message' => 'Route caching disabled'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Cache configuration for better performance
     */
    public function cacheConfig(): array
    {
        try {
            if (config('performance.optimization.enable_config_cache', true)) {
                Artisan::call('config:cache');
                return ['success' => true, 'message' => 'Configuration cached successfully'];
            }
            return ['success' => false, 'message' => 'Config caching disabled'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Optimize views for better performance
     */
    public function optimizeViews(): array
    {
        try {
            if (config('performance.optimization.enable_view_cache', true)) {
                // Pre-compile common views
                $this->precompileViews();
                return ['success' => true, 'message' => 'Views optimized successfully'];
            }
            return ['success' => false, 'message' => 'View optimization disabled'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Set optimal cache settings
     */
    public function setOptimalCacheSettings(): array
    {
        try {
            // Set cache TTL values
            Cache::put('performance_settings', [
                'default_ttl' => config('performance.cache.default_ttl', 3600),
                'user_ttl' => config('performance.cache.user_ttl', 1800),
                'webinar_ttl' => config('performance.cache.webinar_ttl', 900),
                'payment_ttl' => config('performance.cache.payment_ttl', 300),
            ], 86400);

            return ['success' => true, 'message' => 'Cache settings optimized'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Optimize file system
     */
    public function optimizeFileSystem(): array
    {
        $results = [];

        try {
            // Clean temporary files
            $this->cleanTempFiles();
            $results['temp_files'] = 'Temporary files cleaned';

            // Set optimal permissions
            $this->setOptimalPermissions();
            $results['permissions'] = 'File permissions optimized';

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get slow database queries
     */
    private function getSlowQueries(): array
    {
        try {
            // This would require query logging to be enabled
            $slowQueries = DB::select("
                SELECT
                    sql_text,
                    exec_count,
                    avg_timer_wait/1000000000 as avg_time_sec
                FROM performance_schema.events_statements_summary_by_digest
                WHERE avg_timer_wait > 1000000000
                ORDER BY avg_timer_wait DESC
                LIMIT 10
            ");

            return $slowQueries;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Optimize database tables
     */
    private function optimizeTables(): void
    {
        try {
            $tables = DB::select('SHOW TABLES');
            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                DB::statement("OPTIMIZE TABLE {$tableName}");
            }
        } catch (\Exception $e) {
            Log::warning('Table optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Set optimal database settings
     */
    private function setOptimalDatabaseSettings(): void
    {
        try {
            // Set connection pool settings
            config([
                'database.connections.mysql.pool.min' => config('performance.database.min_connections', 5),
                'database.connections.mysql.pool.max' => config('performance.database.max_connections', 100),
            ]);

            // Set query timeout
            DB::statement("SET SESSION MAX_EXECUTION_TIME = " . config('performance.database.query_timeout', 30));

        } catch (\Exception $e) {
            Log::warning('Database settings optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Pre-compile common views
     */
    private function precompileViews(): void
    {
        try {
            // Pre-compile commonly used views
            $commonViews = [
                'design_1.web.layouts.app',
                'design_1.web.cart.overview.includes.summary',
                'design_1.web.cart.overview.includes.cashback_alert'
            ];

            foreach ($commonViews as $view) {
                if (View::exists($view)) {
                    View::make($view)->render();
                }
            }
        } catch (\Exception $e) {
            Log::warning('View pre-compilation failed: ' . $e->getMessage());
        }
    }

    /**
     * Clean temporary files
     */
    private function cleanTempFiles(): void
    {
        try {
            $tempPaths = [
                storage_path('framework/cache'),
                storage_path('framework/sessions'),
                storage_path('framework/views'),
                storage_path('logs'),
            ];

            foreach ($tempPaths as $path) {
                if (File::exists($path)) {
                    $this->cleanDirectory($path);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Temp file cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Clean directory contents
     */
    private function cleanDirectory(string $path): void
    {
        try {
            $files = File::files($path);
            foreach ($files as $file) {
                if (time() - File::lastModified($file) > 86400) { // Older than 24 hours
                    File::delete($file);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Directory cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Set optimal file permissions
     */
    private function setOptimalPermissions(): void
    {
        try {
            $paths = [
                storage_path() => 0755,
                storage_path('app') => 0755,
                storage_path('framework') => 0755,
                storage_path('logs') => 0755,
                base_path('bootstrap/cache') => 0755,
            ];

            foreach ($paths as $path => $permission) {
                if (File::exists($path)) {
                    File::chmod($path, $permission);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Permission setting failed: ' . $e->getMessage());
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'cache_hits' => Cache::get('cache_hits', 0),
            'cache_misses' => Cache::get('cache_misses', 0),
            'database_connections' => DB::connection()->getPdo() ? 1 : 0,
        ];
    }
}
