<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;

class PerformanceMonitorService
{
    /**
     * Performance metrics storage
     */
    protected array $metrics = [];

    /**
     * Start monitoring
     */
    public function startMonitoring(): void
    {
        $this->metrics['start_time'] = microtime(true);
        $this->metrics['start_memory'] = memory_get_usage();
        $this->metrics['start_peak_memory'] = memory_get_peak_usage();

        // Listen to database queries
        $this->listenToDatabaseQueries();
        
        // Listen to cache events
        $this->listenToCacheEvents();
    }

    /**
     * Stop monitoring and get results
     */
    public function stopMonitoring(): array
    {
        $this->metrics['end_time'] = microtime(true);
        $this->metrics['end_memory'] = memory_get_usage();
        $this->metrics['end_peak_memory'] = memory_get_peak_usage();

        $this->metrics['execution_time'] = $this->metrics['end_time'] - $this->metrics['start_time'];
        $this->metrics['memory_usage'] = $this->metrics['end_memory'] - $this->metrics['start_memory'];
        $this->metrics['peak_memory'] = $this->metrics['end_peak_memory'];

        return $this->metrics;
    }

    /**
     * Listen to database queries
     */
    protected function listenToDatabaseQueries(): void
    {
        $this->metrics['queries'] = [];
        $this->metrics['slow_queries'] = [];
        $this->metrics['total_query_time'] = 0;

        DB::listen(function (QueryExecuted $event) {
            $queryData = [
                'sql' => $event->sql,
                'bindings' => $event->bindings,
                'time' => $event->time,
                'connection' => $event->connectionName,
            ];

            $this->metrics['queries'][] = $queryData;
            $this->metrics['total_query_time'] += $event->time;

            // Log slow queries
            if ($event->time > 100) { // >100ms
                $this->metrics['slow_queries'][] = $queryData;
                
                if (config('app.debug')) {
                    Log::warning('Slow query detected', $queryData);
                }
            }
        });
    }

    /**
     * Listen to cache events
     */
    protected function listenToCacheEvents(): void
    {
        $this->metrics['cache_hits'] = 0;
        $this->metrics['cache_misses'] = 0;

        Event::listen(CacheHit::class, function (CacheHit $event) {
            $this->metrics['cache_hits']++;
        });

        Event::listen(CacheMissed::class, function (CacheMissed $event) {
            $this->metrics['cache_misses']++;
        });
    }

    /**
     * Get performance report
     */
    public function getPerformanceReport(): array
    {
        $report = [
            'execution_time' => round($this->metrics['execution_time'] * 1000, 2) . 'ms',
            'memory_usage' => $this->formatBytes($this->metrics['memory_usage']),
            'peak_memory' => $this->formatBytes($this->metrics['peak_memory']),
            'database' => [
                'total_queries' => count($this->metrics['queries'] ?? []),
                'total_query_time' => round($this->metrics['total_query_time'] ?? 0, 2) . 'ms',
                'slow_queries' => count($this->metrics['slow_queries'] ?? []),
                'average_query_time' => $this->getAverageQueryTime(),
            ],
            'cache' => [
                'hits' => $this->metrics['cache_hits'] ?? 0,
                'misses' => $this->metrics['cache_misses'] ?? 0,
                'hit_rate' => $this->getCacheHitRate(),
            ],
            'recommendations' => $this->getRecommendations(),
        ];

        return $report;
    }

    /**
     * Get average query time
     */
    protected function getAverageQueryTime(): float
    {
        $queries = $this->metrics['queries'] ?? [];
        if (empty($queries)) {
            return 0;
        }

        $totalTime = array_sum(array_column($queries, 'time'));
        return round($totalTime / count($queries), 2);
    }

    /**
     * Get cache hit rate
     */
    protected function getCacheHitRate(): float
    {
        $hits = $this->metrics['cache_hits'] ?? 0;
        $misses = $this->metrics['cache_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return 0;
        }

        return round(($hits / $total) * 100, 2);
    }

    /**
     * Get performance recommendations
     */
    protected function getRecommendations(): array
    {
        $recommendations = [];

        // Database recommendations
        if (($this->metrics['slow_queries'] ?? []) > 0) {
            $recommendations[] = 'Consider adding database indexes for slow queries';
        }

        if (($this->metrics['total_query_time'] ?? 0) > 500) {
            $recommendations[] = 'Database queries are taking too long, consider query optimization';
        }

        // Cache recommendations
        $hitRate = $this->getCacheHitRate();
        if ($hitRate < 80) {
            $recommendations[] = 'Cache hit rate is low, consider implementing more caching strategies';
        }

        // Memory recommendations
        $peakMemory = $this->metrics['peak_memory'] ?? 0;
        if ($peakMemory > 128 * 1024 * 1024) { // 128MB
            $recommendations[] = 'High memory usage detected, consider optimizing memory-intensive operations';
        }

        // Execution time recommendations
        $executionTime = $this->metrics['execution_time'] ?? 0;
        if ($executionTime > 1.0) {
            $recommendations[] = 'Request execution time is high, consider implementing caching or optimization';
        }

        return $recommendations;
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

    /**
     * Check if performance is acceptable
     */
    public function isPerformanceAcceptable(): bool
    {
        $executionTime = $this->metrics['execution_time'] ?? 0;
        $peakMemory = $this->metrics['peak_memory'] ?? 0;
        $slowQueries = count($this->metrics['slow_queries'] ?? []);

        return $executionTime < 1.0 && 
               $peakMemory < 128 * 1024 * 1024 && 
               $slowQueries === 0;
    }

    /**
     * Store performance metrics for historical analysis
     */
    public function storeMetrics(): void
    {
        $report = $this->getPerformanceReport();
        $timestamp = now()->timestamp;

        Cache::put("performance_metrics_{$timestamp}", $report, 86400); // Store for 24 hours
    }
}
