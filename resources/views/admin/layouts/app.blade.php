<html lang="{{ app()->getLocale() }}">
@php
    $rtlLanguages = !empty($generalSettings['rtl_languages']) ? $generalSettings['rtl_languages'] : [];
    $isRtl = ((in_array(mb_strtoupper(app()->getLocale()), $rtlLanguages)) or (!empty($generalSettings['rtl_layout']) and $generalSettings['rtl_layout'] == 1));
    $themeCustomCssAndJs = getThemeCustomCssAndJs();
@endphp
<head>
    @include('design_1.web.includes.metas')
    <title>{{ $pageTitle ?? '' }} </title>

    <!-- General CSS File -->
    <link rel="stylesheet" href="/assets/admin/vendor/bootstrap/bootstrap.min.css"/>
    <link rel="stylesheet" href="/assets/vendors/fontawesome/css/all.min.css"/>
    <link rel="stylesheet" href="/assets/default/vendors/toast/jquery.toast.min.css">


    @stack('libraries_top')

    <link rel="stylesheet" href="/assets/admin/css/style.css">
    <link rel="stylesheet" href="/assets/admin/css/custom.css">
    <link rel="stylesheet" href="/assets/admin/css/components.css">
    <link rel="stylesheet" href="/assets/admin/css/extra.min.css">
    @if($isRtl)
        <link rel="stylesheet" href="/assets/admin/css/rtl.css">
    @endif
    <link rel="stylesheet" href="/assets/admin/vendor/daterangepicker/daterangepicker.min.css">
    <link rel="stylesheet" href="/assets/default/vendors/select2/select2.min.css">

    @stack('styles_top')
    @stack('scripts_top')

    <style>
        {!! !empty($themeCustomCssAndJs['css']) ? $themeCustomCssAndJs['css'] : '' !!}

        {!! getThemeFontsSettings() !!}

        {!! getThemeColorsSettings(true) !!}

        /* Image Error Handling CSS */
        img {
            /* Prevent broken images from affecting layout */
            max-width: 100%;
            height: auto;
        }

        /* Hide broken images gracefully */
        img:not([src]),
        img[src=""],
        img[src*="data:image/svg+xml;base64,"] {
            display: none;
        }

        /* Optional: Add placeholder for broken images */
        img[src*="404"]::before,
        img[src*="error"]::before {
            content: "⚠️ Image not found";
            display: block;
            padding: 10px;
            background: #f8f9fa;
            border: 1px dashed #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
        }

        /* Performance: Reduce repaints for images */
        img {
            will-change: auto;
            transform: translateZ(0);
        }

        /* Lazy loading placeholder */
        img[data-src] {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        img[data-src].loaded {
            opacity: 1;
        }
    </style>
</head>
<body class="{{ $isRtl ? 'rtl' : '' }}">

<div id="app">
    <div class="main-wrapper">
        @include('admin.includes.header.index')

        @include('admin.includes.sidebar.index')


        <div class="main-content">

            @yield('content')

        </div>
    </div>

    <div class="modal fade" id="fileViewModal" tabindex="-1" aria-labelledby="fileViewModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <img src="" class="img-fluid" alt="">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('public.close') }}</button>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- AI Contents --}}
@if(!empty(getAiContentsSettingsName("status")) and !empty(getAiContentsSettingsName("active_for_admin_panel")))
    @include('admin.includes.aiContent.generator')
@endif

<script>
    window.adminPanelPrefix = '{{ getAdminPanelUrl() }}';
</script>

<!-- General JS Scripts -->
<script src="/assets/admin/vendor/jquery/jquery-3.3.1.min.js"></script>

<script>
    // Image Error Handling - Prevents 404 errors from slowing down the page
    (function() {
        'use strict';

        // Configuration from Laravel
        const config = {
            handle404Errors: {{ config('image-optimization.handle_404_errors', true) ? 'true' : 'false' }},
            enableLazyLoading: {{ config('image-optimization.enable_lazy_loading', true) ? 'true' : 'false' }},
            showPlaceholders: {{ config('image-optimization.show_placeholders', false) ? 'true' : 'false' }},
            loadingTimeout: {{ config('image-optimization.loading_timeout', 5000) }},
            retryAttempts: {{ config('image-optimization.retry_attempts', 1) }},
            consoleLogLevel: '{{ config('image-optimization.console_log_level', 'warn') }}'
        };

        // Console logging function
        function logImage(message, level = 'info') {
            if (config.consoleLogLevel === 'none') return;

            const levels = ['info', 'warn', 'error'];
            const currentLevel = levels.indexOf(level);
            const configLevel = levels.indexOf(config.consoleLogLevel);

            if (currentLevel >= configLevel) {
                console[level]('Image Optimization:', message);
            }
        }

        // Handle image errors
        function handleImageError(img, retryCount = 0) {
            if (retryCount < config.retryAttempts) {
                logImage(`Retrying image load (${retryCount + 1}/${config.retryAttempts}): ${img.src}`, 'warn');

                setTimeout(() => {
                    img.src = img.src; // Retry loading
                }, 1000 * (retryCount + 1));

                return;
            }

            logImage(`Image failed to load after ${config.retryAttempts} attempts: ${img.src}`, 'warn');

            if (config.showPlaceholders) {
                // Show placeholder
                img.src = '{{ config('image-optimization.default_placeholder') }}';
                img.alt = 'Image not found';
                img.classList.add('image-placeholder');
            } else {
                // Hide broken image
                $(img).hide();
            }
        }

        if (config.handle404Errors) {
            // Handle existing images
            $(document).ready(function() {
                $('img').each(function() {
                    $(this).on('error', function() {
                        handleImageError(this);
                    });
                });
            });

            // Handle dynamically added images
            $(document).on('error', 'img', function() {
                handleImageError(this);
            });

            // Handle SVG images specifically
            $(document).on('error', 'img[src*=".svg"], img[src*="svg"]', function() {
                logImage(`SVG failed to load: ${this.src}`, 'warn');
                handleImageError(this);
            });
        }

        // Performance optimization: Lazy load images
        if (config.enableLazyLoading && 'IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            // Set loading timeout
                            const timeoutId = setTimeout(() => {
                                logImage(`Image loading timeout: ${img.dataset.src}`, 'warn');
                                handleImageError(img);
                            }, config.loadingTimeout);

                            img.onload = () => {
                                clearTimeout(timeoutId);
                                img.classList.add('loaded');
                                logImage(`Image loaded successfully: ${img.dataset.src}`, 'info');
                            };

                            img.onerror = () => {
                                clearTimeout(timeoutId);
                                handleImageError(img);
                            };

                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    }
                });
            });

            // Observe all images with data-src attribute
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });

            logImage('Lazy loading enabled', 'info');
        }

        // Performance monitoring
        if (config.consoleLogLevel !== 'none') {
            // Monitor image loading performance
            window.addEventListener('load', () => {
                const images = document.querySelectorAll('img');
                const loadedImages = Array.from(images).filter(img => img.complete && img.naturalHeight !== 0);
                const failedImages = Array.from(images).filter(img => !img.complete || img.naturalHeight === 0);

                logImage(`Page load complete. Images: ${loadedImages.length} loaded, ${failedImages.length} failed`, 'info');

                if (failedImages.length > 0) {
                    logImage('Failed images:', 'warn');
                    failedImages.forEach(img => {
                        logImage(`- ${img.src}`, 'warn');
                    });
                }
            });
        }

        logImage('Image optimization system loaded', 'info');
    })();
