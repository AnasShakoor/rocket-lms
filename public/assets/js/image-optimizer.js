/**
 * Image Optimizer for Localhost Development
 * Handles missing images gracefully and optimizes loading performance
 */
class ImageOptimizer {
    constructor() {
        this.isLocalhost = this.checkIfLocalhost();
        this.placeholderImages = this.getPlaceholderImages();
        this.init();
    }

    /**
     * Check if running on localhost
     */
    checkIfLocalhost() {
        return window.location.hostname === 'localhost' || 
               window.location.hostname.includes('.test') ||
               window.location.hostname === '127.0.0.1';
    }

    /**
     * Get placeholder images for different types
     */
    getPlaceholderImages() {
        return {
            course: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjNmNGY2Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZiNzI4MCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkNVUlNPIDwvdGV4dD48L3N2Zz4=',
            product: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjNmNGY2Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZiNzI4MCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlBST0RVQ1QgPC90ZXh0Pjwvc3ZnPg==',
            user: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSI1MCIgY3k9IjUwIiByPSI1MCIgZmlsbD0iIzZiNzI4MCIvPjxjaXJjbGUgY3g9IjUwIiBjeT0iMzUiIHI9IjE1IiBmaWxsPSIjZjNmNGY2IvPjxwYXRoIGQ9Ik0yMCA4MGg2MHYyMEgyMHoiIGZpbGw9IiNmM2Y0ZjYiLz48L3N2Zz4=',
            default: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjNmNGY2Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZiNzI4MCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlPC90ZXh0Pjwvc3ZnPg=='
        };
    }

    /**
     * Initialize the image optimizer
     */
    init() {
        if (!this.isLocalhost) return;

        this.optimizeExistingImages();
        this.setupIntersectionObserver();
        this.setupErrorHandling();
        this.addPerformanceMonitoring();
    }

    /**
     * Optimize existing images on the page
     */
    optimizeExistingImages() {
        const images = document.querySelectorAll('img:not([data-optimized])');
        
        images.forEach(img => {
            this.optimizeImage(img);
            img.setAttribute('data-optimized', 'true');
        });
    }

    /**
     * Optimize a single image
     */
    optimizeImage(img) {
        // Add lazy loading if not present
        if (!img.hasAttribute('loading')) {
            img.setAttribute('loading', 'lazy');
        }

        // Add decoding attribute
        if (!img.hasAttribute('decoding')) {
            img.setAttribute('decoding', 'async');
        }

        // Add fetch priority for above-the-fold images
        if (this.isAboveTheFold(img)) {
            img.setAttribute('fetchpriority', 'high');
        }

        // Add error handling
        img.addEventListener('error', (e) => this.handleImageError(e.target));
        img.addEventListener('load', (e) => this.handleImageLoad(e.target));
    }

    /**
     * Check if image is above the fold
     */
    isAboveTheFold(img) {
        const rect = img.getBoundingClientRect();
        return rect.top < window.innerHeight && rect.bottom > 0;
    }

    /**
     * Handle image loading errors
     */
    handleImageError(img) {
        const placeholder = this.getPlaceholderForImage(img);
        
        // Hide the broken image
        img.style.display = 'none';
        
        // Create or show fallback
        let fallback = img.nextElementSibling;
        if (!fallback || !fallback.classList.contains('image-fallback')) {
            fallback = this.createFallbackElement(img);
            img.parentNode.insertBefore(fallback, img.nextSibling);
        }
        
        fallback.style.display = 'block';
        fallback.innerHTML = `<img src="${placeholder}" alt="Placeholder" style="width: 100%; height: 100%; object-fit: cover;">`;
    }

    /**
     * Handle successful image load
     */
    handleImageLoad(img) {
        // Hide fallback if it exists
        const fallback = img.nextElementSibling;
        if (fallback && fallback.classList.contains('image-fallback')) {
            fallback.style.display = 'none';
        }
        
        // Show the image
        img.style.display = '';
    }

    /**
     * Get appropriate placeholder for image type
     */
    getPlaceholderForImage(img) {
        const src = img.src || '';
        const alt = img.alt || '';
        const className = img.className || '';
        
        if (src.includes('course') || className.includes('course') || alt.includes('course')) {
            return this.placeholderImages.course;
        }
        
        if (src.includes('product') || className.includes('product') || alt.includes('product')) {
            return this.placeholderImages.product;
        }
        
        if (src.includes('avatar') || className.includes('avatar') || className.includes('user')) {
            return this.placeholderImages.user;
        }
        
        return this.placeholderImages.default;
    }

    /**
     * Create fallback element
     */
    createFallbackElement(img) {
        const fallback = document.createElement('div');
        fallback.className = 'image-fallback';
        fallback.style.cssText = `
            display: none;
            background: #f3f4f6;
            color: #6b7280;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            font-size: 14px;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        
        const alt = img.alt || 'Image';
        fallback.innerHTML = `📷 ${alt}`;
        
        return fallback;
    }

    /**
     * Setup intersection observer for lazy loading
     */
    setupIntersectionObserver() {
        if (!('IntersectionObserver' in window)) return;

        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });

        // Observe images with data-src attribute
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    /**
     * Setup global error handling
     */
    setupErrorHandling() {
        // Global error handler for dynamically added images
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG') {
                this.handleImageError(e.target);
            }
        }, true);
    }

    /**
     * Add performance monitoring
     */
    addPerformanceMonitoring() {
        // Monitor image loading performance
        const observer = new PerformanceObserver((list) => {
            list.getEntries().forEach((entry) => {
                if (entry.name.includes('image') && entry.duration > 1000) {
                    console.warn(`Slow image load: ${entry.name} took ${entry.duration}ms`);
                }
            });
        });
        
        try {
            observer.observe({ entryTypes: ['resource'] });
        } catch (e) {
            // PerformanceObserver not supported
        }
    }

    /**
     * Preload critical images
     */
    preloadCriticalImages() {
        const criticalImages = [
            '/assets/default/img/logo.png',
            '/assets/default/img/favicon.ico'
        ];
        
        criticalImages.forEach(src => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = src;
            document.head.appendChild(link);
        });
    }

    /**
     * Convert images to WebP if supported
     */
    convertToWebP() {
        if (!this.supportsWebP()) return;
        
        const images = document.querySelectorAll('img[src$=".jpg"], img[src$=".png"]');
        images.forEach(img => {
            const webpSrc = img.src.replace(/\.(jpg|png)$/, '.webp');
            img.addEventListener('error', () => {
                // Fallback to original if WebP fails
                img.src = img.src;
            });
            img.src = webpSrc;
        });
    }

    /**
     * Check WebP support
     */
    supportsWebP() {
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new ImageOptimizer());
} else {
    new ImageOptimizer();
}

// Export for global use
window.ImageOptimizer = ImageOptimizer;
