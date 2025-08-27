<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait OptimizedQueries
{
    /**
     * Cache key prefix for this model
     */
    protected function getCacheKeyPrefix(): string
    {
        return strtolower(class_basename($this)) . '_';
    }

    /**
     * Get cached data with fallback
     */
    protected function getCachedData(string $key, callable $callback, int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKeyPrefix() . $key;
        $ttl = $ttl ?? config('performance.cache.default_ttl', 3600);

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Optimize query with eager loading and caching
     */
    protected function optimizedQuery(Builder $query, array $with = [], int $ttl = null): Builder
    {
        // Add eager loading to prevent N+1 queries
        if (!empty($with)) {
            $query->with($with);
        }

        // Add query optimization hints
        $query->select($this->getTable() . '.*');

        return $query;
    }

    /**
     * Get paginated results with optimization
     */
    protected function getOptimizedPaginated(Builder $query, int $perPage = null, array $with = []): mixed
    {
        $perPage = $perPage ?? config('performance.pagination.default_per_page', 20);
        $perPage = min($perPage, config('performance.pagination.max_per_page', 100));

        $query = $this->optimizedQuery($query, $with);

        return $query->paginate($perPage);
    }

    /**
     * Get cached paginated results
     */
    protected function getCachedPaginated(string $cacheKey, Builder $query, int $perPage = null, array $with = [], int $ttl = null): mixed
    {
        return $this->getCachedData($cacheKey, function () use ($query, $perPage, $with) {
            return $this->getOptimizedPaginated($query, $perPage, $with);
        }, $ttl);
    }

    /**
     * Optimize user queries with common relationships
     */
    protected function optimizeUserQuery(Builder $query): Builder
    {
        return $query->with([
            'userMetas',
            'userGroup.group',
            'role',
            'badges.badge',
            'occupations.category'
        ])->select([
            'id',
            'full_name',
            'email',
            'username',
            'avatar',
            'role_name',
            'role_id',
            'status',
            'verified',
            'created_at',
            'updated_at'
        ]);
    }

    /**
     * Optimize webinar queries with common relationships
     */
    protected function optimizeWebinarQuery(Builder $query): Builder
    {
        return $query->with([
            'creator:id,full_name,avatar,role_name',
            'teacher:id,full_name,avatar,role_name',
            'category:id,title',
            'feature',
            'tickets',
            'reviews'
        ])->select([
            'id',
            'title',
            'slug',
            'creator_id',
            'teacher_id',
            'category_id',
            'status',
            'price',
            'created_at',
            'updated_at'
        ]);
    }

    /**
     * Optimize payment queries
     */
    protected function optimizePaymentQuery(Builder $query): Builder
    {
        return $query->with([
            'user:id,full_name,email',
            'webinar:id,title,price',
            'product:id,title,price'
        ])->select([
            'id',
            'user_id',
            'webinar_id',
            'product_id',
            'amount',
            'status',
            'created_at'
        ]);
    }

    /**
     * Get optimized user data with caching
     */
    protected function getOptimizedUser(int $userId, array $additionalWith = []): mixed
    {
        $cacheKey = "user_{$userId}_optimized";
        
        return $this->getCachedData($cacheKey, function () use ($userId, $additionalWith) {
            $query = $this->newQuery()->where('id', $userId);
            $query = $this->optimizeUserQuery($query);
            
            if (!empty($additionalWith)) {
                $query->with($additionalWith);
            }
            
            return $query->first();
        }, config('performance.cache.user_ttl', 1800));
    }

    /**
     * Get optimized webinar data with caching
     */
    protected function getOptimizedWebinar(int $webinarId, array $additionalWith = []): mixed
    {
        $cacheKey = "webinar_{$webinarId}_optimized";
        
        return $this->getCachedData($cacheKey, function () use ($webinarId, $additionalWith) {
            $query = $this->newQuery()->where('id', $webinarId);
            $query = $this->optimizeWebinarQuery($query);
            
            if (!empty($additionalWith)) {
                $query->with($additionalWith);
            }
            
            return $query->first();
        }, config('performance.cache.webinar_ttl', 900));
    }

    /**
     * Bulk update with optimization
     */
    protected function bulkUpdate(array $data, array $conditions): int
    {
        $query = $this->newQuery();
        
        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }
        
        $result = $query->update($data);
        
        // Clear related caches
        $this->clearRelatedCaches($conditions);
        
        return $result;
    }

    /**
     * Clear related caches
     */
    protected function clearRelatedCaches(array $conditions): void
    {
        foreach ($conditions as $column => $value) {
            $cacheKey = $this->getCacheKeyPrefix() . "{$column}_{$value}";
            Cache::forget($cacheKey);
        }
    }

    /**
     * Execute query with timeout protection
     */
    protected function executeWithTimeout(callable $callback, int $timeout = 30): mixed
    {
        $timeout = $timeout ?? config('performance.database.query_timeout', 30);
        
        // Set query timeout
        DB::statement("SET SESSION MAX_EXECUTION_TIME = {$timeout}");
        
        try {
            return $callback();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'timeout')) {
                // Log timeout and return fallback
                Log::warning("Query timeout after {$timeout} seconds");
                return null;
            }
            throw $e;
        }
    }

    /**
     * Get query execution plan
     */
    protected function getQueryPlan(Builder $query): array
    {
        try {
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            
            $explainQuery = "EXPLAIN " . $sql;
            $plan = DB::select($explainQuery, $bindings);
            
            return $plan;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Optimize table if needed
     */
    protected function optimizeTableIfNeeded(): void
    {
        try {
            $tableName = $this->getTable();
            $lastOptimized = Cache::get("table_optimized_{$tableName}");
            
            // Optimize table once per day
            if (!$lastOptimized || (time() - $lastOptimized) > 86400) {
                DB::statement("OPTIMIZE TABLE {$tableName}");
                Cache::put("table_optimized_{$tableName}", time(), 86400);
            }
        } catch (\Exception $e) {
            Log::warning("Table optimization failed: " . $e->getMessage());
        }
    }
}
