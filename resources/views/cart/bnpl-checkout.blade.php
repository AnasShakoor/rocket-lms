@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>🛒 Shopping Cart</h3>
                </div>
                <div class="card-body">
                    @if($cartItems && $cartItems->count() > 0)
                        <div class="cart-items">
                            @foreach($cartItems as $item)
                                <div class="cart-item d-flex justify-content-between align-items-center p-3 border-bottom">
                                    <div class="item-details">
                                        <h5 class="mb-1">{{ $item->title ?? 'Course' }}</h5>
                                        <p class="text-muted mb-0">{{ $item->description ?? 'Course description' }}</p>
                                    </div>
                                    <div class="item-price">
                                        <span class="h5 text-primary">${{ number_format($item->price, 2) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="cart-summary mt-4">
                            <div class="d-flex justify-content-between">
                                <span class="h5">Subtotal:</span>
                                <span class="h5">${{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>VAT (15%):</span>
                                <span>${{ number_format($vat, 2) }}</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="h4">Total:</span>
                                <span class="h4 text-primary">${{ number_format($total, 2) }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h4>Your cart is empty</h4>
                            <p class="text-muted">Add some courses to get started!</p>
                            <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse Courses</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            @if($cartItems && $cartItems->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h4>💳 Payment Options</h4>
                    </div>
                    <div class="card-body">
                        <!-- Payment Method Selection -->
                        <div class="mb-4">
                            <label class="form-label">Payment Method</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                <label class="form-check-label" for="credit_card">
                                    <i class="fas fa-credit-card text-primary"></i> Credit Card
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="bnpl" value="bnpl">
                                <label class="form-check-label" for="bnpl">
                                    <i class="fas fa-clock text-success"></i> Buy Now, Pay Later
                                </label>
                            </div>
                        </div>
                        
                        <!-- BNPL Options (Hidden by default) -->
                        <div id="bnpl-options" class="mb-4" style="display: none;">
                            <label class="form-label">BNPL Provider</label>
                            <select id="bnpl-provider" class="form-control mb-3">
                                <option value="">Select Provider</option>
                                @foreach($bnplProviders ?? [] as $provider)
                                    <option value="{{ $provider->name }}" 
                                            data-installments="{{ $provider->installment_count }}"
                                            data-fee="{{ $provider->fee_percentage }}">
                                        {{ $provider->name }} ({{ $provider->installment_count }} installments)
                                    </option>
                                @endforeach
                            </select>
                            
                            <div id="bnpl-breakdown" class="bg-light p-3 rounded" style="display: none;">
                                <h6>Payment Breakdown</h6>
                                <div class="d-flex justify-content-between">
                                    <span>Base Amount:</span>
                                    <span>${{ number_format($subtotal, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>VAT:</span>
                                    <span>${{ number_format($vat, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>BNPL Fee:</span>
                                    <span id="bnpl-fee">$0.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold">Total:</span>
                                    <span class="fw-bold" id="bnpl-total">${{ number_format($total, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Installments:</span>
                                    <span id="installment-count">-</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Monthly Payment:</span>
                                    <span id="monthly-payment">-</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Checkout Button -->
                        <button id="checkout-btn" class="btn btn-primary btn-lg btn-block w-100" onclick="processCheckout()">
                            <i class="fas fa-lock"></i> Secure Checkout
                        </button>
                        
                        <!-- Security Notice -->
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> Your payment information is secure and encrypted
                            </small>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Payment Processing Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Processing Payment</h5>
            </div>
            <div class="modal-body text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Please wait while we process your payment...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment method toggle
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const bnplOptions = document.getElementById('bnpl-options');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'bnpl') {
                bnplOptions.style.display = 'block';
            } else {
                bnplOptions.style.display = 'none';
                document.getElementById('bnpl-breakdown').style.display = 'none';
            }
        });
    });
    
    // BNPL provider selection
    const bnplProvider = document.getElementById('bnpl-provider');
    bnplProvider.addEventListener('change', function() {
        if (this.value) {
            updateBnplBreakdown();
        } else {
            document.getElementById('bnpl-breakdown').style.display = 'none';
        }
    });
});

function updateBnplBreakdown() {
    const provider = document.getElementById('bnpl-provider');
    const selectedOption = provider.options[provider.selectedIndex];
    
    if (!selectedOption.value) return;
    
    const installments = parseInt(selectedOption.dataset.installments);
    const feePercentage = parseFloat(selectedOption.dataset.fee);
    
    const baseAmount = {{ $subtotal ?? 0 }};
    const vat = {{ $vat ?? 0 }};
    const bnplFee = (baseAmount + vat) * (feePercentage / 100);
    const total = baseAmount + vat + bnplFee;
    const monthlyPayment = total / installments;
    
    // Update breakdown display
    document.getElementById('bnpl-fee').textContent = '$' + bnplFee.toFixed(2);
    document.getElementById('bnpl-total').textContent = '$' + total.toFixed(2);
    document.getElementById('installment-count').textContent = installments;
    document.getElementById('monthly-payment').textContent = '$' + monthlyPayment.toFixed(2);
    
    document.getElementById('bnpl-breakdown').style.display = 'block';
}

function processCheckout() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const bnplProvider = document.getElementById('bnpl-provider').value;
    
    // Validate BNPL selection
    if (paymentMethod === 'bnpl' && !bnplProvider) {
        alert('Please select a BNPL provider');
        return;
    }
    
    // Show processing modal
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
    
    // Prepare checkout data
    const checkoutData = {
        payment_method: paymentMethod,
        bnpl_provider: paymentMethod === 'bnpl' ? bnplProvider : null,
        _token: '{{ csrf_token() }}'
    };
    
    // Process checkout
    fetch('{{ route("cart.checkout") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(checkoutData)
    })
    .then(response => response.json())
    .then(data => {
        modal.hide();
        
        if (data.success) {
            // Redirect to success page
            window.location.href = data.redirect_url || '{{ route("checkout.success") }}';
        } else {
            alert('Checkout failed: ' + data.error);
        }
    })
    .catch(error => {
        modal.hide();
        console.error('Checkout error:', error);
        alert('An error occurred during checkout. Please try again.');
    });
}
</script>
@endpush

<style>
.cart-item {
    transition: background-color 0.2s;
}

.cart-item:hover {
    background-color: #f8f9fa;
}

.cart-summary {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
}

#bnpl-options {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    background-color: #f8f9fa;
}

#bnpl-breakdown {
    background-color: #e9ecef !important;
}
</style>
@endsection

