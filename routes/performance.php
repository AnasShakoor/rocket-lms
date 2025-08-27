<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PerformanceController;

/*
|--------------------------------------------------------------------------
| Performance Optimization Routes
|--------------------------------------------------------------------------
|
| These routes handle performance monitoring and optimization
|
*/

Route::group(['prefix' => 'admin/performance', 'middleware' => ['auth', 'admin'], 'as' => 'admin.performance.'], function () {
    Route::get('/', [PerformanceController::class, 'index'])->name('index');
    Route::post('/optimize', [PerformanceController::class, 'optimize'])->name('optimize');
    Route::post('/clear-cache/{type}', [PerformanceController::class, 'clearCache'])->name('clear-cache');
    Route::get('/metrics', [PerformanceController::class, 'getMetrics'])->name('metrics');
});

// Public performance optimization route (for cron jobs)
Route::post('/performance/optimize', [PerformanceController::class, 'optimize'])->middleware('throttle:10,1');
