<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseCode;
use App\Services\LicenseService;
use App\Services\PluginBundleLicenseService;
use App\Services\ThemeBuilderLicenseService;
use App\Services\MobileAppLicenseService;

class LicensesController extends Controller
{
    protected $mainLicenseService;
    protected $pluginLicenseService;
    protected $themeBuilderLicenseService;
    protected $mobileAppLicenseService;

    public function __construct(
        LicenseService $mainLicenseService, 
        PluginBundleLicenseService $pluginLicenseService,
        ThemeBuilderLicenseService $themeBuilderLicenseService,
        MobileAppLicenseService $mobileAppLicenseService
    ) {
        $this->mainLicenseService = $mainLicenseService;
        $this->pluginLicenseService = $pluginLicenseService;
        $this->themeBuilderLicenseService = $themeBuilderLicenseService;
        $this->mobileAppLicenseService = $mobileAppLicenseService;
    }

    /**
     * Display licenses page
     */
    public function index()
    {
        $mainLicense = [
            'code' => PurchaseCode::getPurchaseCode(),
            'license_type' => PurchaseCode::getLicenseType(),
            'status' => null,
            'message' => null
        ];

        $pluginBundleLicense = [
            'code' => PurchaseCode::getPluginBundlePurchaseCode(),
            'license_type' => PurchaseCode::getPluginBundleLicenseType(),
            'status' => null,
            'message' => null
        ];

        $themeBuilderLicense = [
            'code' => PurchaseCode::getThemeBuilderPurchaseCode(),
            'license_type' => PurchaseCode::getThemeBuilderLicenseType(),
            'status' => null,
            'message' => null
        ];
        
        $mobileAppLicense = [
            'code' => PurchaseCode::getMobileAppPurchaseCode(),
            'license_type' => PurchaseCode::getMobileAppLicenseType(),
            'status' => null,
            'message' => null
        ];

        // Validate main license
        if ($mainLicense['code']) {
            $validationResult = $this->mainLicenseService->validate($mainLicense['code']);
            $mainLicense['status'] = $validationResult['valid'];
            
            if (!$validationResult['valid']) {
                $mainLicense['message'] = $validationResult['message'] ?? 'Invalid license';
                $mainLicense['error_type'] = $validationResult['error'] ?? null;
            }
        }

        // Validate plugin bundle license
        if ($pluginBundleLicense['code']) {
            $validationResult = $this->pluginLicenseService->validate($pluginBundleLicense['code']);
            $pluginBundleLicense['status'] = $validationResult['valid'];
            
            if (!$validationResult['valid']) {
                $pluginBundleLicense['message'] = $validationResult['message'] ?? 'Invalid license';
                $pluginBundleLicense['error_type'] = $validationResult['error'] ?? null;
            }
        }

        // Validate theme builder license
        if ($themeBuilderLicense['code']) {
            $validationResult = $this->themeBuilderLicenseService->validate($themeBuilderLicense['code']);
            $themeBuilderLicense['status'] = $validationResult['valid'];
            
            if (!$validationResult['valid']) {
                $themeBuilderLicense['message'] = $validationResult['message'] ?? 'Invalid license';
                $themeBuilderLicense['error_type'] = $validationResult['error'] ?? null;
            }
        }
        
        // Validate mobile app license
        if ($mobileAppLicense['code']) {
            $validationResult = $this->mobileAppLicenseService->validate($mobileAppLicense['code']);
            $mobileAppLicense['status'] = $validationResult['valid'];
            
            if (!$validationResult['valid']) {
                $mobileAppLicense['message'] = $validationResult['message'] ?? 'Invalid license';
                $mobileAppLicense['error_type'] = $validationResult['error'] ?? null;
            }
        }

        $data = [
            'pageTitle' => trans('admin/main.licenses'),
            'mainLicense' => $mainLicense,
            'pluginBundleLicense' => $pluginBundleLicense,
            'themeBuilderLicense' => $themeBuilderLicense,
            'mobileAppLicense' => $mobileAppLicense,
        ];

        return view('admin.licenses.index', $data);
    }
} 