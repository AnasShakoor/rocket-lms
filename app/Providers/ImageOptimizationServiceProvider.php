<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ImageOptimizationMiddleware;

class ImageOptimizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/image-optimization.php', 'image-optimization'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/image-optimization.php' => config_path('image-optimization.php'),
        ], 'image-optimization');

        // Register middleware - TEMPORARILY DISABLED FOR TESTING
        // $this->app['router']->pushMiddlewareToGroup('web', ImageOptimizationMiddleware::class);

        // Register Blade component
        Blade::component('optimized-image', \App\View\Components\OptimizedImage::class);

        // Register Blade directives
        $this->registerBladeDirectives();

        // Add image optimization assets to views
        $this->addImageOptimizationAssets();
    }

    /**
     * Register Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        // @image directive for quick optimized images
        Blade::directive('image', function ($expression) {
            return "<?php echo app('App\\View\\Components\\OptimizedImage')->render($expression); ?>";
        });

        // @lazyimage directive for lazy loaded images
        Blade::directive('lazyimage', function ($expression) {
            return "<?php echo app('App\\View\\Components\\OptimizedImage')->render($expression . ', true'); ?>";
        });

        // @priorityimage directive for high priority images
        Blade::directive('priorityimage', function ($expression) {
            return "<?php echo app('App\\View\\Components\\OptimizedImage')->render($expression . ', false, \'high\'); ?>";
        });
    }

    /**
     * Add image optimization assets to views
     */
    protected function addImageOptimizationAssets(): void
    {
        // Add CSS and JS to all views when image optimization is enabled
        if (config('image-optimization.enabled', true)) {
            view()->composer('*', function ($view) {
                $view->with('imageOptimizationEnabled', true);
            });
        }
    }
}
