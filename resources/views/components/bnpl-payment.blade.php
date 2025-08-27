@props(['price', 'vatPercentage' => 15, 'providers' => null])

@php
    if (!$providers) {
        $providers = \App\Models\BnplProvider::active()->get();
    }

    $priceWithVat = $price * (1 + ($vatPercentage / 100));
@endphp

@if($providers->count() > 0)
    <div class="bnpl-payment-options">
        <h6 class="text-muted mb-3">
            <i class="fas fa-credit-card mr-2"></i>
            Pay in Installments
        </h6>

        <div class="row">
            @foreach($providers as $provider)
                @php
                    $installmentAmount = $provider->calculateInstallmentAmount($price, $vatPercentage);
                    $totalWithFee = $priceWithVat * (1 + ((float) $provider->fee_percentage / 100));
                @endphp

                <div class="col-md-6 mb-3">
                    <div class="bnpl-option-card">
                        <div class="d-flex align-items-center mb-2">
                            @if($provider->logo_path)
                                <img src="{{ $provider->logo_url }}" alt="{{ $provider->name }}" class="provider-logo mr-2">
                            @else
                                <div class="provider-logo-placeholder mr-2">{{ substr($provider->name, 0, 1) }}</div>
                            @endif
                            <h6 class="mb-0">{{ $provider->name }}</h6>
                        </div>

                        <div class="installment-details">
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block">Monthly</small>
                                    <strong class="text-primary">{{ number_format($installmentAmount, 2) }} SAR</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Duration</small>
                                    <strong>{{ $provider->installment_count }} months</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Total</small>
                                    <strong>{{ number_format($totalWithFee, 2) }} SAR</strong>
                                </div>
                            </div>

                            @if($provider->fee_percentage > 0)
                                <div class="fee-notice mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        +{{ $provider->fee_percentage }}% fee applied
                                    </small>
                                </div>
                            @endif
                        </div>

                        <button class="btn btn-outline-primary btn-sm btn-block mt-2"
                                onclick="selectBnplProvider('{{ $provider->id }}', '{{ $provider->name }}')">
                            Choose {{ $provider->name }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-3">
            <small class="text-muted">
                <i class="fas fa-shield-alt"></i>
                Secure payment powered by {{ $providers->pluck('name')->implode(', ') }}
            </small>
        </div>
    </div>
@endif

<style>
.bnpl-payment-options {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.bnpl-option-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    transition: all 0.2s ease;
}

.bnpl-option-card:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0,123,255,0.1);
}

.provider-logo {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.provider-logo-placeholder {
    width: 32px;
    height: 32px;
    background: #007bff;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.installment-details {
    border-top: 1px solid #dee2e6;
    padding-top: 15px;
}

.fee-notice {
    border-top: 1px solid #f8f9fa;
    padding-top: 10px;
}
</style>

<script>
function selectBnplProvider(providerId, providerName) {
    // This function would typically integrate with your payment system
    // For now, we'll just show an alert
    alert(`Selected ${providerName} for installment payment. Provider ID: ${providerId}`);

    // You could also:
    // - Update a hidden form field
    // - Redirect to payment page
    // - Show payment form
    // - etc.
}
</script>

