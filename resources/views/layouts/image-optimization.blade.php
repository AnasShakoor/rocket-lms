{{-- Image Optimization Layout Includes --}}
@if(config('image-optimization.enabled', true))

    {{-- CSS for Image Optimization --}}
    <link rel="stylesheet" href="{{ asset('assets/css/image-optimization.css') }}">

    {{-- JavaScript for Image Optimization --}}
    <script src="{{ asset('assets/js/image-optimizer.js') }}" defer></script>

    {{-- Preload critical images --}}
    @if(config('image-optimization.performance.preload_critical', true))
        @foreach(config('image-optimization.critical_images', []) as $criticalImage)
            <link rel="preload" href="{{ asset($criticalImage) }}" as="image">
        @endforeach
    @endif

    {{-- Inline critical CSS for immediate loading --}}
    <style>
        /* Critical image optimization styles */
        .image-fallback {
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
            border: 2px dashed #d1d5db;
        }
        
        .img-loading {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Responsive image containers */
        .img-container {
            position: relative;
            overflow: hidden;
            background: #f9fafb;
        }
        
        .img-container::before {
            content: "";
            display: block;
            padding-top: 56.25%; /* 16:9 aspect ratio */
        }
        
        .img-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>

@endif
