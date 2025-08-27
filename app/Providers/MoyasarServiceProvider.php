<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MoyasarServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/moyasar.php' => config_path('moyasar.php'),
        ], 'moyasar-config');
    }
}
