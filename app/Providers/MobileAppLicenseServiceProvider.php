<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Services\MobileAppLicenseService;
use App\Services\LicenseService;
use App\Models\PurchaseCode;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class MobileAppLicenseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MobileAppLicenseService::class, function ($app) {
            return new MobileAppLicenseService($app->make(LicenseService::class));
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
        $licenseService = app(MobileAppLicenseService::class);
        $currentDomain = request()->getHost();
        $isLocalDomain = $licenseService->isLocalDomain($currentDomain);
        
        if ($isLocalDomain) {
            return;
        }

        Event::listen(RouteMatched::class, function ($event) {
            try {
                $route = $event->route;
                
                // Get admin prefix
                $adminPrefix = getAdminPanelUrlPrefix();
                
                // If user is on the mobile app license page, allow access
                if ($route->uri == $adminPrefix . '/mobile-app-license' || $route->uri == $adminPrefix . '/mobile-app-license/*' || 
                    $route->getName() == 'admin.mobile_app.license') {
                    return;
                }
                
                // Check if this route uses the mobile_app_license middleware
                $middlewares = $route->middleware();
                if (!in_array('mobile_app_license', $middlewares)) {
                    // Also check if the route is an assignments route (for backward compatibility)
                    if (!(str_starts_with($route->uri, $adminPrefix . '/assignments') ||
                        str_contains($route->getName(), 'admin.assignments'))) {
                        return;
                    }
                }
                
                $licenseService = app(MobileAppLicenseService::class);
                $purchaseCode = PurchaseCode::getMobileAppPurchaseCode();

                $validationResult = $licenseService->validate($purchaseCode);
                
                if (!$validationResult['valid']) {
                    // Log the license validation failure
                    Log::warning('Mobile App license validation failed in event listener', [
                        'error' => $validationResult['error'] ?? null,
                        'message' => $validationResult['message'] ?? null
                    ]);
                    
                    // Store error details in session for display on the purchase code page
                    if (isset($validationResult['error']) && isset($validationResult['message'])) {
                        session()->flash('mobile_app_error', $validationResult['message']);
                        
                        if ($validationResult['error'] === MobileAppLicenseService::ERROR_DOMAIN_MISMATCH && isset($validationResult['registered_domain'])) {
                            session()->flash('mobile_app_registered_domain', $validationResult['registered_domain']);
                        }
                    }
                    
                    if (Route::has('admin.mobile_app.license')) {
                        redirect()->route('admin.mobile_app.license')->send();
                        exit;
                    } else {
                        redirect('/' . $adminPrefix . '/mobile-app-license')->send();
                        exit;
                    }
                }
            } catch (\Exception $e) {
                // Log the error
                Log::error('Error in Mobile App license check event listener: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
                
                // Always redirect to mobile app license page on error
                session()->flash('error', 'An error occurred while validating your Mobile App license.');
                
                // Get admin prefix
                $adminPrefix = getAdminPanelUrlPrefix();
                
                if (Route::has('admin.mobile_app.license')) {
                    redirect()->route('admin.mobile_app.license')->send();
                } else {
                    redirect('/' . $adminPrefix . '/mobile-app-license')->send();
                }
                exit;
            }
        });
    }
} 