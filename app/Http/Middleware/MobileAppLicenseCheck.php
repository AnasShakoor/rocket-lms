<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\MobileAppLicenseService;
use Illuminate\Support\Facades\Log;
use App\Models\PurchaseCode;
use Throwable;

class MobileAppLicenseCheck
{
    // protected $licenseService;

    // public function __construct(MobileAppLicenseService $licenseService)
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
            
    //         // If user is already on the mobile app license page in admin panel, allow access
    //         if ($request->is('*/mobile-app-license*') || $request->routeIs('admin.mobile_app.license')) {
    //             return $next($request);
    //         }
            
    //         // Check purchase code in database
    //         $purchaseCode = PurchaseCode::getMobileAppPurchaseCode();
            
    //         // Validate purchase code
    //         $validationResult = $this->licenseService->validate($purchaseCode);
            
    //         if (!$validationResult['valid']) {
    //             // Store error message in session for display
    //             if (isset($validationResult['error']) && isset($validationResult['message'])) {
    //                 session()->flash('mobile_app_error', $validationResult['message']);
                    
    //                 if ($validationResult['error'] === MobileAppLicenseService::ERROR_DOMAIN_MISMATCH && isset($validationResult['registered_domain'])) {
    //                     session()->flash('mobile_app_registered_domain', $validationResult['registered_domain']);
    //                 }
    //             }
                
    //             // Redirect to the admin mobile app license page
    //             return redirect()->route('admin.mobile_app.license');
    //         }

    //         // Valid license - proceed
    //         return $next($request);
    //     } catch (Throwable $e) {
    //         // Log the error
    //         Log::error('Error in Mobile App license check: ' . $e->getMessage(), [
    //             'exception' => $e
    //         ]);
            
    //         // Fail gracefully - let the user continue
    //         return $next($request);
    //     }
    // }
} 