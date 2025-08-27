<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PerformanceOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class PerformanceController extends Controller
{
    protected PerformanceOptimizationService $performanceService;

    public function __construct(PerformanceOptimizationService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Display performance dashboard
     */
    public function index()
    {
        $metrics = $this->performanceService->getPerformanceMetrics();
        $cacheStats = $this->getCacheStatistics();
        $databaseStats = $this->getDatabaseStatistics();
        $systemStats = $this->getSystemStatistics();
        
        return view('admin.performance.dashboard', compact(
            'metrics',
            'cacheStats',
            'databaseStats',
            'systemStats'
        ));
    }

    /**
     * Run performance optimization
     */
    public function optimize(Request $request)
    {
        try {
            $results = $this->performanceService->optimizeApplication();
            
            if ($request->expectsJson()) {
                return response()->json($results);
            }
            
            return redirect()->route('admin.performance.index')
                ->with('success', 'Performance optimization completed successfully!');
                
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            
            return redirect()->route('admin.performance.index')
                ->with('error', 'Performance optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear specific cache
     */
    public function clearCache(Request $request, string $type = 'all')
    {
        try {
            switch ($type) {
                case 'application':
                    Artisan::call('cache:clear');
                    $message = 'Application cache cleared successfully';
                    break;
                    
                case 'views':
                    Artisan::call('view:clear');
                    $message = 'View cache cleared successfully';
                    break;
                    
                case 'routes':
                    Artisan::call('route:clear');
                    $message = 'Route cache cleared successfully';
                    break;
                    
                case 'config':
                    Artisan::call('config:clear');
                    $message = 'Configuration cache cleared successfully';
                    break;
                    
                case 'all':
                default:
                    Artisan::call('optimize:clear');
                    $message = 'All caches cleared successfully';
                    break;
            }
            
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            
            return redirect()->route('admin.performance.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            
            return redirect()->route('admin.performance.index')
                ->with('error', 'Cache clearing failed: ' . $e->getMessage());
        }
    }

    /**
     * Get cache statistics
     */
    protected function getCacheStatistics(): array
    {
        try {
            $cacheDriver = config('cache.default');
            $cacheStats = [
                'driver' => $cacheDriver,
                'status' => 'Active',
                'keys_count' => 0,
                'memory_usage' => 'N/A',
                'hit_rate' => 'N/A',
            ];
            
            if ($cacheDriver === 'redis') {
                $redis = Cache::getRedis();
                $info = $redis->info();
                $cacheStats['memory_usage'] = $this->formatBytes($info['used_memory'] ?? 0);
                $cacheStats['keys_count'] = $redis->dbSize();
            } elseif ($cacheDriver === 'memcached') {
                $cacheStats['status'] = 'Active (Stats unavailable)';
            }
            
            return $cacheStats;
        } catch (\Exception $e) {
            return [
                'driver' => config('cache.default'),
                'status' => 'Error: ' . $e->getMessage(),
                'keys_count' => 0,
                'memory_usage' => 'N/A',
                'hit_rate' => 'N/A',
            ];
        }
    }

    /**
     * Get database statistics
     */
    protected function getDatabaseStatistics(): array
    {
        try {
            $connection = DB::connection();
            $dbName = $connection->getDatabaseName();
            
            $stats = [
                'connection' => $connection->getDriverName(),
                'database' => $dbName,
                'status' => $connection->getPdo() ? 'Connected' : 'Disconnected',
                'tables_count' => 0,
                'total_size' => 'N/A',
            ];
            
            if ($connection->getPdo()) {
                // Get table count
                $tables = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
                $stats['tables_count'] = $tables[0]->count ?? 0;
                
                // Get total database size
                $sizeQuery = DB::select("
                    SELECT 
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS total_size_mb
                    FROM information_schema.tables 
                    WHERE table_schema = ?
                ", [$dbName]);
                
                if (!empty($sizeQuery)) {
                    $stats['total_size'] = ($sizeQuery[0]->total_size_mb ?? 0) . ' MB';
                }
            }
            
            return $stats;
        } catch (\Exception $e) {
            return [
                'connection' => 'Unknown',
                'database' => 'Unknown',
                'status' => 'Error: ' . $e->getMessage(),
                'tables_count' => 0,
                'total_size' => 'N/A',
            ];
        }
    }

    /**
     * Get system statistics
     */
    protected function getSystemStatistics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'disk_free_space' => $this->formatBytes(disk_free_space(storage_path())),
            'disk_total_space' => $this->formatBytes(disk_total_space(storage_path())),
        ];
    }

    /**
     * Get performance metrics for charts
     */
    public function getMetrics(Request $request)
    {
        $period = $request->get('period', '24h');
        $metrics = Cache::get('performance_metrics_' . date('Y-m-d'), []);
        
        // Filter by period
        $filteredMetrics = $this->filterMetricsByPeriod($metrics, $period);
        
        return response()->json([
            'labels' => array_column($filteredMetrics, 'timestamp'),
            'execution_times' => array_column($filteredMetrics, 'execution_time'),
            'memory_usage' => array_column($filteredMetrics, 'memory_used'),
        ]);
    }

    /**
     * Filter metrics by time period
     */
    protected function filterMetricsByPeriod(array $metrics, string $period): array
    {
        $now = time();
        $cutoff = match($period) {
            '1h' => $now - 3600,
            '6h' => $now - 21600,
            '24h' => $now - 86400,
            '7d' => $now - 604800,
            default => $now - 86400,
        };
        
        return array_filter($metrics, fn($metric) => $metric['timestamp'] >= $cutoff);
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
