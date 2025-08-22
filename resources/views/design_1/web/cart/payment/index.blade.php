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
    </style>
@endpush

@php
    $isMultiCurrency = !empty(getFinancialCurrencySettings('multi_currency'));
    $userCurrency = currency();
    $invalidChannels = [];
@endphp

@section("content")
    <section class="container my-56 position-relative">
        <div class="d-flex-center flex-column text-center">
            <h1 class="font-32">{{ trans('update.checkout') }}</h1>
            <p class="mt-8 font-16 text-gray-500">{{ handlePrice($calculatePrices["total"], true, true, false, null, true) . ' ' . trans('cart.for_items',['count' => $count]) }}</p>
        </div>

        <form action="/payments/payment-request" method="post">
            {{ csrf_field() }}
            <input type="hidden" name="order_id" value="{{ $order->id }}">

            <div class="row">
                {{-- Items --}}
                <div class="col-12 col-md-7 col-lg-9 mt-32">

                    {{-- CashBack --}}
                    @if(!empty($totalCashbackAmount))
                        @include('design_1.web.cart.overview.includes.cashback_alert')
                    @endif

                    <div class="card-with-mask position-relative">
                        <div class="mask-8-white"></div>

                        <div class="position-relative z-index-2 bg-white rounded-16 py-16">
                            <div class="card-before-line px-16">
                                <h3 class="font-14">{{ trans('update.select_a_payment_gateway') }}</h3>
                            </div>

                            <div class="d-grid grid-columns-2 grid-lg-columns-3 gap-24 px-16 mt-16">
                                @if(!empty($paymentChannels))
                                    @foreach($paymentChannels as $paymentChannel)
                                        @if(!$isMultiCurrency or (!empty($paymentChannel->currencies) and in_array($userCurrency, $paymentChannel->currencies)))
                                            <div class="payment-channel-card position-relative">
                                                <input type="radio" name="gateway" id="gateway_{{ $paymentChannel->id }}" data-class="{{ $paymentChannel->class_name }}" value="{{ $paymentChannel->id }}">
                                                <label class="position-relative w-100 d-block cursor-pointer" for="gateway_{{ $paymentChannel->id }}">
                                                    <div class="gateway-mask"></div>
                                                    <div class="gateway-card position-relative z-index-2 d-flex-center flex-column rounded-16 bg-white w-100 h-100 text-center">
                                                        <div class="d-flex-center size-48 bg-gray-100">
                                                            <img src="{{ $paymentChannel->image }}" alt="" class="img-fluid">
                                                        </div>
                                                        <h6 class="font-14 mt-12">{{ $paymentChannel->title }}</h6>
                                                    </div>
                                                </label>
                                            </div>
                                        @else
                                            @php
                                                $invalidChannels[] = $paymentChannel;
                                            @endphp
                                        @endif
                                    @endforeach
                                @endif

                                <div class="payment-channel-card position-relative">
                                    <input type="radio" name="gateway" id="gateway_credit" value="credit" {{ (empty($userCharge) or ($calculatePrices["total"] > $userCharge)) ? 'disabled' : '' }}>
                                    <label class="position-relative w-100 d-block cursor-pointer" for="gateway_credit">
                                        <div class="gateway-mask"></div>
                                        <div class="gateway-card position-relative z-index-2 d-flex-center flex-column rounded-16 bg-white w-100 h-100 text-center">
                                            <div class="d-flex-center size-48 bg-gray-100">
                                                <x-iconsax-bul-empty-wallet class="icons text-dark" width="48px" height="48px"/>
                                            </div>
                                            <h6 class="font-14 mt-12">{{ trans('financial.account_charge') }}</h6>
                                            <p class="mt-4 font-12 text-gray-500">{{ handlePrice($userCharge) }}</p>
                                        </div>
                                    </label>
                                </div>
                            </div>


                            @if(!empty($invalidChannels) and empty(getFinancialSettings("hide_disabled_payment_gateways")))
                                <div class="px-16 mt-28">
                                    {{-- Alert --}}
                                    <div class="position-relative pl-8">
                                        <div class="d-flex align-items-center p-12 rounded-12 bg-gray-500-20">
                                            <div class="alert-left-20 d-flex-center size-48 bg-gray-500 rounded-12">
                                                <x-iconsax-bol-info-circle class="icons text-white" width="24px" height="24px"/>
                                            </div>

                                            <div class="ml-8">
                                                <h6 class="font-14 text-gray-500">{{ trans('update.disabled_payment_gateways') }}</h6>
                                                <p class="font-12 text-gray-500 opacity-75">{{ trans('update.disabled_payment_gateways_hint') }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid grid-columns-3 gap-24 mt-16">
                                        @foreach($invalidChannels as $invalidChannel)
                                            <div class="disabled-payment-channel d-flex align-items-center p-16 rounded-16 border-gray-200">
                                                <div class="d-flex-center size-48 bg-gray-100">
                                                    <img src="{{ $invalidChannel->image }}" alt="" class="img-fluid">
                                                </div>
                                                <h6 class="font-14 ml-16 text-gray-500">{{ $invalidChannel->title }}</h6>
                                            </div>
                                        @endforeach
                                    </div>

                                </div>

                            @endif

                        </div>
                    </div>


                </div>

                {{-- Right Side --}}
                <div class="col-12 col-md-5 col-lg-3 mt-32">
                    <div class="cart-right-side-section">
                        {{-- Summary --}}

                        <div class="js-cart-summary-container">
                            @include('design_1.web.cart.overview.includes.summary', ['isCartPaymentPage' => true])
                        </div>

                    </div>
                </div>
            </div>

        </form>

    </section>

    @if(!empty($razorpay) and $razorpay)
        <form action="/payments/verify/Razorpay" method="get">
            <input type="hidden" name="order_id" value="{{ $order->id }}">

            <script src="https://checkout.razorpay.com/v1/checkout.js"
                    data-key="{{ getRazorpayApiKey()['api_key'] }}"
                    data-amount="{{ (int)($order->total_amount * 100) }}"
                    data-buttontext=""
                    data-description="Rozerpay"
                    data-currency="{{ currency() }}"
                    data-image="{{ $generalSettings['logo'] }}"
                    data-prefill.name="{{ $order->user->full_name }}"
                    data-prefill.email="{{ $order->user->email }}"
                    data-theme.color="#43d477">
            </script>
        </form>
    @endif

    {{-- Moyasar Payment Form Container (Always available but hidden by default) --}}
    <!-- Overlay -->
    <div id="moyasar-overlay" class="moyasar-overlay" style="display: none;"></div>

    <div id="moyasar-payment-form" class="moyasar-payment-container" style="display: none;">
            <button type="button" class="moyasar-close-btn" onclick="closeMoyasarForm()">&times;</button>

            <div class="payment-header">
                <h2>💳 Complete Your Payment</h2>
                <p>Secure payment powered by Moyasar</p>
            </div>

            <div class="order-summary">
                <h4>📋 Order Summary</h4>
                <div class="summary-row">
                    <span>Order ID:</span>
                    <span>#{{ $order->id }}</span>
                </div>
                <div class="summary-row">
                    <span>Amount:</span>
                    <span>{{ handlePrice($order->total_amount, true, true, false, null, true) }}</span>
                </div>
                <div class="summary-row">
                    <span>Description:</span>
                    <span>Payment for Order #{{ $order->id }}</span>
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

@endsection

@push('scripts_bottom')
    <script src="https://unpkg.com/moyasar-payment-form@2.0.16/dist/moyasar.umd.js"></script>
    <script>
        var hasErrors = '{{ (!empty($errors) and count($errors)) ? 'true' : 'false' }}';
        var hasErrorsHintLang = '{{ trans('update.please_check_the_errors_in_the_shipping_form') }}';
        var selectPaymentGatewayLang = '{{ trans('update.select_a_payment_gateway') }}';
        var pleaseWaitLang = '{{ trans('update.please_wait') }}';
        var transferringToLang = '{{ trans('update.transferring_to_the_payment_gateway') }}';

        // Moyasar payment form handling
        var moyasarInitialized = false;
        var moyasarFormData = null;

        // Bootstrap from session (when controller flashed Moyasar data) - only on checkout page
        var moyasarFormDataFromSession = {!! json_encode(session('moyasar_form_data')) !!};
        if (moyasarFormDataFromSession && window.location.pathname === '/cart/checkout') {
            moyasarFormData = moyasarFormDataFromSession;
            // Don't auto-show form from session - only show when user selects Moyasar
            console.log('Moyasar form data loaded from session, waiting for user selection...');
        }

                // Function to show Moyasar payment form
        function showMoyasarForm() {
            // Only show form on checkout page
            if (window.location.pathname !== '/cart/checkout') {
                console.warn('Moyasar form can only be shown on checkout page');
                return;
            }

            const overlay = document.getElementById('moyasar-overlay');
            const form = document.getElementById('moyasar-payment-form');

            if (overlay && form) {
                overlay.style.display = 'block';
                form.style.display = 'block';

                // Show loading spinner while form initializes
                showMoyasarLoadingSpinner();

                // Initialize Moyasar form if not already done
                if (!moyasarInitialized && moyasarFormData) {
                    initializeMoyasarForm();
                }
            }
        }

                // Function to close Moyasar payment form
        function closeMoyasarForm() {
            // Only close form on checkout page
            if (window.location.pathname !== '/cart/checkout') {
                return;
            }

            const overlay = document.getElementById('moyasar-overlay');
            const form = document.getElementById('moyasar-payment-form');

            if (overlay && form) {
                overlay.style.display = 'none';
                form.style.display = 'none';

                // Reset form state
                hideMoyasarLoadingSpinner();

                // Hide any error/success messages
                const errorDiv = document.getElementById('moyasar-error-message');
                const successDiv = document.getElementById('moyasar-success-message');
                if (errorDiv) errorDiv.style.display = 'none';
                if (successDiv) successDiv.style.display = 'none';
            }
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
                        showMoyasarSuccessMessage('Payment completed successfully! Redirecting...');

                        // Save payment ID to backend
                        await saveMoyasarPaymentOnBackend(payment);

                        // Redirect to success page - ensure it's not cart page
                        setTimeout(() => {
                            let redirectUrl = moyasarFormData.callback_url;

                            // If callback URL is cart-related, redirect to dashboard instead
                            if (redirectUrl.includes('/cart') || redirectUrl.includes('/checkout')) {
                                redirectUrl = '/panel/dashboard';
                            }

                            window.location.href = redirectUrl;
                        }, 2000);
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
                    console.log('Moyasar payment saved to backend successfully');
                } else {
                    console.error('Failed to save Moyasar payment to backend');
                }
            } catch (error) {
                console.error('Error saving Moyasar payment to backend:', error);
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
            var spinnerEl = document.getElementById('moyasar-loading-spinner');
            if (spinnerEl) {
                spinnerEl.style.display = 'block';
            }
        }

        // Function to hide Moyasar loading spinner
        function hideMoyasarLoadingSpinner() {
            var spinnerEl = document.getElementById('moyasar-loading-spinner');
            if (spinnerEl) {
                spinnerEl.style.display = 'none';
            }
        }

        // Initialize Moyasar form elements when DOM is ready - only on checkout page
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.pathname === '/cart/checkout') {
                // Ensure Moyasar form is hidden by default
                hideMoyasarLoadingSpinner();

                // Double-check that form and overlay are hidden
                const overlay = document.getElementById('moyasar-overlay');
                const form = document.getElementById('moyasar-payment-form');
                if (overlay) overlay.style.display = 'none';
                if (form) form.style.display = 'none';
            }
        });

        // Close overlay when clicking on it - only on checkout page
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.pathname !== '/cart/checkout') {
                return;
            }

            const overlay = document.getElementById('moyasar-overlay');
            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        closeMoyasarForm();
                    }
                });
            }
        });

        // Initialize Moyasar form handling - only on checkout page
        document.addEventListener('DOMContentLoaded', function() {
            // Only initialize Moyasar form handling on checkout page
            if (window.location.pathname !== '/cart/checkout') {
                return;
            }

            // Add change handlers for Moyasar payment gateway selection
            const moyasarGateways = document.querySelectorAll('input[name="gateway"][data-class="Moyasar"], input[name="gateway"][value="moyasar"]');
            moyasarGateways.forEach(function(gateway) {
                gateway.addEventListener('change', function() {
                    if (this.checked) {
                        console.log('Moyasar gateway selected, preparing payment form...');
                    }
                });
            });
        });
    </script>
    <script src="{{ getDesign1ScriptPath("cart_page") }}"></script>
@endpush
