<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseCode extends Model
{
    protected $table = 'purchase_code';
    protected $fillable = ['code', 'product_type', 'license_type'];

    const TYPE_MAIN = 'main';
    const TYPE_PLUGIN_BUNDLE = 'plugin_bundle';
    const TYPE_THEME_BUILDER = 'theme_builder';
    const TYPE_MOBILE_APP = 'mobile_app';

    public static function getPurchaseCode($type = self::TYPE_MAIN)
    {
        $record = self::where('product_type', $type)->first();
        return $record ? $record->code : null;
    }
    
    public static function getLicenseType($type = self::TYPE_MAIN)
    {
        $record = self::where('product_type', $type)->first();
        return $record ? $record->license_type : null;
    }

    public static function updatePurchaseCode($code, $type = self::TYPE_MAIN, $licenseType = 'Regular license')
    {
        $record = self::where('product_type', $type)->first();
        
        if ($record) {
            $record->update([
                'code' => $code,
                'license_type' => $licenseType
            ]);
        } else {
            self::create([
                'code' => $code,
                'product_type' => $type,
                'license_type' => $licenseType
            ]);
        }
        
        return true;
    }

    public static function getPluginBundlePurchaseCode()
    {
        return self::getPurchaseCode(self::TYPE_PLUGIN_BUNDLE);
    }
    
    public static function getPluginBundleLicenseType()
    {
        return self::getLicenseType(self::TYPE_PLUGIN_BUNDLE);
    }

    public static function updatePluginBundlePurchaseCode($code, $licenseType = 'Regular license')
    {
        return self::updatePurchaseCode($code, self::TYPE_PLUGIN_BUNDLE, $licenseType);
    }
    
    public static function getThemeBuilderPurchaseCode()
    {
        return self::getPurchaseCode(self::TYPE_THEME_BUILDER);
    }
    
    public static function getThemeBuilderLicenseType()
    {
        return self::getLicenseType(self::TYPE_THEME_BUILDER);
    }

    public static function updateThemeBuilderPurchaseCode($code, $licenseType = 'Regular license')
    {
        return self::updatePurchaseCode($code, self::TYPE_THEME_BUILDER, $licenseType);
    }
    
    public static function getMobileAppPurchaseCode()
    {
        return self::getPurchaseCode(self::TYPE_MOBILE_APP);
    }
    
    public static function getMobileAppLicenseType()
    {
        return self::getLicenseType(self::TYPE_MOBILE_APP);
    }

    public static function updateMobileAppPurchaseCode($code, $licenseType = 'Regular license')
    {
        return self::updatePurchaseCode($code, self::TYPE_MOBILE_APP, $licenseType);
    }
} 