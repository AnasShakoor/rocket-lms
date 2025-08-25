<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class clearAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:all';

    /**
     * The console command description.
     *   
     * @var string
     */
    protected $description = 'Clear all caches and optimize the application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Clearing all caches...');

        // Clear Laravel caches
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');

        // Clear settings cache specifically
        Setting::clearSettingsCache();
        $this->info('Settings cache cleared.');

        // Clear any custom caches
        Cache::flush();
        $this->info('All caches cleared successfully!');

        // Optimize the application
        $this->info('Optimizing application...');
        Artisan::call('optimize');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        $this->info('Application optimized successfully!');

        return 0;
    }
}
