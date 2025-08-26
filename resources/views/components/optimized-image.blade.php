@if($optimized)
    {{-- Optimized Image with Fallback --}}
    <div class="optimized-image-container">
        <img 
            src="{{ $src }}" 
            alt="{{ $alt }}"
            @if($class) class="{{ $class }}" @endif
            @if($width) width="{{ $width }}" @endif
            @if($height) height="{{ $height }}" @endif
            @foreach($attributes as $key => $value)
                {{ $key }}="{{ $value }}"
            @endforeach
        >
        
        @if($fallback)
            <div class="image-fallback" style="display: none;">
                @if($placeholder)
                    <div class="placeholder-content">
                        <div class="placeholder-icon">📷</div>
                        <div class="placeholder-text">{{ $alt ?: 'Image' }}</div>
                    </div>
                @else
                    <div class="fallback-text">{{ $alt ?: 'Image not available' }}</div>
                @endif
            </div>
        @endif
    </div>
@else
    {{-- Basic Image without Optimization --}}
    <img 
        src="{{ $src }}" 
        alt="{{ $alt }}"
        @if($class) class="{{ $class }}" @endif
        @if($width) width="{{ $width }}" @endif
        @if($height) height="{{ $height }}" @endif
    >
@endif

<style>
.optimized-image-container {
    position: relative;
    display: inline-block;
}

.image-fallback {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #f3f4f6;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #6b7280;
    font-size: 14px;
}

.placeholder-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.placeholder-icon {
    font-size: 24px;
    opacity: 0.7;
}

.placeholder-text {
    font-weight: 500;
}

.fallback-text {
    font-size: 12px;
    opacity: 0.8;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .image-fallback {
        font-size: 12px;
        padding: 15px;
    }
    
    .placeholder-icon {
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .image-fallback {
        font-size: 11px;
        padding: 10px;
    }
    
    .placeholder-icon {
        font-size: 18px;
    }
}
</style>
