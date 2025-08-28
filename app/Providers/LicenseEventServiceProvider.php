<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Services\LicenseService;
use App\Models\PurchaseCode;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class LicenseEventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */

    public function register()
    {
        $this->app->singleton(LicenseService::class, function ($app) {
            return new LicenseService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Skip for artisan commands
        if (app()->runningInConsole()) {
            return;
        }

        // Skip license check for local domains
        $licenseService = app(LicenseService::class);
        $currentDomain = request()->getHost();
        $isLocalDomain = $licenseService->isLocalDomain($currentDomain);
        
        // dd('anas');
        // if ($isLocalDomain) {
        //     return;
        // }

        Event::listen(RouteMatched::class, function ($event) {
            try {
                $route = $event->route;
                
                // If user is on the purchase code page, allow access
                if ($route->uri == 'purchase-code' || $route->uri == 'purchase-code/store' || 
                    $route->uri == 'purchase' || $route->uri == 'license') {
                    return;
                }
                
                $licenseService = app(LicenseService::class);
                $purchaseCode = PurchaseCode::getPurchaseCode();

                $validationResult = $licenseService->validate($purchaseCode);
                
                if (!$validationResult['valid']) {
                    // Log the license validation failure
                    Log::warning('License validation failed in event listener', [
                        'error' => $validationResult['error'] ?? null,
                        'message' => $validationResult['message'] ?? null
                    ]);
                    
                    // Store error details in session for display on the purchase code page
                    if (isset($validationResult['error']) && isset($validationResult['message'])) {
                        session()->flash('purchase_code_error', $validationResult['message']);
                        
                        if ($validationResult['error'] === LicenseService::ERROR_DOMAIN_MISMATCH && isset($validationResult['registered_domain'])) {
                            session()->flash('registered_domain', $validationResult['registered_domain']);
                        }
                    }
                    
                    if (Route::has('purchase.code.show')) {
                        redirect()->route('purchase.code.show')->send();
                        exit;
                    } else {
                        redirect('/purchase-code')->send();
                        exit;
                    }
                }
            } catch (\Exception $e) {
                // Log the error
                Log::error('Error in license check event listener: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
                
                // Always redirect to purchase code page on error, regardless of environment
                session()->flash('error', 'An error occurred while validating your license.');
                if (Route::has('purchase.code.show')) {
                    redirect()->route('purchase.code.show')->send();
                } else {
                    redirect('/purchase-code')->send();
                }
                exit;
            }
        });
    }
} 