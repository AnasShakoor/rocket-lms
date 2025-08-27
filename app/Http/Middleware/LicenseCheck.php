<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LicenseService;
use App\Models\PurchaseCode;
use Illuminate\Support\Facades\Log;

class LicenseCheck
{
    // protected $licenseService;

    // public function __construct(LicenseService $licenseService)
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
            
    //         // If user is on the purchase code page, allow access
    //         if ($request->is('purchase-code*')) {
    //             return $next($request);
    //         }
            
    //         // Check purchase code in database
    //         $purchaseCode = PurchaseCode::getPurchaseCode();
            
    //         // Validate purchase code
    //         $validationResult = $this->licenseService->validate($purchaseCode);
            
    //         if (!$validationResult['valid']) {
    //             // Store error message in session for display
    //             if (isset($validationResult['error']) && isset($validationResult['message'])) {
    //                 session()->flash('purchase_code_error', $validationResult['message']);
                    
    //                 if ($validationResult['error'] === LicenseService::ERROR_DOMAIN_MISMATCH && isset($validationResult['registered_domain'])) {
    //                     session()->flash('registered_domain', $validationResult['registered_domain']);
    //                 }
    //             }
                
    //             // Redirect to purchase code page
    //             return redirect()->route('purchase.code.show');
    //         }

    //         // Valid license - proceed
    //         return $next($request);
    //     } catch (\Exception $e) {
    //         // Log the error
    //         Log::error('Error in license check: ' . $e->getMessage(), [
    //             'exception' => $e
    //         ]);
            
    //         // Fail gracefully - let the user continue
    //         return $next($request);
    //     }
    // }
} 