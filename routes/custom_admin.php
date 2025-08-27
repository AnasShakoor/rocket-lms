<?php

/**
 * Custom Admin Routes File
 *
 * This file allows adding custom admin routes without modifying the main admin.php
 * Routes defined here will be loaded by the main admin routes file.
 */

use Illuminate\Support\Facades\Route;

// Get the admin panel prefix from the main application
$prefix = getAdminPanelUrlPrefix();

/**
 * Define your custom admin panel routes here
 * They will be automatically loaded alongside the main admin routes
 *
 * All routes will be prefixed with your admin panel prefix
 * and will have 'web' and 'admin' middleware applied automatically
 */

// Simulation Module Routes
Route::group(['prefix' => 'simulation'], function () {
    Route::get('/', 'SimulationController@index')->name('admin.simulation.index');
    Route::get('/create', 'SimulationController@create')->name('admin.simulation.create');
    Route::post('/store', 'SimulationController@store')->name('admin.simulation.store');
    Route::get('/{rule}', 'SimulationController@show')->name('admin.simulation.show');
    Route::post('/{rule}/preview', 'SimulationController@preview')->name('admin.simulation.preview');
    Route::post('/{rule}/execute', 'SimulationController@execute')->name('admin.simulation.execute');
    Route::delete('/{rule}', 'SimulationController@destroy')->name('admin.simulation.destroy');

    // Test route for debugging
    Route::get('/test', function() {
        return response()->json([
            'message' => 'Simulation routes are working',
            'timestamp' => now(),
            'user' => auth()->user() ? auth()->user()->id : 'not authenticated'
        ]);
    })->name('admin.simulation.test');
});

// BNPL Providers Routes
Route::group(['prefix' => 'bnpl-providers'], function () {
    Route::get('/', 'BnplProviderController@index')->name('admin.bnpl-providers.index');
Route::get('/create', 'BnplProviderController@create')->name('admin.bnpl-providers.create');
Route::post('/store', 'BnplProviderController@store')->name('admin.bnpl-providers.store');
Route::get('/{provider}/edit', 'BnplProviderController@edit')->name('admin.bnpl-providers.edit');
Route::put('/{provider}', 'BnplProviderController@update')->name('admin.bnpl-providers.update');
Route::delete('/{provider}', 'BnplProviderController@destroy')->name('admin.bnpl-providers.destroy');
Route::get('/tabby/status', 'BnplProviderController@tabbyStatus')->name('admin.bnpl-providers.tabby-status');
});

// Enhanced Reports Routes
Route::group(['prefix' => 'enhanced-reports'], function () {
    Route::get('/', 'EnhancedReportController@index')->name('admin.enhanced-reports.index');
    Route::get('/charts', 'EnhancedReportController@charts')->name('admin.enhanced-reports.charts');
    Route::get('/chart-data', 'EnhancedReportController@getChartData')->name('admin.enhanced-reports.chart-data');
    Route::post('/export', 'EnhancedReportController@export')->name('admin.enhanced-reports.export');
    Route::post('/send-email', 'EnhancedReportController@sendEmail')->name('admin.enhanced-reports.send-email');
    Route::post('/archive', 'EnhancedReportController@archive')->name('admin.enhanced-reports.archive');
    Route::get('/archived', 'EnhancedReportController@archived')->name('admin.enhanced-reports.archived');
    Route::post('/restore', 'EnhancedReportController@restore')->name('admin.enhanced-reports.restore');
});

// Example of custom routes:
//
// Route::group(['prefix' => 'custom-section'], function () {
//     Route::get('/', 'YourCustomController@index')->name('admin.custom.index');
//     Route::get('/create', 'YourCustomController@create')->name('admin.custom.create');
//     Route::post('/store', 'YourCustomController@store')->name('admin.custom.store');
//     Route::get('/{id}/edit', 'YourCustomController@edit')->name('admin.custom.edit');
//     Route::post('/{id}/update', 'YourCustomController@update')->name('admin.custom.update');
//     Route::get('/{id}/delete', 'YourCustomController@delete')->name('admin.custom.delete');
// });

// You can add as many route groups as needed

/**
 * To use these routes, you must have your controller in App\Http\Controllers\Admin namespace
 * or specify the complete namespace like:
 *
 * Route::get('/custom-page', '\App\Http\Controllers\YourNamespace\YourController@method');
 */
