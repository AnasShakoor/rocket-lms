<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::group(['prefix' => '/development'], function () {

    Route::get('/', function () {
        return response()->json([
            'code' => 200,
            'message' => 'OK, API test'
        ]);
    });

    Route::middleware('api') ->group(base_path('routes/api/auth.php'));

    Route::namespace('Web')->group(base_path('routes/api/guest.php'));

    Route::prefix('panel')->namespace('Panel')->group(base_path('routes/api/user.php'));

    Route::group(['namespace' => 'Config', 'middleware' => []], function () {
        Route::get('/config', ['uses' => 'ConfigController@list']);
        Route::get('/config/register/{type}', ['uses' => 'ConfigController@getRegisterConfig']);
    });

    Route::prefix('instructor')->middleware(['api.auth', 'api.level-access:teacher'])->namespace('Instructor')->group(base_path('routes/api/instructor.php'));

    // Eligibility checks disabled for Tabby and MisPay

    // Debug endpoint for Tabby
    Route::get('/debug/tabby/test', function() {
        try {
            $tabbyService = new \App\Services\TabbyService();
            $configStatus = $tabbyService->getConfigurationStatus();

            return response()->json([
                'configured' => $tabbyService->isConfigured(),
                'config_status' => $configStatus,
                'api_key_length' => strlen($tabbyService->apiKey ?? ''),
                'merchant_code_length' => strlen($tabbyService->merchantCode ?? ''),
                'endpoint' => $tabbyService->apiEndpoint ?? 'not_set'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });


});
