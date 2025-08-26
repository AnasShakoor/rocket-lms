<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class ImageOptimizationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // Only apply image optimization on localhost/development
        if (App::environment('local') || $request->getHost() === 'localhost' || str_contains($request->getHost(), '.test')) {
            $this->optimizeImages($response);
        }

        return $response;
    }

    /**
     * Optimize images in the response
     */
    protected function optimizeImages($response): void
    {
        try {
            // Only process HTML responses
            if (!$response instanceof Response || !$this->isHtmlResponse($response)) {
                return;
            }

            $content = $response->getContent();
            
            // Skip if content is empty or not HTML
            if (empty($content) || !$this->isHtmlContent($content)) {
                return;
            }

            // Apply image optimization
            $optimizedContent = $this->processImageOptimization($content);
            
            if ($optimizedContent !== $content) {
                $response->setContent($optimizedContent);
            }
            
        } catch (\Exception $e) {
            Log::warning('Image optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if response is HTML
     */
    protected function isHtmlResponse($response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'text/html');
    }

    /**
     * Check if content is HTML
     */
    protected function isHtmlContent(string $content): bool
    {
        return str_contains($content, '<html') || str_contains($content, '<!DOCTYPE');
    }

    /**
     * Process image optimization in HTML content
     */
    protected function processImageOptimization(string $content): string
    {
        // Add lazy loading to images
        $content = preg_replace(
            '/<img([^>]*)>/i',
            '<img$1 loading="lazy">',
            $content
        );

        // Add width and height attributes if missing (helps with layout stability)
        $content = preg_replace_callback(
            '/<img([^>]*?)>/i',
            function ($matches) {
                $imgTag = $matches[0];
                
                // Skip if already has width and height
                if (str_contains($imgTag, 'width=') && str_contains($imgTag, 'height=')) {
                    return $imgTag;
                }
                
                // Add default dimensions for better CLS (Cumulative Layout Shift)
                if (!str_contains($imgTag, 'width=')) {
                    $imgTag = str_replace('<img', '<img width="800"', $imgTag);
                }
                if (!str_contains($imgTag, 'height=')) {
                    $imgTag = str_replace('<img', '<img height="600"', $imgTag);
                }
                
                return $imgTag;
            },
            $content
        );

        // Add decoding="async" for better performance
        $content = preg_replace(
            '/<img([^>]*?)>/i',
            '<img$1 decoding="async">',
            $content
        );

        return $content;
    }
}
