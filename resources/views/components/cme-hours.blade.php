@props(['hours', 'showLabel' => true])

@if($hours && $hours > 0)
    <div class="cme-hours-badge d-inline-flex align-items-center">
        <i class="fas fa-certificate text-primary mr-2"></i>
        @if($showLabel)
            <span class="font-weight-bold">{{ number_format($hours, 1) }} CME Hours</span>
        @else
            <span class="font-weight-bold">{{ number_format($hours, 1) }}</span>
        @endif
    </div>
@endif

<style>
.cme-hours-badge {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 0.875rem;
    color: #495057;
    transition: all 0.2s ease;
}

.cme-hours-badge:hover {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.cme-hours-badge i {
    font-size: 0.75rem;
    margin-right: 6px;
}
</style>

