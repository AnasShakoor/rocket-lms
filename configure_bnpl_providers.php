<?php
/**
 * BNPL Providers Configuration Script
 *
 * This script helps configure Tabby and MIS Pay providers in the database.
 * Run this from your project root directory.
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 BNPL Providers Configuration Script\n";
echo "=====================================\n\n";

try {
    // Check if bnpl_providers table exists
    if (!Schema::hasTable('bnpl_providers')) {
        echo "❌ Error: bnpl_providers table does not exist!\n";
        exit(1);
    }

    echo "✅ bnpl_providers table found\n\n";

    // Get current providers
    $providers = DB::table('bnpl_providers')->get();

    echo "📋 Current BNPL Providers:\n";
    foreach ($providers as $provider) {
        echo "- {$provider->name} (ID: {$provider->id})\n";
        echo "  Active: " . ($provider->is_active ? 'Yes' : 'No') . "\n";
        echo "  Installments: {$provider->installment_count}\n";
        echo "  Fee: {$provider->fee_percentage}%\n";

        // Check configuration
        if ($provider->name === 'Tabby') {
            $hasApiKey = !empty($provider->secret_api_key);
            $hasMerchantCode = !empty($provider->merchant_code);
            echo "  API Key: " . ($hasApiKey ? '✅ Set' : '❌ Missing') . "\n";
            echo "  Merchant Code: " . ($hasMerchantCode ? '✅ Set' : '❌ Missing') . "\n";
        } elseif ($provider->name === 'MIS Pay') {
            $hasAppId = !empty($provider->app_id);
            $hasAppSecret = !empty($provider->app_secret_key);
            echo "  App ID: " . ($hasAppId ? '✅ Set' : '❌ Missing') . "\n";
            echo "  App Secret: " . ($hasAppSecret ? '✅ Set' : '❌ Missing') . "\n";
        }
        echo "\n";
    }

    echo "🔧 Configuration Instructions:\n";
    echo "=============================\n\n";

    echo "1. TABBY Configuration:\n";
    echo "   - Get your Secret API Key from Tabby merchant dashboard\n";
    echo "   - Get your Merchant Code from Tabby merchant dashboard\n";
    echo "   - Update the database with:\n\n";

    echo "   UPDATE bnpl_providers SET \n";
    echo "       secret_api_key = 'your_tabby_secret_key_here',\n";
    echo "       merchant_code = 'your_tabby_merchant_code_here',\n";
    echo "       config = '{\"api_endpoint\": \"https://api.tabby.ai\", \"test_mode\": true}',\n";
    echo "       updated_at = NOW()\n";
    echo "   WHERE name = 'Tabby';\n\n";

    echo "2. MIS PAY Configuration:\n";
    echo "   - Get your App ID from MIS Pay merchant dashboard\n";
    echo "   - Get your App Secret from MIS Pay merchant dashboard\n";
    echo "   - Update the database with:\n\n";

    echo "   UPDATE bnpl_providers SET \n";
    echo "       app_id = 'your_mispay_app_id_here',\n";
    echo "       app_secret_key = 'your_mispay_app_secret_here',\n";
    echo "       config = '{\"api_endpoint\": \"https://api.mispay.co/sandbox/v1/api\", \"test_mode\": true}',\n";
    echo "       updated_at = NOW()\n";
    echo "   WHERE name = 'MIS Pay';\n\n";

    echo "3. Test Configuration:\n";
    echo "   - Visit: /api/debug/tabby/test\n";
    echo "   - Check Laravel logs for configuration details\n";
    echo "   - Test BNPL payment flow on checkout page\n\n";

    echo "📝 Note: Replace 'your_*_here' with actual values from your merchant dashboards.\n";
    echo "   The config field contains JSON with API endpoint and test mode settings.\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