</script>

<script src="/assets/admin/vendor/poper/popper.min.js"></script>
<script src="/assets/admin/vendor/bootstrap/bootstrap.min.js"></script>
<script src="/assets/admin/vendor/nicescroll/jquery.nicescroll.min.js"></script>
<script src="/assets/admin/vendor/moment/moment.min.js"></script>
<script src="/assets/admin/js/stisla.js"></script>
<script src="/assets/default/vendors/toast/jquery.toast.min.js"></script>


<script src="/assets/admin/vendor/daterangepicker/daterangepicker.min.js"></script>
<script src="/assets/default/vendors/select2/select2.min.js"></script>

<script src="/vendor/laravel-filemanager/js/stand-alone-button.js"></script>
<!-- Template JS File -->
<script src="/assets/admin/js/scripts.js"></script>


<script src="/assets/admin/js/admin.min.js"></script>



@stack('styles_bottom')
@stack('scripts_bottom')

<script>
    (function () {
        "use strict";

        @if(session()->has('toast'))
        showToast('{{ session()->get('toast')['status'] }}', '{{ session()->get('toast')['title'] ?? '' }}', '{{ session()->get('toast')['msg'] ?? '' }}')
        @endif
    })(jQuery);


    var siteDomain = '{{ url('') }}';
    var deleteAlertTitle = '{{ trans('public.are_you_sure') }}';
    var deleteAlertHint = '{{ trans('public.deleteAlertHint') }}';
    var deleteAlertConfirm = '{{ trans('public.deleteAlertConfirm') }}';
    var deleteAlertCancel = '{{ trans('public.cancel') }}';
    var deleteAlertSuccess = '{{ trans('public.success') }}';
    var deleteAlertFail = '{{ trans('public.fail') }}';
    var deleteAlertFailHint = '{{ trans('public.deleteAlertFailHint') }}';
    var deleteAlertSuccessHint = '{{ trans('public.deleteAlertSuccessHint') }}';
    var forbiddenRequestToastTitleLang = '{{ trans('public.forbidden_request_toast_lang') }}';
    var forbiddenRequestToastMsgLang = '{{ trans('public.forbidden_request_toast_msg_lang') }}';
    var generatedContentLang = '{{ trans('update.generated_content') }}';
    var copyLang = '{{ trans('public.copy') }}';
    var doneLang = '{{ trans('public.done') }}';
    var priceInvalidHintLang = '{{ trans('update.price_invalid_hint') }}';
</script>

<script src="/assets/admin/js/custom.js"></script>
<script src="/assets/admin/js/parts/ai-content-generator.min.js"></script>

<script>
    {!! !empty($themeCustomCssAndJs['js']) ? $themeCustomCssAndJs['js'] : '' !!}
</script>
</body>
</html>
