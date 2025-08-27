@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("cart_page") }}">
    <link rel="stylesheet" href="https://unpkg.com/moyasar-payment-form@2.0.16/dist/moyasar.css" />
    <style>
        /* Moyasar Payment Form Styles */
        .moyasar-payment-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            max-height: 90vh;
            overflow-y: auto;
        }

        .moyasar-payment-container .payment-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .moyasar-payment-container .payment-header h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .moyasar-payment-container .payment-header p {
            color: #666;
            font-size: 16px;
            margin: 0;
        }

        .moyasar-payment-container .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .moyasar-payment-container .order-summary h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .moyasar-payment-container .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .moyasar-payment-container .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
            color: #28a745;
        }

        .moyasar-payment-container .mysr-form {
            margin-top: 20px;
        }

        .moyasar-payment-container .payment-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            color: #666;
            font-size: 14px;
        }

        .moyasar-payment-container .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .moyasar-payment-container .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .moyasar-payment-container .error-message {
            display: none;
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }

        .moyasar-payment-container .success-message {
            display: none;
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }

        /* Overlay for Moyasar form */
        .moyasar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9998;
        }

        /* Close button for Moyasar form */
        .moyasar-close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .moyasar-close-btn:hover {
            background: #c82333;
        }

        /* Payment Gateway Selection Styles */
        .payment-channel-card {
            margin-bottom: 16px;
        }

        .payment-channel-card input[type="radio"] {
            display: none;
        }

        .payment-channel-card .gateway-card {
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .payment-channel-card input[type="radio"]:checked + label .gateway-card {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .payment-channel-card .gateway-mask {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 123, 255, 0.1);
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 16px;
            z-index: 1;
        }

        .payment-channel-card input[type="radio"]:checked + label .gateway-mask {
            opacity: 1;
        }

        .payment-channel-card input[type="radio"]:disabled + label .gateway-card {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
@endpush

@section("content")
    <section class="container mt-56 mb-80 position-relative">
        <div class="d-flex-center flex-column text-center">
            <h1 class="font-32">{{ trans('update.cart') }}</h1>
            <p class="mt-8 font-16 text-gray-500">{{ handlePrice($calculatePrices["sub_total"], true, true, false, null, true) . ' ' . trans('cart.for_items',['count' => $carts->count()]) }}</p>
        </div>

        <form action="{{ route('checkout') }}" method="post" id="cartForm">
            {{ csrf_field() }}

            <div class="row mb-160">
                {{-- Items --}}
                <div class="col-12 col-md-7 col-lg-9 mt-32">

                    {{-- CashBack --}}
                    @if(!empty($totalCashbackAmount))
                        @include('design_1.web.cart.overview.includes.cashback_alert')
                    @endif

                    @if(!empty($userGroup) and !empty($userGroup->discount))
                        @include('design_1.web.cart.overview.includes.user_group_discount')
                    @endif

                    <div class="card-with-mask position-relative">
                        <div class="mask-8-white"></div>

                        <div class="position-relative z-index-2 bg-white rounded-16 py-16">
                            {{-- Items --}}
                            @include('design_1.web.cart.overview.includes.cart_items')

                            @if($hasPhysicalProduct)
                                @include('design_1.web.cart.overview.includes.shipping_and_delivery')
                            @endif

                        </div>
                    </div>

                    {{-- Coupon --}}
                    @include('design_1.web.cart.overview.includes.coupon')
                </div>

                {{-- Right Side --}}
                <div class="col-12 col-md-5 col-lg-3 mt-32">
                    <div class="cart-right-side-section">
                        {{-- Summary --}}
                        <div class="js-cart-summary-container">
                            @include('design_1.web.cart.overview.includes.summary')
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </section>

    {{-- Moyasar Payment Form --}}
    @if(session('moyasar') || (!empty($moyasar) and $moyasar))
        <!-- Overlay -->
        <div id="moyasar-overlay" class="moyasar-overlay"></div>

        <div id="moyasar-payment-form" class="moyasar-payment-container" style="display: none;">
            <button type="button" class="moyasar-close-btn" onclick="closeMoyasarForm()">&times;</button>

            <div class="payment-header">
                <h2>💳 Complete Your Payment</h2>
                <p>Secure payment powered by Moyasar</p>
            </div>

            <div class="order-summary">
                <h4>📋 Order Summary</h4>
                <div class="summary-row">
                    <span>Total Amount:</span>
                    <span>{{ handlePrice($calculatePrices["sub_total"], true, true, false, null, true) }}</span>
                </div>
                <div class="summary-row">
                    <span>Items:</span>
                    <span>{{ $carts->count() }} item(s)</span>
                </div>
                <div class="summary-row">
                    <span>Description:</span>
                    <span>Cart Payment</span>
                </div>
            </div>

            <!-- Error Message -->
            <div id="moyasar-error-message" class="error-message"></div>

            <!-- Success Message -->
            <div id="moyasar-success-message" class="success-message"></div>

            <!-- Moyasar Payment Form -->
            <div class="mysr-form"></div>

            <!-- Loading Spinner -->
            <div id="moyasar-loading-spinner" class="loading-spinner">
                <div class="spinner"></div>
                <p>Processing your payment...</p>
            </div>

            <div class="payment-footer">
                <p>🔒 Your payment information is encrypted and secure</p>
                <p>Supported: Visa, Mastercard, Mada</p>
            </div>
        </div>
    @endif
@endsection

@push('scripts_bottom')
    <script src="https://unpkg.com/moyasar-payment-form@2.0.16/dist/moyasar.umd.js"></script>
    <script>
        var selectRegionDefaultVal = '';
        var selectStateLang = '{{ trans('update.choose_a_state') }}';
        var selectCityLang = '{{ trans('update.choose_a_city') }}';
        var selectDistrictLang = '{{ trans('update.all_districts') }}';
        var couponLang = '{{ trans('update.coupon') }}';
        var enterCouponLang = '{{ trans('update.please_enter_your_discount_code') }}';
        var removeCouponTitleLang = '{{ trans('update.remove_coupon_title') }}';
        var removeCouponHintLang = '{{ trans('update.remove_coupon_massage_hint') }}';
        var cancelLang = '{{ trans('public.cancel') }}';
        var removeLang = '{{ trans('public.remove') }}';
        var hasErrors = '{{ (!empty($errors) and count($errors)) ? 'true' : 'false' }}';
        var hasErrorsHintLang = '{{ trans('update.please_check_the_errors_in_the_shipping_form') }}';

        // Moyasar payment form handling
        var moyasarInitialized = false;
        var moyasarFormData = null;

        // Bootstrap from session (when controller flashed Moyasar data)
        var moyasarFormDataFromSession = {!! json_encode(session('moyasar_form_data')) !!};
        if (moyasarFormDataFromSession) {
            moyasarFormData = moyasarFormDataFromSession;
            // Ensure modal elements exist before showing
            document.addEventListener('DOMContentLoaded', function() {
                showMoyasarForm();
            });
        }

        // Function to show Moyasar payment form
        function showMoyasarForm() {
            document.getElementById('moyasar-overlay').style.display = 'block';
            document.getElementById('moyasar-payment-form').style.display = 'block';

            // Initialize Moyasar form if not already done
            if (!moyasarInitialized && moyasarFormData) {
                initializeMoyasarForm();
            }
        }

        // Function to close Moyasar payment form
        function closeMoyasarForm() {
            document.getElementById('moyasar-overlay').style.display = 'none';
            document.getElementById('moyasar-payment-form').style.display = 'none';
        }

        // Function to initialize Moyasar form
        function initializeMoyasarForm() {
            if (moyasarInitialized) return;

            try {
                Moyasar.init({
                    element: '.mysr-form',
                    amount: moyasarFormData.amount,
                    currency: moyasarFormData.currency,
                    description: moyasarFormData.description,
                    publishable_api_key: moyasarFormData.publishable_api_key,
                    callback_url: moyasarFormData.callback_url,
                    back_url: moyasarFormData.back_url,
                    supported_networks: moyasarFormData.supported_networks,
                    methods: moyasarFormData.methods,
                    on_completed: async function (payment) {
                        console.log('Moyasar payment completed:', payment);
                        showMoyasarSuccessMessage('Payment completed successfully! Verifying payment...');

                        // Show loading spinner during verification
                        showMoyasarLoadingSpinner();

                        try {
                            // Save payment ID to backend
                            await saveMoyasarPaymentOnBackend(payment);

                            // Hide loading spinner
                            hideMoyasarLoadingSpinner();

                            // Show final success message
                            showMoyasarSuccessMessage('Payment verified successfully! Redirecting to dashboard...');

                            // Close the payment form
                            setTimeout(() => {
                                closeMoyasarForm();
                            }, 1000);

                            // Redirect to dashboard after successful payment
                            setTimeout(() => {
                                window.location.href = '/panel';
                            }, 3000);
                        } catch (error) {
                            console.error('Error during payment verification:', error);
                            hideMoyasarLoadingSpinner();
                            showMoyasarErrorMessage('Payment verification failed. Please contact support.');
                        }
                    },
                    on_failed: function (payment) {
                        console.log('Moyasar payment failed:', payment);
                        showMoyasarErrorMessage('Payment failed. Please try again.');
                    },
                    on_ready: function () {
                        console.log('Moyasar form is ready');
                        hideMoyasarLoadingSpinner();
                    }
                });

                moyasarInitialized = true;
                console.log('Moyasar form initialized successfully');

            } catch (error) {
                console.error('Error initializing Moyasar form:', error);
                showMoyasarErrorMessage('Error initializing payment form. Please refresh and try again.');
            }
        }

        // Function to save Moyasar payment on backend
        async function saveMoyasarPaymentOnBackend(payment) {
            try {
                console.log('Saving Moyasar payment to backend:', payment);

                const response = await fetch('/payments/verify/Moyasar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        id: payment.id,
                        status: payment.status,
                        message: payment.message || 'Payment completed'
                    })
                });

                if (response.ok) {
                    const result = await response.json();
                    console.log('Moyasar payment saved to backend successfully:', result);

                    // Update success message with more details
                    showMoyasarSuccessMessage('Payment verified successfully! Redirecting to dashboard...');
                } else {
                    console.error('Failed to save Moyasar payment to backend');
                    showMoyasarErrorMessage('Payment verification failed. Please contact support.');
                }
            } catch (error) {
                console.error('Error saving Moyasar payment to backend:', error);
                showMoyasarErrorMessage('Error verifying payment. Please contact support.');
            }
        }

        // Function to show Moyasar error message
        function showMoyasarErrorMessage(message) {
            const errorDiv = document.getElementById('moyasar-error-message');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';

            // Hide success message if visible
            document.getElementById('moyasar-success-message').style.display = 'none';
        }

        // Function to show Moyasar success message
        function showMoyasarSuccessMessage(message) {
            const successDiv = document.getElementById('moyasar-success-message');
            successDiv.textContent = message;
            successDiv.style.display = 'block';

            // Hide error message if visible
            document.getElementById('moyasar-error-message').style.display = 'none';
        }

        // Function to show Moyasar loading spinner
        function showMoyasarLoadingSpinner() {
            document.getElementById('moyasar-loading-spinner').style.display = 'block';
        }

        // Function to hide Moyasar loading spinner
        function hideMoyasarLoadingSpinner() {
            document.getElementById('moyasar-loading-spinner').style.display = 'none';
        }

        // Show loading spinner initially
        showMoyasarLoadingSpinner();

        // Close overlay when clicking on it
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('moyasar-overlay');
            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        closeMoyasarForm();
                    }
                });
            }

            // Override form submission for Moyasar
            const form = document.getElementById('cartForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const selectedGateway = document.querySelector('input[name="gateway"]:checked');
                    if (selectedGateway && selectedGateway.dataset.class === 'Moyasar') {
                        e.preventDefault();

                        // Create order and get Moyasar form data
                        createOrderForMoyasar();
                    }
                });
            }
        });

        // Function to create order and get Moyasar form data
        async function createOrderForMoyasar() {
            try {
                // First create the order
                const formData = new FormData(document.getElementById('cartForm'));
                const response = await fetch('/cart/checkout', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.order_id) {
                        // Now get Moyasar form data
                        await getMoyasarFormData(result.order_id);
                    } else {
                        alert('Error creating order: ' + (result.message || 'Unknown error'));
                    }
                } else {
                    alert('Error creating order. Please try again.');
                }
            } catch (error) {
                console.error('Error creating order:', error);
                alert('Error creating order. Please try again.');
            }
        }

        // Function to get Moyasar form data
        async function getMoyasarFormData(orderId) {
            try {
                const selectedGateway = document.querySelector('input[name="gateway"]:checked');
                const response = await fetch('/payments/moyasar-form-data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        gateway: selectedGateway.value
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        moyasarFormData = data.payment_form_data;
                        showMoyasarForm();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } else {
                    alert('Error loading payment form. Please try again.');
                }
            } catch (error) {
                console.error('Error fetching Moyasar form data:', error);
                alert('Error loading payment form. Please try again.');
            }
        }
    </script>

    <script src="{{ getDesign1ScriptPath("get_regions") }}"></script>
    <script src="{{ getDesign1ScriptPath("cart_page") }}"></script>
@endpush
