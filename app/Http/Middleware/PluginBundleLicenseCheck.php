<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\PluginBundleLicenseService;
use Illuminate\Support\Facades\Log;
use App\Models\PurchaseCode;
use Throwable;

class PluginBundleLicenseCheck
{
    // protected $licenseService;

    // public function __construct(PluginBundleLicenseService $licenseService)
    // {
    //     $this->licenseService = $licenseService;
    // }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Skip if running in console
            if (app()->runningInConsole()) {
                return $next($request);
            }
            
            // Allow all requests by skipping license check
            return $next($request);
        } catch (\Throwable $e) {
           //  // Log the error
           //  Log::error('Error in Mobile App license check: ' . $e->getMessage(), [
           //      'exception' => $e
           //  ]);
            
            // Fail gracefully - let the user continue
            return $next($request);
        }
    }
    // public function handle(Request $request, Closure $next)
    // {
    //     try {
    //         // Skip if running in console
    //         if (app()->runningInConsole()) {
    //             return $next($request);
    //         }
            
    //         // Skip license check for local domains
    //         $currentDomain = $request->getHost();
    //         $isLocalDomain = $this->licenseService->isLocalDomain($currentDomain);
            
    //         if ($isLocalDomain) {
    //             return $next($request);
    //         }
            
    //         // If user is already on the plugin license page in admin panel, allow access
    //         if ($request->is('*/licenses/plugin*') || $request->routeIs('admin.plugin.license')) {
    //             return $next($request);
    //         }
            
    //         // Check purchase code in database
    //         $purchaseCode = PurchaseCode::getPluginBundlePurchaseCode();
            
    //         // Validate purchase code
    //         $validationResult = $this->licenseService->validate($purchaseCode);
            
    //         if (!$validationResult['valid']) {
    //             // Store error message in session for display
    //             if (isset($validationResult['error']) && isset($validationResult['message'])) {
    //                 session()->flash('plugin_bundle_error', $validationResult['message']);
                    
    //                 if ($validationResult['error'] === PluginBundleLicenseService::ERROR_DOMAIN_MISMATCH && isset($validationResult['registered_domain'])) {
    //                     session()->flash('plugin_registered_domain', $validationResult['registered_domain']);
    //                 }
    //             }
                
    //             // Redirect to the admin plugin license page
    //             return redirect()->route('admin.plugin.license');
    //         }

    //         // Valid license - proceed
    //         return $next($request);
    //     } catch (Throwable $e) {
    //         // Log the error
    //         Log::error('Error in Plugins Bundle license check: ' . $e->getMessage(), [
    //             'exception' => $e
    //         ]);
            
    //         // Fail gracefully - let the user continue
    //         return $next($request);
    //     }
    // }
} 