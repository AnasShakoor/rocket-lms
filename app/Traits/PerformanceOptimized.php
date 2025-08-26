<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

trait PerformanceOptimized
{
    /**
     * Boot the trait
     */
    protected static function bootPerformanceOptimized()
    {
        static::saved(function ($model) {
            $model->clearModelCache();
        });

        static::deleted(function ($model) {
            $model->clearModelCache();
        });
    }

    /**
     * Get cached model with automatic cache invalidation
     */
    public static function cached($key, $callback, $ttl = 3600)
    {
        $cacheKey = static::getCacheKey($key);
        
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cached model by ID
     */
    public static function findCached($id, $ttl = 3600)
    {
        return static::cached("model_{$id}", function () use ($id) {
            return static::find($id);
        }, $ttl);
    }

    /**
     * Get cached model with relationships
     */
    public static function withCached($id, $relationships, $ttl = 3600)
    {
        return static::cached("model_{$id}_with_" . implode('_', $relationships), function () use ($id, $relationships) {
            return static::with($relationships)->find($id);
        }, $ttl);
    }

    /**
     * Get cached collection with pagination
     */
    public static function paginateCached($perPage = 15, $ttl = 1800)
    {
        $page = request()->get('page', 1);
        $cacheKey = "paginate_{$perPage}_page_{$page}";
        
        return static::cached($cacheKey, function () use ($perPage) {
            return static::paginate($perPage);
        }, $ttl);
    }

    /**
     * Get cached collection with conditions
     */
    public static function whereCached($conditions, $ttl = 1800)
    {
        $cacheKey = "where_" . md5(serialize($conditions));
        
        return static::cached($cacheKey, function () use ($conditions) {
            return static::where($conditions)->get();
        }, $ttl);
    }

    /**
     * Clear model cache
     */
    public function clearModelCache()
    {
        $modelName = class_basename($this);
        $cachePattern = "{$modelName}_*";
        
        // Clear all cache keys for this model
        $this->clearCacheByPattern($cachePattern);
    }

    /**
     * Clear cache by pattern
     */
    protected function clearCacheByPattern($pattern)
    {
        // This is a simplified approach - in production you might want to use Redis SCAN
        // For file-based caching, we'll clear the entire cache when a model is updated
        if (config('cache.default') === 'file') {
            Cache::flush();
        }
    }

    /**
     * Get cache key for the model
     */
    protected static function getCacheKey($key)
    {
        $modelName = class_basename(static::class);
        return "{$modelName}_{$key}";
    }

    /**
     * Scope for eager loading commonly used relationships
     */
    public function scopeWithCommon($query)
    {
        if (method_exists($this, 'getCommonRelationships')) {
            return $query->with($this->getCommonRelationships());
        }
        
        return $query;
    }

    /**
     * Scope for selecting only necessary columns
     */
    public function scopeSelectNecessary($query)
    {
        if (method_exists($this, 'getNecessaryColumns')) {
            return $query->select($this->getNecessaryColumns());
        }
        
        return $query;
    }

    /**
     * Scope for limiting results in development
     */
    public function scopeLimitInDev($query, $limit = 100)
    {
        if (config('app.debug') && !app()->environment('production')) {
            return $query->limit($limit);
        }
        
        return $query;
    }
}
