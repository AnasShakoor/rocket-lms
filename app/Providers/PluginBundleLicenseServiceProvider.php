<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Services\PluginBundleLicenseService;
use App\Services\LicenseService;
use App\Models\PurchaseCode;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class PluginBundleLicenseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PluginBundleLicenseService::class, function ($app) {
            return new PluginBundleLicenseService($app->make(LicenseService::class));
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
        $licenseService = app(PluginBundleLicenseService::class);
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
                
                // If user is on the plugin license page, allow access
                if ($route->uri == $adminPrefix . '/licenses/plugin' || $route->uri == $adminPrefix . '/licenses/plugin/*' || 
                    $route->getName() == 'admin.plugin.license') {
                    return;
                }
                
   // Check if this route uses the plugin_bundle_license middleware
                $middlewares = $route->middleware();
                if (!in_array('plugin_bundle_license', $middlewares)) {
                    return;
                }
                
                $licenseService = app(PluginBundleLicenseService::class);
                $purchaseCode = PurchaseCode::getPluginBundlePurchaseCode();

                $validationResult = $licenseService->validate($purchaseCode);
                
                if (!$validationResult['valid']) {
                    // Log the license validation failure
                    Log::warning('Plugins Bundle license validation failed in event listener', [
                        'error' => $validationResult['error'] ?? null,
                        'message' => $validationResult['message'] ?? null
                    ]);
                    
                    // Store error details in session for display on the purchase code page
                    if (isset($validationResult['error']) && isset($validationResult['message'])) {
                        session()->flash('plugin_bundle_error', $validationResult['message']);
                        
                        if ($validationResult['error'] === PluginBundleLicenseService::ERROR_DOMAIN_MISMATCH && isset($validationResult['registered_domain'])) {
                            session()->flash('plugin_registered_domain', $validationResult['registered_domain']);
                        }
                    }
                    
                    if (Route::has('admin.plugin.license')) {
                        redirect()->route('admin.plugin.license')->send();
                        exit;
                    } else {
                        redirect('/' . $adminPrefix . '/licenses/plugin')->send();
                        exit;
                    }
                }
            } catch (\Exception $e) {
                // Log the error
                Log::error('Error in Plugins Bundle license check event listener: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
                
                // Always redirect to plugin license page on error
                session()->flash('error', 'An error occurred while validating your Plugins Bundle license.');
                
                // Get admin prefix
                $adminPrefix = getAdminPanelUrlPrefix();
                
                if (Route::has('admin.plugin.license')) {
                    redirect()->route('admin.plugin.license')->send();
                } else {
                    redirect('/' . $adminPrefix . '/licenses/plugin')->send();
                }
                exit;
            }
        });
    }
} 