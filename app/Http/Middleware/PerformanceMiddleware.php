<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

class PerformanceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response)  $next
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Add performance headers
        $response = $next($request);

        // Calculate performance metrics
        $executionTime = microtime(true) - $startTime;
        $memoryUsage = memory_get_usage() - $startMemory;

        // Add performance headers
        $response->headers->set('X-Execution-Time', round($executionTime * 1000, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));

        // Log slow requests in development
        if (config('app.debug') && $executionTime > 1.0) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage,
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Cache static responses for better performance
        if ($this->shouldCacheResponse($request, $response)) {
            $this->cacheResponse($request, $response);
        }

        return $response;
    }

    /**
     * Check if response should be cached
     */
    protected function shouldCacheResponse(Request $request, Response $response): bool
    {
        // Only cache successful GET requests
        if (!$request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }

        // Don't cache authenticated requests
        if (auth()->check()) {
            return false;
        }

        // Don't cache requests with query parameters (dynamic content)
        if ($request->getQueryString()) {
            return false;
        }

        // Don't cache admin routes
        if ($request->is('admin*') || $request->is('panel*')) {
            return false;
        }

        return true;
    }

    /**
     * Cache the response
     */
    protected function cacheResponse(Request $request, Response $response): void
    {
        $cacheKey = 'response_' . md5($request->fullUrl());
        $cacheData = [
            'content' => $response->getContent(),
            'headers' => $response->headers->all(),
            'status' => $response->getStatusCode(),
        ];

        // Cache for 5 minutes
        Cache::put($cacheKey, $cacheData, 300);
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
