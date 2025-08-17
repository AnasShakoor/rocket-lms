<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Services\ThemeBuilderLicenseService;
use App\Services\LicenseService;
use App\Models\PurchaseCode;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class ThemeBuilderLicenseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ThemeBuilderLicenseService::class, function ($app) {
            return new ThemeBuilderLicenseService($app->make(LicenseService::class));
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
        $licenseService = app(ThemeBuilderLicenseService::class);
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
                
                // If user is on the theme builder license page, allow access
                if ($route->uri == $adminPrefix . '/licenses/theme-builder' || $route->uri == $adminPrefix . '/licenses/theme-builder/*' || 
                    $route->getName() == 'admin.theme-builder.license') {
                    return;
                }
                
                // Check if this route uses the theme_builder_license middleware
                $middlewares = $route->middleware();
                if (!in_array('theme_builder_license', $middlewares)) {
                    return;
                }
                
                $licenseService = app(ThemeBuilderLicenseService::class);
                $purchaseCode = PurchaseCode::getThemeBuilderPurchaseCode();

                $validationResult = $licenseService->validate($purchaseCode);
                
                if (!$validationResult['valid']) {
                    // Log the license validation failure
                    Log::warning('Theme builder license validation failed in event listener', [
                        'error' => $validationResult['error'] ?? null,
                        'message' => $validationResult['message'] ?? null
                    ]);
                    
                    // Store error details in session for display on the purchase code page
                    if (isset($validationResult['error']) && isset($validationResult['message'])) {
                        session()->flash('theme_builder_error', $validationResult['message']);
                        
                        if ($validationResult['error'] === ThemeBuilderLicenseService::ERROR_DOMAIN_MISMATCH && isset($validationResult['registered_domain'])) {
                            session()->flash('theme_builder_registered_domain', $validationResult['registered_domain']);
                        }
                    }
                    
                    if (Route::has('admin.theme-builder.license')) {
                        redirect()->route('admin.theme-builder.license')->send();
                        exit;
                    } else {
                        redirect('/' . $adminPrefix . '/licenses/theme-builder')->send();
                        exit;
                    }
                }
            } catch (\Exception $e) {
                // Log the error
                Log::error('Error in theme builder license check event listener: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
                
                // Always redirect to theme builder license page on error
                session()->flash('error', 'An error occurred while validating your theme builder license.');
                
                // Get admin prefix
                $adminPrefix = getAdminPanelUrlPrefix();
                
                if (Route::has('admin.theme-builder.license')) {
                    redirect()->route('admin.theme-builder.license')->send();
                } else {
                    redirect('/' . $adminPrefix . '/licenses/theme-builder')->send();
                }
                exit;
            }
        });
    }
} 