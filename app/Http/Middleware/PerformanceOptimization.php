<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class PerformanceOptimization
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Add performance headers
        $response = $next($request);
        
        // Add performance optimization headers
        $response = $this->addPerformanceHeaders($response);
        
        // Compress response if possible
        $response = $this->compressResponse($response);
        
        // Log performance metrics
        $this->logPerformanceMetrics($request, $startTime, $startMemory);
        
        return $response;
    }

    /**
     * Add performance optimization headers
     */
    protected function addPerformanceHeaders($response)
    {
        // Cache control headers
        $response->headers->set('Cache-Control', 'public, max-age=3600, s-maxage=3600');
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
        
        // Performance headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Remove unnecessary headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');
        
        return $response;
    }

    /**
     * Compress response if possible
     */
    protected function compressResponse($response)
    {
        $content = $response->getContent();
        
        // Only compress text-based content
        $contentTypes = ['text/html', 'text/plain', 'text/css', 'application/javascript', 'application/json'];
        $contentType = $response->headers->get('Content-Type');
        
        if ($contentType && in_array(explode(';', $contentType)[0], $contentTypes)) {
            // Check if gzip is supported
            if (extension_loaded('zlib') && !empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
                if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
                    $compressed = gzencode($content, 6);
                    if ($compressed !== false) {
                        $response->setContent($compressed);
                        $response->headers->set('Content-Encoding', 'gzip');
                        $response->headers->set('Content-Length', strlen($compressed));
                    }
                }
            }
        }
        
        return $response;
    }

    /**
     * Log performance metrics
     */
    protected function logPerformanceMetrics(Request $request, float $startTime, int $startMemory): void
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = $endMemory - $startMemory;
        $peakMemory = memory_get_peak_usage();
        
        // Log slow requests
        $slowThreshold = config('performance.monitoring.slow_request_threshold', 1000);
        if ($executionTime > $slowThreshold) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => round($executionTime, 2) . 'ms',
                'memory_used' => $this->formatBytes($memoryUsed),
                'peak_memory' => $this->formatBytes($peakMemory),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);
        }
        
        // Store performance metrics in cache for monitoring
        $this->storePerformanceMetrics($request, $executionTime, $memoryUsed, $peakMemory);
    }

    /**
     * Store performance metrics for monitoring
     */
    protected function storePerformanceMetrics(Request $request, float $executionTime, int $memoryUsed, int $peakMemory): void
    {
        $metrics = [
            'url' => $request->path(),
            'method' => $request->method(),
            'execution_time' => $executionTime,
            'memory_used' => $memoryUsed,
            'peak_memory' => $peakMemory,
            'timestamp' => time(),
        ];
        
        $cacheKey = 'performance_metrics_' . date('Y-m-d');
        $existingMetrics = Cache::get($cacheKey, []);
        $existingMetrics[] = $metrics;
        
        // Keep only last 1000 metrics per day
        if (count($existingMetrics) > 1000) {
            $existingMetrics = array_slice($existingMetrics, -1000);
        }
        
        Cache::put($cacheKey, $existingMetrics, 86400); // 24 hours
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
