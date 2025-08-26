<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Config;

class OptimizedImage extends Component
{
    public $src;
    public $alt;
    public $class;
    public $width;
    public $height;
    public $lazy;
    public $priority;
    public $fallback;
    public $placeholder;

    public function __construct(
        $src = null,
        $alt = '',
        $class = '',
        $width = null,
        $height = null,
        $lazy = true,
        $priority = 'normal',
        $fallback = true,
        $placeholder = true
    ) {
        $this->src = $src;
        $this->alt = $alt;
        $this->class = $class;
        $this->width = $width;
        $this->lazy = $lazy;
        $this->priority = $priority;
        $this->fallback = $fallback;
        $this->placeholder = $placeholder;
    }

    public function render()
    {
        // Check if image optimization is enabled
        if (!Config::get('image-optimization.enabled', true)) {
            return $this->renderBasicImage();
        }

        return $this->renderOptimizedImage();
    }

    protected function renderBasicImage()
    {
        return view('components.optimized-image', [
            'src' => $this->src,
            'alt' => $this->alt,
            'class' => $this->class,
            'width' => $this->width,
            'height' => $this->height,
            'optimized' => false
        ]);
    }

    protected function renderOptimizedImage()
    {
        $attributes = $this->buildAttributes();
        
        return view('components.optimized-image', [
            'src' => $this->src,
            'alt' => $this->alt,
            'class' => $this->class,
            'width' => $this->width,
            'height' => $this->height,
            'attributes' => $attributes,
            'optimized' => true,
            'fallback' => $this->fallback,
            'placeholder' => $this->placeholder
        ]);
    }

    protected function buildAttributes()
    {
        $attributes = [];

        // Add lazy loading
        if ($this->lazy && Config::get('image-optimization.lazy_loading.enabled', true)) {
            $attributes['loading'] = 'lazy';
        }

        // Add decoding attribute
        if (Config::get('image-optimization.performance.add_decoding', true)) {
            $attributes['decoding'] = 'async';
        }

        // Add fetch priority
        if (Config::get('image-optimization.performance.add_fetchpriority', true)) {
            $attributes['fetchpriority'] = $this->priority;
        }

        // Add error handling
        if ($this->fallback) {
            $attributes['onerror'] = 'this.style.display=\'none\'; this.nextElementSibling?.style.display=\'block\';';
            $attributes['onload'] = 'this.nextElementSibling?.style.display=\'none\';';
        }

        return $attributes;
    }

    public function shouldShowFallback()
    {
        return $this->fallback && Config::get('image-optimization.fallbacks.enabled', true);
    }

    public function getFallbackText()
    {
        if (empty($this->alt)) {
            return Config::get('image-optimization.fallbacks.placeholder_text', '📷 Image');
        }

        return "📷 {$this->alt}";
    }

    public function getPlaceholderType()
    {
        if (empty($this->src)) {
            return 'default';
        }

        if (str_contains($this->src, 'course') || str_contains($this->class, 'course')) {
            return 'course';
        }

        if (str_contains($this->src, 'product') || str_contains($this->class, 'product')) {
            return 'product';
        }

        if (str_contains($this->src, 'avatar') || str_contains($this->class, 'avatar')) {
            return 'user';
        }

        return 'default';
    }
}
