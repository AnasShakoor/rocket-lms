<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\CacheForgotten;
use Illuminate\Cache\Events\CacheWritten;

class PerformanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only enable in non-production environments for debugging
        if (config('app.debug') && !app()->environment('production')) {
            $this->enableQueryLogging();
            $this->enableCacheLogging();
        }

        // Enable database query optimization
        $this->optimizeDatabaseQueries();

        // Enable route caching in production
        if (app()->environment('production')) {
            $this->enableRouteCaching();
        }

        // Enable view caching
        $this->enableViewCaching();

        // Enable config caching
        $this->enableConfigCaching();
    }

    /**
     * Enable query logging for debugging
     */
    protected function enableQueryLogging(): void
    {
        DB::listen(function (QueryExecuted $event) {
            if ($event->time > 100) { // Log slow queries (>100ms)
                Log::warning('Slow query detected', [
                    'sql' => $event->sql,
                    'bindings' => $event->bindings,
                    'time' => $event->time,
                ]);
            }
        });
    }

    /**
     * Enable cache logging for debugging
     * Disabled to reduce log noise - uncomment for debugging if needed
     */
    protected function enableCacheLogging(): void
    {
        // Cache logging disabled to reduce log noise
        // Uncomment the following lines if you need to debug cache performance

        /*
        Event::listen(CacheHit::class, function (CacheHit $event) {
            Log::info('Cache hit', ['key' => $event->key, 'tags' => $event->tags]);
        });

        Event::listen(CacheMissed::class, function (CacheMissed $event) {
            Log::info('Cache miss', ['key' => $event->key, 'tags' => $event->tags]);
        });
        */
    }

    /**
     * Optimize database queries
     */
    protected function optimizeDatabaseQueries(): void
    {
        // Check if database optimization is enabled
        if (!config('performance.database.safe_mode', true)) {
            return;
        }

        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql') {
                $this->optimizeMySQL();
            } elseif ($driver === 'pgsql') {
                $this->optimizePostgreSQL();
            } elseif ($driver === 'sqlite') {
                $this->optimizeSQLite();
            }
        } catch (\Exception $e) {
            Log::warning('Database optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Optimize MySQL database
     */
    protected function optimizeMySQL(): void
    {
        try {
            // Only set sql_mode if safe mode is disabled
            if (!config('performance.database.safe_mode', true)) {
                DB::statement('SET SESSION sql_mode = ""');
            }

            // Check if query cache is enabled in config
            if (config('performance.database.query_cache.enabled', false)) {
                $mysqlVersion = $this->getMySQLVersion();

                if ($this->isMySQLVersionLessThan($mysqlVersion, '8.0')) {
                    // MySQL 5.7 and below support query cache
                    try {
                        DB::statement('SET SESSION query_cache_type = 1');
                        DB::statement('SET SESSION query_cache_size = 67108864'); // 64MB
                    } catch (\Exception $e) {
                        // MySQL query cache not available - silent fail
                    }
                } else {
                    // MySQL 8.0+ - query cache was removed, use other optimizations
                    try {
                        // Set other performance variables (if supported)
                        $this->setMySQLVariable('innodb_buffer_pool_size', '134217728'); // 128MB
                        $this->setMySQLVariable('innodb_log_file_size', '67108864'); // 64MB
                        $this->setMySQLVariable('innodb_flush_log_at_trx_commit', '2');
                    } catch (\Exception $e) {
                        // MySQL 8.0+ optimization failed - silent fail
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('MySQL optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Optimize PostgreSQL database
     */
    protected function optimizePostgreSQL(): void
    {
        try {
            // PostgreSQL specific optimizations
            DB::statement('SET work_mem = \'64MB\'');
            DB::statement('SET shared_buffers = \'128MB\'');
            DB::statement('SET effective_cache_size = \'256MB\'');
        } catch (\Exception $e) {
            // PostgreSQL optimization failed - silent fail
        }
    }

    /**
     * Optimize SQLite database
     */
    protected function optimizeSQLite(): void
    {
        try {
            // SQLite specific optimizations
            DB::statement('PRAGMA journal_mode = WAL');
            DB::statement('PRAGMA synchronous = NORMAL');
            DB::statement('PRAGMA cache_size = 10000');
            DB::statement('PRAGMA temp_store = MEMORY');
        } catch (\Exception $e) {
            // SQLite optimization failed - silent fail
        }
    }

    /**
     * Safely set MySQL variable
     */
    protected function setMySQLVariable(string $variable, string $value): void
    {
        try {
            DB::statement("SET SESSION {$variable} = {$value}");
        } catch (\Exception $e) {
            // Failed to set MySQL variable - silent fail
        }
    }

    /**
     * Get MySQL version
     */
    protected function getMySQLVersion(): string
    {
        try {
            $result = DB::select('SELECT VERSION() as version');
            return $result[0]->version ?? '0.0.0';
        } catch (\Exception $e) {
            return '0.0.0';
        }
    }

    /**
     * Check if MySQL version is less than specified version
     */
    protected function isMySQLVersionLessThan(string $currentVersion, string $compareVersion): bool
    {
        $current = $this->parseVersion($currentVersion);
        $compare = $this->parseVersion($compareVersion);

        for ($i = 0; $i < 3; $i++) {
            if ($current[$i] < $compare[$i]) {
                return true;
            } elseif ($current[$i] > $compare[$i]) {
                return false;
            }
        }

        return false;
    }

    /**
     * Parse version string to array
     */
    protected function parseVersion(string $version): array
    {
        $parts = explode('.', $version);
        return [
            (int) ($parts[0] ?? 0),
            (int) ($parts[1] ?? 0),
            (int) ($parts[2] ?? 0)
        ];
    }

    /**
     * Enable route caching
     */
    protected function enableRouteCaching(): void
    {
        if (!file_exists(base_path('bootstrap/cache/routes.php'))) {
            $this->app['artisan']->call('route:cache');
        }
    }

    /**
     * Enable view caching
     */
    protected function enableViewCaching(): void
    {
        if (app()->environment('production')) {
            $this->app['artisan']->call('view:cache');
        }
    }

    /**
     * Enable config caching
     */
    protected function enableConfigCaching(): void
    {
        if (app()->environment('production')) {
            $this->app['artisan']->call('config:cache');
        }
    }
}
