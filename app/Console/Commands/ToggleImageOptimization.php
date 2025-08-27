<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ToggleImageOptimization extends Command
{
    protected $signature = 'image:optimize {action : enable/disable/status} {--config= : Specific configuration to toggle}';

    protected $description = 'Toggle image optimization settings to improve page loading performance';

    public function handle()
    {
        $action = $this->argument('action');
        $config = $this->option('config');

        $envFile = base_path('.env');
        $envContent = File::exists($envFile) ? File::get($envFile) : '';

        $settings = [
            'IMAGE_HANDLE_404_ERRORS' => 'true',
            'IMAGE_LAZY_LOADING' => 'true',
            'IMAGE_SHOW_PLACEHOLDERS' => 'false',
            'IMAGE_LOADING_TIMEOUT' => '5000',
            'IMAGE_RETRY_ATTEMPTS' => '1',
            'IMAGE_CONSOLE_LOG_LEVEL' => 'warn'
        ];

        switch ($action) {
            case 'enable':
                $this->enableOptimization($envFile, $envContent, $settings, $config);
                break;

            case 'disable':
                $this->disableOptimization($envFile, $envContent, $settings, $config);
                break;

            case 'status':
                $this->showStatus($envContent);
                break;

            default:
                $this->error('Invalid action. Use: enable, disable, or status');
                return 1;
        }

        return 0;
    }

    private function enableOptimization($envFile, $envContent, $settings, $config)
    {
        if ($config) {
            if (!array_key_exists($config, $settings)) {
                $this->error("Invalid configuration: {$config}");
                $this->info("Available configurations: " . implode(', ', array_keys($settings)));
                return;
            }

            $this->updateEnvSetting($envFile, $envContent, $config, $settings[$config]);
            $this->info("Image optimization setting '{$config}' enabled.");
        } else {
            foreach ($settings as $key => $value) {
                $this->updateEnvSetting($envFile, $envContent, $key, $value);
            }
            $this->info('All image optimization settings enabled.');
        }

        $this->info('Remember to clear config cache: php artisan config:clear');
    }

    private function disableOptimization($envFile, $envContent, $settings, $config)
    {
        if ($config) {
            if (!array_key_exists($config, $settings)) {
                $this->error("Invalid configuration: {$config}");
                $this->info("Available configurations: " . implode(', ', array_keys($settings)));
                return;
            }

            $this->updateEnvSetting($envFile, $envContent, $config, 'false');
            $this->info("Image optimization setting '{$config}' disabled.");
        } else {
            foreach ($settings as $key => $value) {
                $this->updateEnvSetting($envFile, $envContent, $key, 'false');
            }
            $this->info('All image optimization settings disabled.');
        }

        $this->info('Remember to clear config cache: php artisan config:clear');
    }

    private function showStatus($envContent)
    {
        $this->info('Image Optimization Status:');
        $this->info('========================');

        $settings = [
            'IMAGE_HANDLE_404_ERRORS' => 'Handle 404 image errors',
            'IMAGE_LAZY_LOADING' => 'Enable lazy loading',
            'IMAGE_SHOW_PLACEHOLDERS' => 'Show broken image placeholders',
            'IMAGE_LOADING_TIMEOUT' => 'Image loading timeout (ms)',
            'IMAGE_RETRY_ATTEMPTS' => 'Retry attempts for failed images',
            'IMAGE_CONSOLE_LOG_LEVEL' => 'Console logging level'
        ];

        foreach ($settings as $key => $description) {
            $value = $this->getEnvValue($envContent, $key);
            $status = $value ? 'Enabled' : 'Disabled';
            $this->line("{$description}: {$status} ({$key}={$value})");
        }
    }

    private function updateEnvSetting($envFile, $envContent, $key, $value)
    {
        if (strpos($envContent, $key . '=') !== false) {
            $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
        } else {
            $envContent .= "\n{$key}={$value}";
        }

        File::put($envFile, $envContent);
    }

    private function getEnvValue($envContent, $key)
    {
        if (preg_match("/^{$key}=(.*)/m", $envContent, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}
