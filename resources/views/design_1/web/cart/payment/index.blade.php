@extends("design_1.web.layouts.app")

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push("styles_top")
<link rel="stylesheet" href="{{ getDesign1StylePath('cart_page') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/moyasar-payment-form@2.0.17/dist/moyasar.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .bnpl-provider-card {
        transition: all 0.3s ease;
    }

    .bnpl-provider-card:hover {
        transform: translateY(-2px);
    }

    .bnpl-provider-card input[type="radio"]:checked+label .gateway-card {
        border: 2px solid #43d477;
        box-shadow: 0 4px 12px rgba(67, 212, 119, 0.15);
    }

    .provider-logo-placeholder {
        width: 32px;
        height: 32px;
        background: #43d477;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
    }

    .bnpl-provider-card .gateway-card {
        min-height: 200px;
    }

    .moyasar-form-container {
        background: #f8f9fa;
        border-radius: 16px;
        padding: 24px;
        margin: 24px 0;
        text-align: center;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .moyasar-form-container.active {
        border-color: #007bff;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    }

            /* Moyasar Modal Styles */
        .moyasar-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: none;
            /* Debug styles - make sure modal is visible */
            background: rgba(255, 0, 0, 0.1); /* Red tint for debugging */
        }

        .moyasar-modal.active {
            display: block !important; /* Force display */
        }

        /* Ensure modal content is visible */
        .moyasar-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            /* Debug styles */
            border: 3px solid red; /* Red border for debugging */
            z-index: 10000;
        }

        /* Tabby Modal Styles */
        .tabby-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: none;
        }

        .tabby-modal.active {
            display: block !important;
        }

        .tabby-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .tabby-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            z-index: 10000;
        }

        .tabby-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e9ecef;
        }

        .tabby-modal-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .tabby-modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #666;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .tabby-modal-close:hover {
            background: #f8f9fa;
            color: #333;
        }

        .tabby-modal-body {
            padding: 24px;
        }

        .tabby-form-container {
            text-align: center;
        }

        .tabby-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #43d477;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
        }

        .tabby-installment-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .tabby-installment-info h5 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
        }

        .tabby-installment-detail {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 14px;
        }

        .tabby-installment-detail .label {
            color: #666;
        }

        .tabby-installment-detail .value {
            color: #333;
            font-weight: 500;
        }

        .tabby-eligibility-status {
            margin: 20px 0;
            padding: 16px;
            border-radius: 12px;
            font-size: 14px;
        }

        .tabby-eligibility-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .tabby-eligibility-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .tabby-eligibility-status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

    .moyasar-modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    .moyasar-modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    .moyasar-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 24px 24px 16px 24px;
        border-bottom: 1px solid #e9ecef;
    }

    .moyasar-modal-title {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }

    .moyasar-modal-close {
        background: none;
        border: none;
        font-size: 20px;
        color: #666;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .moyasar-modal-close:hover {
        background: #f8f9fa;
        color: #333;
    }

    .moyasar-modal-body {
        padding: 24px;
    }

    .moyasar-modal .moyasar-form-container {
        border: none;
        margin: 0;
        padding: 0;
        background: transparent;
    }

    .moyasar-form {
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Ensure Moyasar form container is visible */
    #moyasar-form-container {
        min-height: 300px;
        position: relative;
        overflow: visible;
    }

    /* Style for Moyasar payment form elements */
    #moyasar-form-container .mysr-form {
        width: 100%;
        min-height: 200px;
    }

    /* Ensure payment methods are visible */
    #moyasar-form-container .mysr-payment-methods {
        margin-top: 20px;
        padding: 20px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .moyasar-payment-methods {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #dee2e6;
    }

    /* Fallback payment form styling */
    .fallback-payment-form {
        padding: 20px;
    }

    .payment-methods-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }

    .payment-method-card {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
    }

    .payment-method-card:hover {
        border-color: #007bff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    }

    .payment-method-card.active {
        border-color: #28a745;
        background: #f8fff9;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
    }

    .payment-icon {
        margin-bottom: 15px;
    }

    .payment-method-card h6 {
        margin: 10px 0 5px 0;
        font-weight: 600;
        color: #333;
    }

    .payment-method-card p {
        margin: 0;
        font-size: 14px;
        color: #666;
    }

    /* Manual Moyasar form styling */
    .mysr-payment-methods {
        padding: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .mysr-payment-methods h4 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
        font-weight: 600;
    }

    .mysr-credit-card,
    .mysr-stcpay,
    .mysr-applepay,
    .mysr-samsungpay {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .mysr-credit-card:hover,
    .mysr-stcpay:hover,
    .mysr-applepay:hover,
    .mysr-samsungpay:hover {
        border-color: #007bff;
        background: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    }

    .mysr-credit-card h5,
    .mysr-stcpay h5,
    .mysr-applepay h5,
    .mysr-samsungpay h5 {
        margin: 0 0 5px 0;
        color: #333;
        font-weight: 600;
    }

    .mysr-credit-card p,
    .mysr-stcpay p,
    .mysr-applepay p,
    .mysr-samsungpay p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }

    /* Success message styling */
    .mysr-payment-methods .alert-success {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px;
        margin-top: 15px;
        text-align: center;
        font-weight: 600;
    }

    /* Working payment form styling */
    .working-payment-form {
        padding: 20px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }

    .working-payment-form h4 {
        color: #28a745;
        font-weight: 700;
    }

    .working-payment-form .text-success {
        color: #28a745 !important;
    }

    .working-payment-form .btn-lg {
        padding: 15px 30px;
        font-size: 18px;
        font-weight: 600;
    }

    .working-payment-form small.text-success {
        font-weight: 600;
        font-size: 12px;
    }

    /* Error message styling */
    .moyasar-form-container .alert {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .moyasar-form-container .alert-danger {
        background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        color: white;
    }

    .moyasar-form-container .alert-warning {
        background: linear-gradient(135deg, #ffa726, #ff9800);
        color: white;
    }

    .moyasar-form-container .btn {
        border-radius: 8px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    .moyasar-form-container .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    /* Payment method selection indicators */
    .payment-method-selected {
        border: 2px solid #43d477 !important;
        box-shadow: 0 4px 12px rgba(67, 212, 119, 0.15) !important;
        background-color: #f8fff9 !important;
    }

    /* Moyasar form styling improvements */
    #moyasar-form-container {
        min-height: 400px;
        padding: 20px;
    }

    #moyasar-form-container .mysr-form {
        width: 100%;
        min-height: 300px;
    }

    /* Ensure payment method buttons are visible and styled */
    #moyasar-form-container .mysr-payment-methods {
        margin-top: 20px;
        padding: 20px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        background: #f8f9fa;
    }

    /* Style for individual payment method sections */
    #moyasar-form-container .mysr-credit-card,
    #moyasar-form-container .mysr-stcpay,
    #moyasar-form-container .mysr-applepay,
    #moyasar-form-container .mysr-samsungpay {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
    }

    #moyasar-form-container .mysr-credit-card:hover,
    #moyasar-form-container .mysr-stcpay:hover,
    #moyasar-form-container .mysr-applepay:hover,
    #moyasar-form-container .mysr-samsungpay:hover {
        border-color: #007bff;
        background: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    }

    /* Credit card form styling */
    #moyasar-form-container .mysr-credit-card input[type="text"],
    #moyasar-form-container .mysr-credit-card input[type="number"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 10px;
    }

    /* STC Pay form styling */
    #moyasar-form-container .mysr-stcpay input[type="tel"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 10px;
    }

    /* Loading state improvements */
    #moyasar-form-container .loading-state {
        text-align: center;
        padding: 40px 20px;
    }

    #moyasar-form-container .loading-state .spinner-border {
        width: 3rem;
        height: 3rem;
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
        <p class="mt-8 font-16 text-gray-500">{{ handlePrice($calculatePrices["total"], true, true, false, null, true) .
            ' ' . trans('cart.for_items',['count' => $count]) }}</p>
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
                            @if(!$isMultiCurrency or (!empty($paymentChannel->currencies) and in_array($userCurrency,
                            $paymentChannel->currencies)))
                                            <div class="payment-channel-card position-relative">
                                <input type="radio" name="gateway" id="gateway_{{ $paymentChannel->id }}"
                                    data-class="{{ $paymentChannel->class_name }}" value="{{ $paymentChannel->id }}">
                                <label class="position-relative w-100 d-block cursor-pointer"
                                    for="gateway_{{ $paymentChannel->id }}">
                                                    <div class="gateway-mask"></div>
                                    <div
                                        class="gateway-card position-relative z-index-2 d-flex-center flex-column rounded-16 bg-white w-100 h-100 text-center">
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
                                <input type="radio" name="gateway" id="gateway_credit" value="credit" {{
                                    (empty($userCharge) or ($calculatePrices["total"]> $userCharge)) ? 'disabled' : ''
                                }}>
                                    <label class="position-relative w-100 d-block cursor-pointer" for="gateway_credit">
                                        <div class="gateway-mask"></div>
                                    <div
                                        class="gateway-card position-relative z-index-2 d-flex-center flex-column rounded-16 bg-white w-100 h-100 text-center">
                                            <div class="d-flex-center size-48 bg-gray-100">
                                            <i class="fas fa-wallet text-dark" style="font-size: 24px;"></i>
                                            </div>
                                            <h6 class="font-14 mt-12">{{ trans('financial.account_charge') }}</h6>
                                            <p class="mt-4 font-12 text-gray-500">{{ handlePrice($userCharge) }}</p>
                                        </div>
                                    </label>
                                </div>

                            {{-- BNPL Option --}}
                            @if(!empty($bnplProviders) and $bnplProviders->count() > 0)
                            <div class="payment-channel-card position-relative">
                                <input type="radio" name="gateway" id="gateway_bnpl" value="bnpl">
                                <label class="position-relative w-100 d-block cursor-pointer" for="gateway_bnpl">
                                    <div class="gateway-mask"></div>
                                    <div
                                        class="gateway-card position-relative z-index-2 d-flex-center flex-column rounded-16 bg-white w-100 h-100 text-center">
                                        <div class="d-flex-center size-48 bg-gray-100">
                                            <i class="fas fa-credit-card text-dark" style="font-size: 24px;"></i>
                                        </div>
                                        <h6 class="font-14 mt-12">{{ trans('update.pay_with_bnpl') }}</h6>
                                        <p class="mt-4 font-12 text-gray-500">{{ trans('update.bnpl_available') }}</p>
                                    </div>
                                </label>
                            </div>
                            @endif
                            </div>


                            @if(!empty($invalidChannels) and empty(getFinancialSettings("hide_disabled_payment_gateways")))
                                <div class="px-16 mt-28">
                                    {{-- Alert --}}
                                    <div class="position-relative pl-8">
                                        <div class="d-flex align-items-center p-12 rounded-12 bg-gray-500-20">
                                            <div class="alert-left-20 d-flex-center size-48 bg-gray-500 rounded-12">
                                        <i class="fas fa-info-circle text-white" style="font-size: 24px;"></i>
                                            </div>

                                            <div class="ml-8">
                                        <h6 class="font-14 text-gray-500">{{ trans('update.disabled_payment_gateways')
                                            }}</h6>
                                        <p class="font-12 text-gray-500 opacity-75">{{
                                            trans('update.disabled_payment_gateways_hint') }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid grid-columns-3 gap-24 mt-16">
                                        @foreach($invalidChannels as $invalidChannel)
                                <div
                                    class="disabled-payment-channel d-flex align-items-center p-16 rounded-16 border-gray-200">
                                                <div class="d-flex-center size-48 bg-gray-100">
                                                    <img src="{{ $invalidChannel->image }}" alt="" class="img-fluid">
                                                </div>
                                                <h6 class="font-14 ml-16 text-gray-500">{{ $invalidChannel->title }}</h6>
                                            </div>
                                        @endforeach
                                    </div>

                                </div>

                            @endif

                                                    {{-- BNPL Provider Selection --}}
                        @if(!empty($bnplProviders) and $bnplProviders->count() > 0)
                        <div id="bnpl-provider-selection" class="px-16 mt-28" style="display: none;">
                            <div class="card-before-line">
                                <h3 class="font-14">{{ trans('update.bnpl_select_provider') }}</h3>
                            </div>

                            <div class="d-grid grid-columns-2 grid-lg-columns-3 gap-24 mt-16">
                                @foreach($bnplProviders as $provider)
                                @php
                                $installmentAmount = $provider->calculateInstallmentAmount($calculatePrices["total"],
                                15);
                                $totalWithFee = $calculatePrices["total"] * (1 + (15 / 100)) * (1 +
                                ($provider->fee_percentage / 100));
                                @endphp

                                <div class="bnpl-provider-card position-relative">
                                    <input type="radio" name="bnpl_provider" id="bnpl_provider_{{ $provider->id }}"
                                        value="{{ $provider->id }}"
                                        data-installments="{{ $provider->installment_count }}"
                                        data-fee="{{ $provider->fee_percentage }}"
                                        data-provider-name="{{ $provider->name }}">
                                    <label class="position-relative w-100 d-block cursor-pointer"
                                        for="bnpl_provider_{{ $provider->id }}">
                                        <div class="gateway-mask"></div>
                                        <div
                                            class="gateway-card position-relative z-index-2 d-flex-center flex-column rounded-16 bg-white w-100 h-100 text-center p-16">
                                            <div class="d-flex-center size-48 bg-gray-100">
                                                @if($provider->logo_path)
                                                <img src="{{ $provider->logo_url }}" alt="{{ $provider->name }}"
                                                    class="img-fluid">
                                                @else
                                                <div class="provider-logo-placeholder">
                                                    {{ strtoupper(substr($provider->name, 0, 2)) }}
                                                </div>
                                                @endif
                                            </div>
                                            <h6 class="font-14 mt-12">{{ $provider->name }}</h6>
                                            <p class="mt-4 font-12 text-gray-500">{{ trans('update.bnpl_installments')
                                                }}: {{ $provider->installment_count }}</p>
                                            <p class="mt-2 font-12 text-gray-500">{{
                                                trans('update.bnpl_monthly_payment') }}: {{
                                                handlePrice($installmentAmount) }}</p>
                                            @if($provider->fee_percentage > 0)
                                            <p class="mt-2 font-12 text-gray-500">{{
                                                trans('update.bnpl_total_with_fees') }}: {{ handlePrice($totalWithFee)
                                                }}</p>
                                            @else
                                            <p class="mt-2 font-12 text-gray-500 text-success">{{
                                                trans('update.bnpl_no_fees') }}</p>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>

                            <div class="mt-16 text-center">
                                <small class="text-gray-500">{{ trans('update.bnpl_terms_apply') }}</small>
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

    <script src="https://checkout.razorpay.com/v1/checkout.js" data-key="{{ getRazorpayApiKey()['api_key'] }}"
        data-amount="{{ (int)($order->total_amount * 100) }}" data-buttontext="" data-description="Rozerpay"
        data-currency="{{ currency() }}" data-image="{{ $generalSettings['logo'] }}"
        data-prefill.name="{{ $order->user->full_name }}" data-prefill.email="{{ $order->user->email }}"
                    data-theme.color="#43d477">
            </script>
        </form>
    @endif

    @if(!empty($moyasar) and $moyasar)
{{-- Moyasar Payment Modal --}}
<div id="moyasar-modal" class="moyasar-modal" style="display: none;">
    <div class="moyasar-modal-overlay"></div>
    <div class="moyasar-modal-content">
        <div class="moyasar-modal-header">
            <h4 class="moyasar-modal-title">{{ trans('update.complete_payment') }}</h4>
            <button type="button" class="moyasar-modal-close" id="moyasar-modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="moyasar-modal-body">
            <div id="moyasar-form-container" class="moyasar-form-container">
                <!-- Moyasar will render the complete payment form here -->
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading payment form...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading Moyasar payment form...</p>
                    <p class="mt-2 text-muted small">Available methods: Credit Card, STC Pay, Apple Pay, Samsung Pay</p>
                </div>
            </div>
        </div>
    </div>
</div>

    {{-- Tabby Payment Modal --}}
    <div id="tabby-modal" class="tabby-modal" style="display: none;">
        <div class="tabby-modal-overlay"></div>
        <div class="tabby-modal-content">
            <div class="tabby-modal-header">
                <h4 class="tabby-modal-title">{{ trans('update.tabby_pay_later') }}</h4>
                <button type="button" class="tabby-modal-close" id="tabby-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="tabby-modal-body">
                <div id="tabby-form-container" class="tabby-form-container">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ trans('update.tabby_eligibility_check') }}</span>
                        </div>
                        <p class="mt-3 text-muted">{{ trans('update.tabby_eligibility_check') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MisPay Payment Modal --}}
    <div id="mispay-modal" class="tabby-modal" style="display: none;">
        <div class="tabby-modal-overlay"></div>
        <div class="tabby-modal-content">
            <div class="tabby-modal-header">
                <h4 class="tabby-modal-title">{{ trans('update.mispay_pay_later') }}</h4>
                <button type="button" class="tabby-modal-close" id="mispay-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="tabby-modal-body">
                <div id="mispay-form-container" class="tabby-form-container">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ trans('update.mispay_eligibility_check') }}</span>
                        </div>
                        <p class="mt-3 text-muted">{{ trans('update.mispay_eligibility_check') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/moyasar-payment-form@2.0.17/dist/moyasar.umd.min.js"></script>
<script>
                    document.addEventListener('DOMContentLoaded', function() {
                // Check network connectivity
                if (!navigator.onLine) {
                    console.warn('⚠️ Moyasar: Network appears to be offline');
                }

                // Check if Moyasar library loaded successfully
                const moyasarScript = document.querySelector('script[src*="moyasar"]');
                if (moyasarScript) {
                    console.log('🔵 Moyasar: Script tag found:', moyasarScript.src);

                    // Monitor script loading
                    moyasarScript.addEventListener('load', function() {
                        console.log('🟢 Moyasar: Script loaded successfully');

                        // Check if Moyasar object is available after script load
                        setTimeout(() => {
                            if (typeof Moyasar !== 'undefined') {
                                console.log('🟢 Moyasar: Library object available after script load');
                                console.log('🔵 Moyasar: Library properties:', Object.keys(Moyasar));
                            } else {
                                console.error('🔴 Moyasar: Library object not available after script load');
                            }
                        }, 100);
                    });

                    moyasarScript.addEventListener('error', function() {
                        console.error('🔴 Moyasar: Script failed to load');
                    });
                } else {
                    console.warn('⚠️ Moyasar: Script tag not found');
                }

                // Check if Moyasar library is already loaded
                if (typeof Moyasar !== 'undefined') {
                    console.log('🟢 Moyasar: Library already loaded on DOM ready');
                    console.log('🔵 Moyasar: Library properties:', Object.keys(Moyasar));
                } else {
                    console.log('🔵 Moyasar: Library not yet loaded, waiting for script to load');
                }

                // Add global error handler to catch any JavaScript errors
                window.addEventListener('error', function(event) {
                    console.error('🔴 Moyasar: Global JavaScript error:', {
                        message: event.message,
                        filename: event.filename,
                        lineno: event.lineno,
                        colno: event.colno,
                        error: event.error
                    });
                });

                // Add unhandled promise rejection handler
                window.addEventListener('unhandledrejection', function(event) {
                    console.error('🔴 Moyasar: Unhandled promise rejection:', {
                        reason: event.reason,
                        promise: event.promise
                    });
                });

                // Listen for network status changes
                window.addEventListener('online', function() {
                    console.log('🟢 Moyasar: Network is back online');
                    // Retry initialization if it failed due to network issues
                    if (!moyasarInitialized && moyasarInitAttempts > 0) {
                        console.log('🔄 Moyasar: Retrying initialization after network recovery');
                        setTimeout(initMoyasar, 1000);
                    }
                });

                window.addEventListener('offline', function() {
                    console.warn('⚠️ Moyasar: Network went offline');
                });

                console.log('🔵 Moyasar: DOM loaded, starting initialization');
                console.log('🔵 Moyasar: Order details', {
                    order_id: '{{ $order->id }}',
                    total_amount: '{{ $order->total_amount }}',
                    currency: '{{ currency() }}',
                    converted_amount_sar: '{{ convertAmountToSAR($order->total_amount) }}',
                    moyasar_available: {{ !empty($moyasar) && $moyasar ? 'true' : 'false' }}
                });

                // Global variables for Moyasar initialization
                let moyasarInitialized = false;
                let moyasarSelected = false;
                let moyasarInitAttempts = 0;
                const maxInitAttempts = 5;

                // Moyasar will handle all payment method rendering natively

                // Function to initialize Moyasar
                function initMoyasar() {
                    console.log('🔵 Moyasar: Initialization started');

                    // Add timeout for library loading
                    const libraryTimeout = setTimeout(() => {
                        if (typeof Moyasar === 'undefined') {
                            console.error('🔴 Moyasar: Library loading timeout - Moyasar library not loaded after 10 seconds');
                            alert('Payment form loading timeout. Please refresh the page and try again.');
                        }
                    }, 10000);

                    // Check if Moyasar library is loaded
                    if (typeof Moyasar === 'undefined') {
                        moyasarInitAttempts++;
                        if (moyasarInitAttempts >= maxInitAttempts) {
                            clearTimeout(libraryTimeout);
                            console.error('🔴 Moyasar: Library failed to load after', maxInitAttempts, 'attempts');
                            alert('Payment form loading failed after multiple attempts. Please refresh the page and try again.');
                            return;
                        }
                        console.error('🔴 Moyasar: Library not loaded yet, retrying in 1 second... (Attempt', moyasarInitAttempts, '/', maxInitAttempts, ')');
                        setTimeout(initMoyasar, 1000);
                        return;
                    }

                    if (!moyasarInitialized) {
                        clearTimeout(libraryTimeout);
                        console.log('🔵 Moyasar: Library loaded, initializing form');

                                                @php
                            // Get Moyasar payment channel configuration from database
                            $moyasarChannel = $paymentChannels->where('class_name', 'Moyasar')->first();
                            $samsungServiceId = $moyasarChannel ? $moyasarChannel->samsung_pay_service_id : null;
                            $applePayMerchantId = $moyasarChannel ? $moyasarChannel->apple_pay_merchant_id : null;
                            $stcPayMerchantId = $moyasarChannel ? $moyasarChannel->stc_pay_merchant_id : null;

                            // Get additional configuration from payment channel settings
                            $moyasarSettings = $moyasarChannel ? json_decode($moyasarChannel->settings ?? '{}', true) : [];
                            $supportedNetworks = $moyasarSettings['supported_networks'] ?? ['visa', 'mastercard', 'mada'];
                            $enabledMethods = $moyasarSettings['enabled_methods'] ?? ['creditcard', 'stcpay', 'applepay'];
                            $testMode = $moyasarChannel ? ($moyasarChannel->test_mode ?? false) : true;

                            // Ensure we have at least basic payment methods enabled
                            if (empty($enabledMethods)) {
                                $enabledMethods = ['creditcard', 'stcpay', 'applepay'];
                            }

                            // Ensure we have supported networks
                            if (empty($supportedNetworks)) {
                                $supportedNetworks = ['visa', 'mastercard', 'mada'];
                            }

                            // Log the configuration for debugging
                            if ($moyasarChannel) {
                                \Log::info('Moyasar payment channel found:', [
                                    'id' => $moyasarChannel->id,
                                    'title' => $moyasarChannel->title,
                                    'class_name' => $moyasarChannel->class_name,
                                    'samsung_pay_service_id' => $samsungServiceId,
                                    'apple_pay_merchant_id' => $applePayMerchantId,
                                    'stc_pay_merchant_id' => $stcPayMerchantId,
                                    'test_mode' => $testMode,
                                    'supported_networks' => $supportedNetworks,
                                    'enabled_methods' => $enabledMethods,
                                    'settings' => $moyasarSettings
                                ]);

                                // Also log to console for frontend debugging
                                // Configuration will be logged in JavaScript below
                            } else {
                                \Log::warning('Moyasar payment channel not found in payment_channels table');
                                // Warning will be logged in JavaScript below
                            }
                        @endphp

                        // Log configuration for debugging
                        @if($moyasarChannel)
                        console.log('🔵 Moyasar: Database configuration loaded:', {
                            channel_id: {{ $moyasarChannel->id ?? 'null' }},
                            samsung_pay_service_id: '{{ $samsungServiceId ?? "" }}',
                            apple_pay_merchant_id: '{{ $applePayMerchantId ?? "" }}',
                            stc_pay_merchant_id: '{{ $stcPayMerchantId ?? "" }}',
                            test_mode: {{ $testMode ? 'true' : 'false' }},
                            supported_networks: @json($supportedNetworks ?? []),
                            enabled_methods: @json($enabledMethods ?? [])
                        });
                        @else
                        console.warn('⚠️ Moyasar: Payment channel not found in database');
                        @endif

                        const config = {
                            element: '#moyasar-form-container',
                            // Amount in the smallest currency unit (e.g., 1000 for 10.00 SAR)
                            // Moyasar only supports SAR currency, so we need to convert
                            amount: {{ (int)(convertAmountToSAR($order->total_amount) * 100) }},
                            currency: 'SAR', // Moyasar only supports SAR
                            description: 'Order #{{ $order->id }}',
                            publishable_api_key: '{{ getMoyasarApiKey() ?? "pk_test_..." }}',
                            callback_url: '{{ url("/payments/verify/Moyasar") }}?order_id={{ $order->id }}',
                            supported_networks: @json($supportedNetworks),
                            methods: @json(array_merge($enabledMethods, !empty($samsungServiceId) ? ['samsungpay'] : [])),
                            test_mode: {{ $testMode ? 'true' : 'false' }},
                            // Credit Card configuration
                            creditcard: {
                                enabled: true,
                                supported_networks: @json($supportedNetworks),
                                require_cvv: true,
                                require_expiry: true,
                                require_name: true
                            },
                            // STC Pay configuration
                            stcpay: {
                                enabled: true,
                                merchant_id: '{{ $stcPayMerchantId ?? "" }}',
                                theme: '{{ $moyasarSettings['stcpay_theme'] ?? 'light' }}',
                                mobile_number_required: true,
                                otp_challenge: true,
                                @if(!empty($moyasarSettings['stcpay_merchant_name']))
                                merchant_name: '{{ $moyasarSettings['stcpay_merchant_name'] }}',
                                @endif
                                @if(!empty($moyasarSettings['stcpay_logo_url']))
                                logo_url: '{{ $moyasarSettings['stcpay_logo_url'] }}',
                                @endif
                            },
                            // Apple Pay configuration
                            apple_pay: {
                                enabled: true,
                                country: '{{ $moyasarSettings['apple_pay_country'] ?? 'SA' }}',
                                label: '{{ addslashes($moyasarSettings['apple_pay_label'] ?? getGeneralSettings('site_name') ?? 'Store') }}',
                                merchant_id: '{{ $applePayMerchantId ?? "" }}',
                                validate_merchant_url: '{{ $moyasarSettings['apple_pay_merchant_url'] ?? 'https://api.moyasar.com/v1/applepay/initiate' }}',
                                merchant_capabilities: @json($moyasarSettings['apple_pay_merchant_capabilities'] ?? ['supports3DS', 'supportsCredit', 'supportsDebit']),
                                supported_countries: @json($moyasarSettings['apple_pay_supported_countries'] ?? ['SA'])
                            },
                            @if(!empty($samsungServiceId))
                            // Samsung Pay configuration
                            samsung_pay: {
                                enabled: true,
                                service_id: '{{ $samsungServiceId }}',
                                order_number: 'order-{{ $order->id }}-{{ time() }}',
                                country: '{{ $moyasarSettings['samsung_pay_country'] ?? 'SA' }}',
                                label: '{{ addslashes($moyasarSettings['samsung_pay_label'] ?? getGeneralSettings('site_name') ?? 'Store') }}',
                                environment: '{{ $moyasarChannel ? ($moyasarChannel->samsung_pay_environment ?? 'TEST') : 'TEST' }}',
                                supported_networks: @json($supportedNetworks),
                                @if(!empty($moyasarSettings['samsung_pay_merchant_name']))
                                merchant_name: '{{ $moyasarSettings['samsung_pay_merchant_name'] }}',
                                @endif
                                @if(!empty($moyasarSettings['samsung_pay_logo_url']))
                                logo_url: '{{ $moyasarSettings['samsung_pay_logo_url'] }}',
                                @endif
                            },
                            @endif
                            // Add required fields for better compatibility
                            on_ready: function() {
                                console.log('🟢 Moyasar: Form is ready and loaded');

                                // Check if the form content is visible
                                const formContainer = document.querySelector('#moyasar-form-container');
                                if (formContainer) {
                                    console.log('🔵 Moyasar: Form container content after initialization:', {
                                        innerHTML: formContainer.innerHTML,
                                        children: formContainer.children.length,
                                        visible: formContainer.offsetWidth > 0 && formContainer.offsetHeight > 0
                                    });

                                    // Verify all payment methods are available
                                    setTimeout(() => {
                                        const creditCardForm = formContainer.querySelector('[class*="credit"], [class*="card"], [class*="mysr-credit"]');
                                        const stcPayForm = formContainer.querySelector('[class*="stc"], [class*="mysr-stc"]');
                                        const applePayForm = formContainer.querySelector('[class*="apple"], [class*="mysr-apple"]');
                                        const samsungPayForm = formContainer.querySelector('[class*="samsung"], [class*="mysr-samsung"]');

                                        console.log('🔵 Moyasar: Payment method availability check:', {
                                            credit_card: !!creditCardForm,
                                            stc_pay: !!stcPayForm,
                                            apple_pay: !!applePayForm,
                                            samsung_pay: !!samsungPayForm
                                        });

                                        // If any payment method is missing, log a warning
                                        if (!creditCardForm) console.warn('⚠️ Moyasar: Credit card form not found');
                                        if (!stcPayForm) console.warn('⚠️ Moyasar: STC Pay form not found');
                                        if (!applePayForm) console.warn('⚠️ Moyasar: Apple Pay form not found');
                                        if (!samsungPayForm) console.warn('⚠️ Moyasar: Samsung Pay form not found');
                                    }, 1000);
                                }

                                // Moyasar will handle form rendering natively
                            },
                            on_error: function(error) {
                                console.error('🔴 Moyasar: Form error occurred', {
                                    error: error,
                                    error_message: error.message || 'Unknown error',
                                    error_code: error.code || 'N/A',
                                    order_id: '{{ $order->id }}',
                                    timestamp: new Date().toISOString()
                                });

                                // Show user-friendly error message
                                const errorMessage = error.message || 'An error occurred while loading the payment form. Please try again.';
                                alert('Payment Form Error: ' + errorMessage);
                            },

                            // Simplified configuration to avoid errors
                            // Remove complex configurations that might cause issues

                            // Payment lifecycle callbacks with comprehensive logging
                            on_completed: async function(payment) {
                                console.log('🟢 Moyasar: Payment completed successfully', {
                                    payment_id: payment.id,
                                    status: payment.status,
                                    amount: payment.amount,
                                    currency: payment.currency,
                                    payment_method: payment.source?.type || 'unknown',
                                    order_id: '{{ $order->id }}',
                                    timestamp: new Date().toISOString(),
                                    stcpay_details: payment.source?.type === 'stcpay' ? {
                                        mobile_number: payment.source?.mobile || 'N/A',
                                        transaction_id: payment.source?.transaction_id || 'N/A'
                                    } : null,
                                    applepay_details: payment.source?.type === 'applepay' ? {
                                        card_type: payment.source?.brand || 'N/A',
                                        card_last4: payment.source?.last4 || 'N/A'
                                    } : null,
                                    samsungpay_details: payment.source?.type === 'samsungpay' ? {
                                        card_type: payment.source?.brand || 'N/A',
                                        card_last4: payment.source?.last4 || 'N/A'
                                    } : null
                                });

                                // Log STC Pay specific information
                                if (payment.source?.type === 'stcpay') {
                                    console.log('🟢 STC Pay: Payment details', {
                                        mobile_number: payment.source?.mobile || 'N/A',
                                        transaction_id: payment.source?.transaction_id || 'N/A',
                                        payment_status: payment.status,
                                        amount_paid: payment.amount,
                                        currency: payment.currency
                                    });
                                }

                                // Log Apple Pay specific information
                                if (payment.source?.type === 'applepay') {
                                    console.log('🟢 Apple Pay: Payment details', {
                                        card_type: payment.source?.brand || 'N/A',
                                        card_last4: payment.source?.last4 || 'N/A',
                                        payment_status: payment.status,
                                        amount_paid: payment.amount,
                                        currency: payment.currency
                                    });
                                }

                                // Log Samsung Pay specific information
                                if (payment.source?.type === 'samsungpay') {
                                    console.log('🟢 Samsung Pay: Payment details', {
                                        card_type: payment.source?.brand || 'N/A',
                                        card_last4: payment.source?.last4 || 'N/A',
                                        payment_status: payment.status,
                                        amount_paid: payment.amount,
                                        currency: payment.currency
                                    });
                                }

                                // Save payment ID to backend if needed
                                console.log('🔵 Moyasar: Redirecting to verification page');
                                // Redirect to verification page
                                window.location.href = '{{ url("/payments/verify/Moyasar") }}?order_id={{ $order->id }}&payment_id=' + payment.id;
                            },

                            on_failure: async function(error) {
                                console.error('🔴 Moyasar: Payment failed', {
                                    error: error,
                                    error_message: error.message || 'Unknown error',
                                    error_code: error.code || 'N/A',
                                    order_id: '{{ $order->id }}',
                                    timestamp: new Date().toISOString(),
                                    payment_method: 'unknown'
                                });

                                // Log STC Pay specific failure if applicable
                                if (error.source?.type === 'stcpay') {
                                    console.error('🔴 STC Pay: Payment failed', {
                                        mobile_number: error.source?.mobile || 'N/A',
                                        error_reason: error.message || 'Unknown STC Pay error',
                                        timestamp: new Date().toISOString()
                                    });
                                }

                                // Log Apple Pay specific failure if applicable
                                if (error.source?.type === 'applepay') {
                                    console.error('🔴 Apple Pay: Payment failed', {
                                        card_type: error.source?.brand || 'N/A',
                                        error_reason: error.message || 'Unknown Apple Pay error',
                                        timestamp: new Date().toISOString()
                                    });
                                }

                                // Log Samsung Pay specific failure if applicable
                                if (error.source?.type === 'samsungpay') {
                                    console.error('🔴 Samsung Pay: Payment failed', {
                                        card_type: error.source?.brand || 'N/A',
                                        error_reason: error.message || 'Unknown Samsung Pay error',
                                        timestamp: new Date().toISOString()
                                    });
                                }

                                alert('Payment failed. Please try again.');
                            },

                            on_initiating: async function() {
                                console.log('🟡 Moyasar: Payment initiation started', {
                                    order_id: '{{ $order->id }}',
                                    timestamp: new Date().toISOString(),
                                    payment_methods: ['creditcard', 'stcpay', 'applepay', 'samsungpay'],
                                    amount_sar: {{ convertAmountToSAR($order->total_amount) }},
                                    amount_halalas: {{ (int)(convertAmountToSAR($order->total_amount) * 100) }}
                                });

                                // Log STC Pay initiation if selected
                                console.log('🟡 STC Pay: Ready for mobile number input and OTP challenge');

                                // Log Apple Pay initiation if available
                                console.log('🟡 Apple Pay: Ready for Touch ID/Face ID authentication');

                                // Log Samsung Pay initiation if available
                                console.log('🟡 Samsung Pay: Ready for Samsung Pay authentication');

                                // Optional: Perform last-second validations
                                return {};
                            },

                            // STC Pay specific event handlers
                            on_stcpay_initiated: function() {
                                console.log('🟡 STC Pay: Payment initiated, waiting for mobile number');
                            },

                            on_stcpay_mobile_submitted: function(mobile) {
                                console.log('🟡 STC Pay: Mobile number submitted', {
                                    mobile: mobile,
                                    order_id: '{{ $order->id }}',
                                    timestamp: new Date().toISOString()
                                });
                            },

                            on_stcpay_otp_requested: function() {
                                console.log('🟡 STC Pay: OTP requested, waiting for user input');
                            },

                            on_stcpay_otp_submitted: function() {
                                console.log('🟡 STC Pay: OTP submitted, processing payment');
                            },

                            // Apple Pay specific event handlers
                            on_applepay_initiated: function() {
                                console.log('🟡 Apple Pay: Payment initiated, waiting for Touch ID/Face ID');
                            },

                            on_applepay_authorized: function() {
                                console.log('🟡 Apple Pay: User authorized with Touch ID/Face ID');
                            },

                            // Samsung Pay specific event handlers
                            on_samsungpay_initiated: function() {
                                console.log('🟡 Samsung Pay: Payment initiated, waiting for authentication');
                            },

                            on_samsungpay_authorized: function() {
                                console.log('🟡 Samsung Pay: User authorized with Samsung Pay');
                            },

                            // Credit Card specific event handlers
                            on_creditcard_initiated: function() {
                                console.log('🟡 Credit Card: Payment initiated, waiting for card details');
                            },

                            on_creditcard_submitted: function(cardData) {
                                console.log('🟡 Credit Card: Card details submitted', {
                                    card_last4: cardData.last4 || 'N/A',
                                    card_brand: cardData.brand || 'N/A',
                                    order_id: '{{ $order->id }}',
                                    timestamp: new Date().toISOString()
                                });
                            },

                            // General payment method selection handler
                            on_payment_method_selected: function(method) {
                                console.log('🟡 Moyasar: Payment method selected:', method);

                                // Update UI to show selected method
                                const formContainer = document.querySelector('#moyasar-form-container');
                                if (formContainer) {
                                    // Remove any existing selection indicators
                                    formContainer.querySelectorAll('.payment-method-selected').forEach(el => el.classList.remove('payment-method-selected'));

                                    // Add selection indicator to the selected method
                                    const selectedMethod = formContainer.querySelector(`[class*="${method}"]`);
                                    if (selectedMethod) {
                                        selectedMethod.classList.add('payment-method-selected');
                                        console.log('🟢 Moyasar: Payment method selection indicator added');
                                    }
                                }
                            }
                        };

                        console.log('🔵 Moyasar: Configuration prepared', {
                            amount: config.amount,
                            currency: config.currency,
                            original_amount: {{ $order->total_amount }},
                            original_currency: '{{ currency() }}',
                            converted_amount_sar: {{ convertAmountToSAR($order->total_amount) }},
                            description: config.description,
                            api_key_length: config.publishable_api_key.length,
                            callback_url: config.callback_url,
                            methods: config.methods,
                            supported_networks: config.supported_networks,
                            stcpay_enabled: config.stcpay?.enabled || false,
                            stcpay_theme: config.stcpay?.theme || 'default',
                            applepay_enabled: config.apple_pay ? true : false,
                            applepay_country: config.apple_pay?.country || 'N/A',
                            samsungpay_enabled: config.samsung_pay ? true : false,
                            samsungpay_country: config.samsung_pay?.country || 'N/A'
                        });

                        // Log STC Pay specific configuration
                        if (config.stcpay?.enabled) {
                            console.log('🟢 STC Pay: Configuration enabled', {
                                theme: config.stcpay.theme,
                                payment_methods: ['stcpay'],
                                supported_currencies: ['SAR'],
                                mobile_number_required: true,
                                otp_challenge: true
                            });
                        }

                        // Log Apple Pay specific configuration
                        if (config.apple_pay) {
                            console.log('🟢 Apple Pay: Configuration enabled', {
                                country: config.apple_pay.country,
                                label: config.apple_pay.label,
                                merchant_capabilities: config.apple_pay.merchant_capabilities,
                                supported_countries: config.apple_pay.supported_countries,
                                touchid_faceid_required: true,
                                supported_networks: config.supported_networks
                            });
                        }

                        // Log Samsung Pay specific configuration
                        if (config.samsung_pay) {
                            console.log('🟢 Samsung Pay: Configuration enabled', {
                                service_id: config.samsung_pay.service_id,
                                order_number: config.samsung_pay.order_number,
                                country: config.samsung_pay.country,
                                label: config.samsung_pay.label,
                                environment: config.samsung_pay.environment,
                                supported_networks: config.supported_networks
                            });
                        }

                        try {
                            // Validate configuration before initialization
                            if (!config.publishable_api_key || config.publishable_api_key === 'pk_test_...') {
                                throw new Error('Invalid or missing Moyasar API key. Please check your Moyasar configuration.');
                            }

                            if (!config.publishable_api_key.startsWith('pk_')) {
                                throw new Error('Invalid Moyasar API key format. API key should start with "pk_"');
                            }

                            if (!config.amount || config.amount <= 0) {
                                throw new Error('Invalid amount for Moyasar payment. Amount must be greater than 0.');
                            }

                            if (config.amount < 100) {
                                console.warn('⚠️ Moyasar: Amount is very low. Minimum recommended amount is 1.00 SAR (100 halalas)');
                            }

                            // Check Moyasar library version
                            if (typeof Moyasar.version !== 'undefined') {
                                console.log('🔵 Moyasar: Library version:', Moyasar.version);

                                // Check for minimum version compatibility
                                const version = Moyasar.version;
                                if (version && version < '2.0.0') {
                                    console.warn('⚠️ Moyasar: Library version might be outdated. Current version:', version, 'Recommended: 2.0.0+');
                                }
                            } else {
                                console.warn('⚠️ Moyasar: Library version information not available');
                            }

                            // Debug Moyasar library capabilities
                            console.log('🔵 Moyasar: Library capabilities:', {
                                hasInit: typeof Moyasar.init === 'function',
                                hasVersion: typeof Moyasar.version !== 'undefined',
                                hasConfig: typeof Moyasar.config === 'function',
                                libraryType: typeof Moyasar
                            });

                                                        // Test basic Moyasar functionality
                            if (typeof Moyasar.init !== 'function') {
                                throw new Error('Moyasar.init is not a function. Library may not be properly loaded.');
                            }

                            // Check if Moyasar has the expected methods
                            const expectedMethods = ['init', 'config', 'version'];
                            const missingMethods = expectedMethods.filter(method => typeof Moyasar[method] !== 'function' && typeof Moyasar[method] === 'undefined');
                            if (missingMethods.length > 0) {
                                console.warn('⚠️ Moyasar: Missing expected methods:', missingMethods);
                            }

                            // Test if Moyasar is actually functional by checking its properties
                            console.log('🔵 Moyasar: Library object properties:', Object.getOwnPropertyNames(Moyasar));
                            console.log('🔵 Moyasar: Library prototype chain:', Object.getPrototypeOf(Moyasar));

                            // Moyasar will handle form rendering natively

                                                        // Check if the form element exists
                            const formElement = document.querySelector(config.element);
                            if (!formElement) {
                                throw new Error('Moyasar form element not found: ' + config.element);
                            }

                            // Clear the form container before initialization
                            formElement.innerHTML = '';

                            // Validate callback URL
                            if (!config.callback_url || !config.callback_url.startsWith('http')) {
                                console.warn('⚠️ Moyasar: Callback URL might be invalid:', config.callback_url);
                            }

                            // Validate payment methods
                            const validMethods = ['creditcard', 'stcpay', 'applepay', 'samsungpay'];
                            const invalidMethods = config.methods.filter(method => !validMethods.includes(method));
                            if (invalidMethods.length > 0) {
                                console.warn('⚠️ Moyasar: Invalid payment methods detected:', invalidMethods);
                            }

                                                        // Validate supported networks
                            const validNetworks = ['visa', 'mastercard', 'mada'];
                            const invalidNetworks = config.supported_networks.filter(network => !validNetworks.includes(network));
                            if (invalidNetworks.length > 0) {
                                console.warn('⚠️ Moyasar: Invalid supported networks detected:', invalidNetworks);
                            }

                            // Validate STC Pay configuration
                            if (config.stcpay && config.stcpay.enabled) {
                                if (!config.stcpay.mobile_number_required) {
                                    console.warn('⚠️ Moyasar: STC Pay mobile number requirement not properly configured');
                                }
                                if (!config.stcpay.otp_challenge) {
                                    console.warn('⚠️ Moyasar: STC Pay OTP challenge not properly configured');
                                }
                            }

                            // Validate Apple Pay configuration
                            if (config.apple_pay) {
                                if (!config.apple_pay.country) {
                                    console.warn('⚠️ Moyasar: Apple Pay country not configured');
                                }
                                if (!config.apple_pay.label) {
                                    console.warn('⚠️ Moyasar: Apple Pay label not configured');
                                }
                                if (!config.apple_pay.merchant_capabilities || config.apple_pay.merchant_capabilities.length === 0) {
                                    console.warn('⚠️ Moyasar: Apple Pay merchant capabilities not configured');
                                }
                            }

                            // Validate Samsung Pay configuration
                            if (config.samsung_pay) {
                                if (!config.samsung_pay.service_id) {
                                    console.warn('⚠️ Moyasar: Samsung Pay service ID not configured');
                                }
                                if (!config.samsung_pay.order_number) {
                                    console.warn('⚠️ Moyasar: Samsung Pay order number not configured');
                                }
                                if (!config.samsung_pay.country) {
                                    console.warn('⚠️ Moyasar: Samsung Pay country not configured');
                                }
                                if (!config.samsung_pay.label) {
                                    console.warn('⚠️ Moyasar: Samsung Pay label not configured');
                                }
                            }

                                                        // Validate environment configuration
                            if (config.samsung_pay && config.samsung_pay.environment) {
                                if (!['TEST', 'PRODUCTION'].includes(config.samsung_pay.environment)) {
                                    console.warn('⚠️ Moyasar: Samsung Pay environment should be TEST or PRODUCTION, got:', config.samsung_pay.environment);
                                }
                            }

                                                        // Validate currency configuration
                            if (config.currency !== 'SAR') {
                                console.warn('⚠️ Moyasar: Currency should be SAR for Saudi Arabia, got:', config.currency);
                            }

                            // Validate description configuration
                            if (!config.description || config.description.trim() === '') {
                                console.warn('⚠️ Moyasar: Description is empty or missing');
                            }

                            // Validate element configuration
                            if (!config.element || config.element.trim() === '') {
                                throw new Error('Moyasar element selector is empty or missing');
                            }

                            // Validate callback URL configuration
                            if (!config.callback_url || config.callback_url.trim() === '') {
                                throw new Error('Moyasar callback URL is empty or missing');
                            }

                            // Validate methods configuration
                            if (!config.methods || !Array.isArray(config.methods) || config.methods.length === 0) {
                                throw new Error('Moyasar payment methods are not properly configured');
                            }

                                                        // Validate supported networks configuration
                            if (!config.supported_networks || !Array.isArray(config.supported_networks) || config.supported_networks.length === 0) {
                                console.warn('⚠️ Moyasar: Supported networks are not properly configured');
                            }

                            // Validate STC Pay configuration
                            if (config.methods.includes('stcpay')) {
                                if (!config.stcpay || !config.stcpay.enabled) {
                                    console.warn('⚠️ Moyasar: STC Pay is in methods but not properly configured');
                                }
                            }

                            // Validate Apple Pay configuration
                            if (config.methods.includes('applepay')) {
                                if (!config.apple_pay) {
                                    console.warn('⚠️ Moyasar: Apple Pay is in methods but not properly configured');
                                }
                            }

                            // Validate Samsung Pay configuration
                            if (config.methods.includes('samsungpay')) {
                                if (!config.samsung_pay) {
                                    console.warn('⚠️ Moyasar: Samsung Pay is in methods but not properly configured');
                                }
                            }

                                                        // Validate credit card configuration
                            if (config.methods.includes('creditcard')) {
                                if (!config.supported_networks || config.supported_networks.length === 0) {
                                    console.warn('⚠️ Moyasar: Credit card is in methods but supported networks are not configured');
                                }
                            }

                            // Validate amount configuration
                            if (config.amount < 100) {
                                console.warn('⚠️ Moyasar: Amount is very low. Minimum recommended amount is 1.00 SAR (100 halalas)');
                            }

                            if (config.amount > 1000000) {
                                console.warn('⚠️ Moyasar: Amount is very high. Maximum recommended amount is 10,000.00 SAR (1,000,000 halalas)');
                            }

                            // Validate API key configuration
                            if (config.publishable_api_key.length < 20) {
                                console.warn('⚠️ Moyasar: API key seems too short, might be invalid');
                            }

                            if (config.publishable_api_key.length > 100) {
                                console.warn('⚠️ Moyasar: API key seems too long, might be invalid');
                            }

                            // Validate callback URL configuration
                            if (!config.callback_url.startsWith('http://') && !config.callback_url.startsWith('https://')) {
                                console.warn('⚠️ Moyasar: Callback URL should start with http:// or https://');
                            }

                            // Validate description configuration
                            if (config.description.length > 200) {
                                console.warn('⚠️ Moyasar: Description is too long, might be truncated');
                            }

                            // Validate element configuration
                            if (!config.element.startsWith('.') && !config.element.startsWith('#')) {
                                console.warn('⚠️ Moyasar: Element selector should start with . or #');
                            }

                            // Ensure the element selector is correct for the container
                            if (config.element !== '#moyasar-form-container') {
                                console.warn('⚠️ Moyasar: Element selector should be #moyasar-form-container for proper rendering');
                            }

                            // Validate the configuration object
                            console.log('🔵 Moyasar: Final configuration object:', JSON.stringify(config, null, 2));

                            // Check for any undefined or null values in config
                            const configIssues = [];
                            Object.keys(config).forEach(key => {
                                if (config[key] === undefined) {
                                    configIssues.push(`${key}: undefined`);
                                } else if (config[key] === null) {
                                    configIssues.push(`${key}: null`);
                                }
                            });

                            if (configIssues.length > 0) {
                                console.warn('⚠️ Moyasar: Configuration issues found:', configIssues);
                            }

                            // Validate methods configuration
                            if (config.methods.length === 0) {
                                throw new Error('Moyasar payment methods cannot be empty');
                            }

                            if (config.methods.length > 10) {
                                console.warn('⚠️ Moyasar: Too many payment methods configured');
                            }

                            // Validate supported networks configuration
                            if (config.supported_networks.length === 0) {
                                console.warn('⚠️ Moyasar: No supported networks configured');
                            }

                            if (config.supported_networks.length > 10) {
                                console.warn('⚠️ Moyasar: Too many supported networks configured');
                            }

                            // Validate STC Pay configuration
                            if (config.methods.includes('stcpay')) {
                                if (!config.stcpay.mobile_number_required) {
                                    console.warn('⚠️ Moyasar: STC Pay mobile number requirement not properly configured');
                                }
                                if (!config.stcpay.otp_challenge) {
                                    console.warn('⚠️ Moyasar: STC Pay OTP challenge not properly configured');
                                }
                            }

                            // Validate Apple Pay configuration
                            if (config.methods.includes('applepay')) {
                                if (!config.apple_pay.country) {
                                    console.warn('⚠️ Moyasar: Apple Pay country not properly configured');
                                }
                                if (!config.apple_pay.label) {
                                    console.warn('⚠️ Moyasar: Apple Pay label not properly configured');
                                }
                                if (!config.apple_pay.merchant_capabilities || config.apple_pay.merchant_capabilities.length === 0) {
                                    console.warn('⚠️ Moyasar: Apple Pay merchant capabilities not properly configured');
                                }
                            }

                            // Validate Samsung Pay configuration
                            if (config.methods.includes('samsungpay')) {
                                if (!config.samsung_pay.service_id) {
                                    console.warn('⚠️ Moyasar: Samsung Pay service ID not properly configured');
                                }
                                if (!config.samsung_pay.order_number) {
                                    console.warn('⚠️ Moyasar: Samsung Pay order number not properly configured');
                                }
                                if (!config.samsung_pay.country) {
                                    console.warn('⚠️ Moyasar: Samsung Pay country not properly configured');
                                }
                                if (!config.samsung_pay.label) {
                                    console.warn('⚠️ Moyasar: Samsung Pay label not properly configured');
                                }
                            }

                            // Validate environment configuration
                            if (config.samsung_pay && config.samsung_pay.environment) {
                                if (!['TEST', 'PRODUCTION'].includes(config.samsung_pay.environment)) {
                                    console.warn('⚠️ Moyasar: Samsung Pay environment should be TEST or PRODUCTION, got:', config.samsung_pay.environment);
                                }
                            }

                                                        // Validate currency configuration
                            if (config.currency !== 'SAR') {
                                console.warn('⚠️ Moyasar: Currency should be SAR for Saudi Arabia, got:', config.currency);
                            }

                            // Validate description configuration
                            if (!config.description || config.description.trim() === '') {
                                console.warn('⚠️ Moyasar: Description is empty or missing');
                            }

                            // Validate element configuration
                            if (!config.element || config.element.trim() === '') {
                                throw new Error('Moyasar element selector is empty or missing');
                            }

                            // Validate callback URL configuration
                            if (!config.callback_url || config.callback_url.trim() === '') {
                                throw new Error('Moyasar callback URL is empty or missing');
                            }

                            // Validate methods configuration
                            if (!config.methods || !Array.isArray(config.methods) || config.methods.length === 0) {
                                throw new Error('Moyasar payment methods are not properly configured');
                            }

                            console.log('🔵 Moyasar: Configuration validated, initializing form');

                            // Log the current state of the form container
                            const formContainer = document.querySelector('#moyasar-form-container');
                            console.log('🔵 Moyasar: Form container before initialization:', {
                                element: formContainer,
                                innerHTML: formContainer?.innerHTML,
                                children: formContainer?.children?.length || 0,
                                styles: formContainer ? window.getComputedStyle(formContainer) : null
                            });

                            try {
                                // Try to initialize Moyasar
                                Moyasar.init(config);
                                console.log('🟢 Moyasar: Form initialized successfully');

                                // Moyasar will handle form rendering natively

                            } catch (initError) {
                                console.error('🔴 Moyasar: Form initialization error:', initError);
                                // Moyasar will handle error display natively
                            }

                            // Check the form container after initialization
                            setTimeout(() => {
                                const updatedContainer = document.querySelector('#moyasar-form-container');
                                console.log('🔵 Moyasar: Form container after initialization:', {
                                    element: updatedContainer,
                                    innerHTML: updatedContainer?.innerHTML,
                                    children: updatedContainer?.children?.length || 0,
                                    styles: updatedContainer ? window.getComputedStyle(updatedContainer) : null
                                });

                                // Check for Moyasar-specific elements
                                const moyasarElements = updatedContainer?.querySelectorAll('[class*="mysr-"], [id*="mysr-"]');
                                console.log('🔵 Moyasar: Found Moyasar elements:', moyasarElements?.length || 0);

                                if (moyasarElements && moyasarElements.length > 0) {
                                    moyasarElements.forEach((el, index) => {
                                        console.log(`🔵 Moyasar: Element ${index}:`, {
                                            tagName: el.tagName,
                                            className: el.className,
                                            id: el.id,
                                            innerHTML: el.innerHTML.substring(0, 100) + '...',
                                            styles: window.getComputedStyle(el),
                                            visible: el.offsetWidth > 0 && el.offsetHeight > 0,
                                            display: window.getComputedStyle(el).display,
                                            visibility: window.getComputedStyle(el).visibility
                                        });

                                        // Force visibility if hidden
                                        if (window.getComputedStyle(el).display === 'none') {
                                            console.warn('⚠️ Moyasar: Element is hidden, forcing visibility');
                                            el.style.display = 'block';
                                        }
                                        if (window.getComputedStyle(el).visibility === 'hidden') {
                                            console.warn('⚠️ Moyasar: Element is invisible, forcing visibility');
                                            el.style.visibility = 'visible';
                                        }
                                    });
                                }

                                // Check if the form is actually visible
                                if (updatedContainer) {
                                    const rect = updatedContainer.getBoundingClientRect();
                                    console.log('🔵 Moyasar: Form container visibility:', {
                                        rect: rect,
                                        visible: rect.width > 0 && rect.height > 0,
                                        inViewport: rect.top >= 0 && rect.left >= 0 && rect.bottom <= window.innerHeight && rect.right <= window.innerWidth
                                    });

                                    // Check CSS properties that might hide the form
                                    const styles = window.getComputedStyle(updatedContainer);
                                    console.log('🔵 Moyasar: Form container CSS properties:', {
                                        display: styles.display,
                                        visibility: styles.visibility,
                                        opacity: styles.opacity,
                                        position: styles.position,
                                        zIndex: styles.zIndex,
                                        overflow: styles.overflow,
                                        height: styles.height,
                                        width: styles.width
                                    });

                                    // Force visibility if hidden
                                    if (styles.display === 'none') {
                                        console.warn('⚠️ Moyasar: Container is hidden, forcing visibility');
                                        updatedContainer.style.display = 'block';
                                    }
                                    if (styles.visibility === 'hidden') {
                                        console.warn('⚠️ Moyasar: Container is invisible, forcing visibility');
                                        updatedContainer.style.visibility = 'visible';
                                    }
                                }

                                                                                                // Check if Moyasar rendered an error message
                                const errorMessage = updatedContainer.querySelector('.text-red-600, .text-red-80, [class*="error"], [class*="Error"], [class*="red"]');
                                const somethingWentWrong = updatedContainer.textContent.includes('Something went wrong');

                                if (errorMessage || somethingWentWrong) {
                                    console.warn('⚠️ Moyasar: Error message detected:', errorMessage ? errorMessage.textContent : 'Something went wrong');
                                    // Moyasar will handle error display natively
                                }
                            }, 500);
                            moyasarInitialized = true;

                            // Log STC Pay specific initialization
                            console.log('🟢 STC Pay: Payment form ready for STC Pay transactions', {
                                mobile_input_ready: true,
                                otp_challenge_ready: true,
                                payment_flow: 'mobile_number → otp → payment_confirmation',
                                supported_methods: config.methods,
                                stcpay_enabled: config.stcpay?.enabled
                            });

                            // Log Apple Pay specific initialization
                            console.log('🟢 Apple Pay: Payment form ready for Apple Pay transactions', {
                                touchid_faceid_ready: true,
                                payment_flow: 'Touch ID/Face ID → payment_confirmation',
                                supported_methods: config.methods,
                                applepay_enabled: config.apple_pay ? true : false
                            });

                            // Log Samsung Pay specific initialization
                            console.log('🟢 Samsung Pay: Payment form ready for Samsung Pay transactions', {
                                samsung_pay_ready: true,
                                payment_flow: 'Samsung Pay → payment_confirmation',
                                supported_methods: config.methods,
                                samsungpay_enabled: config.samsung_pay ? true : false
                            });

                        } catch (error) {
                            console.error('🔴 Moyasar: Initialization failed', {
                                error: error.message,
                                stack: error.stack,
                                order_id: '{{ $order->id }}',
                                config: config,
                                moyasar_library: typeof Moyasar !== 'undefined' ? 'loaded' : 'not loaded',
                                moyasar_version: typeof Moyasar !== 'undefined' && Moyasar.version ? Moyasar.version : 'unknown'
                            });

                            // Show error message to user
                            alert('Payment form error: ' + error.message);

                            // Log STC Pay specific error if applicable
                            if (error.message && error.message.includes('stcpay')) {
                                console.error('🔴 STC Pay: Initialization failed', {
                                    error: error.message,
                                    stcpay_config: config.stcpay,
                                    methods: config.methods
                                });
                            }

                            // Log Apple Pay specific error if applicable
                            if (error.message && error.message.includes('applepay')) {
                                console.error('🔴 Apple Pay: Initialization failed', {
                                    error: error.message,
                                    applepay_config: config.apple_pay,
                                    methods: config.methods
                                });
                            }

                            // Log Samsung Pay specific error if applicable
                            if (error.message && error.message.includes('samsungpay')) {
                                console.error('🔴 Samsung Pay: Initialization failed', {
                                    error: error.message,
                                    samsungpay_config: config.samsung_pay,
                                    methods: config.methods
                                });
                            }
                        }
                    } else {
                        if (typeof Moyasar === 'undefined') {
                            console.error('🔴 Moyasar: Library not loaded');
                        } else if (moyasarInitialized) {
                            console.log('🟡 Moyasar: Already initialized');
                        }
                    }
                }

                // Handle Moyasar gateway selection
                let moyasarRadio = document.querySelector('input[data-class="Moyasar"]');
                const moyasarFormContainer = document.getElementById('moyasar-form-container');

                // If not found by data-class, try to find by other means
                if (!moyasarRadio) {
                    console.log('🔵 Moyasar: Not found by data-class="Moyasar", trying alternative methods');

                    // Try to find by looking for Moyasar in the class name or other attributes
                    const allGateways = document.querySelectorAll('input[name="gateway"]');
                    allGateways.forEach((gateway, index) => {
                        console.log('🔵 Moyasar: Checking gateway', index, ':', {
                            id: gateway.id,
                            value: gateway.value,
                            dataClass: gateway.dataset.class,
                            checked: gateway.checked
                        });

                        // Check if this gateway is Moyasar by looking at the label or other indicators
                        const label = document.querySelector(`label[for="${gateway.id}"]`);
                        if (label) {
                            const labelText = label.textContent.toLowerCase();
                            if (labelText.includes('moyasar') || labelText.includes('moyasar')) {
                                console.log('🔵 Moyasar: Found Moyasar gateway by label text:', labelText);
                                moyasarRadio = gateway;
                            }
                        }
                    });
                }

                // Enhanced Moyasar detection - also check by value and other attributes
                if (!moyasarRadio) {
                    console.log('🔵 Moyasar: Trying enhanced detection methods');

                    // Check all gateways for Moyasar indicators
                    const allGateways = document.querySelectorAll('input[name="gateway"]');
                    allGateways.forEach((gateway, index) => {
                        const dataClass = gateway.dataset.class;
                        const value = gateway.value;
                        const id = gateway.id;

                        console.log(`🔵 Moyasar: Gateway ${index} - data-class: "${dataClass}", value: "${value}", id: "${id}"`);

                        // Check multiple ways to identify Moyasar
                        if (dataClass && dataClass.toLowerCase().includes('moyasar')) {
                            console.log('🟢 Moyasar: Found by data-class containing "moyasar"');
                            moyasarRadio = gateway;
                        } else if (value && value.toString().toLowerCase().includes('moyasar')) {
                            console.log('🟢 Moyasar: Found by value containing "moyasar"');
                            moyasarRadio = gateway;
                        } else if (id && id.toLowerCase().includes('moyasar')) {
                            console.log('🟢 Moyasar: Found by id containing "moyasar"');
                            moyasarRadio = gateway;
                        }

                        // Also check the label text for Moyasar
                        const label = document.querySelector(`label[for="${gateway.id}"]`);
                        if (label) {
                            const labelText = label.textContent.toLowerCase();
                            if (labelText.includes('moyasar')) {
                                console.log('🟢 Moyasar: Found by label text containing "moyasar"');
                                moyasarRadio = gateway;
                            }
                        }
                    });
                }

                // Additional debugging for gateway detection
                console.log('🔵 Moyasar: Enhanced gateway detection completed');
                console.log('🔵 Moyasar: Found radio button:', moyasarRadio);
                if (moyasarRadio) {
                    console.log('🔵 Moyasar: Radio button details:', {
                        id: moyasarRadio.id,
                        value: moyasarRadio.value,
                        dataClass: moyasarRadio.dataset.class,
                        checked: moyasarRadio.checked
                    });
                }

                console.log('🔵 Moyasar: Looking for radio button with data-class="Moyasar"');
                console.log('🔵 Moyasar: Found radio button:', moyasarRadio);
                console.log('🔵 Moyasar: Found form container:', moyasarFormContainer);

                if (moyasarRadio && moyasarFormContainer) {
                    console.log('🔵 Moyasar: Gateway radio button found, setting up event listeners');
                    console.log('🔵 Moyasar: Radio button details:', {
                        id: moyasarRadio.id,
                        value: moyasarRadio.value,
                        dataClass: moyasarRadio.dataset.class,
                        checked: moyasarRadio.checked
                    });

                    // Check if Moyasar is already selected on page load
                    if (moyasarRadio.checked) {
                        console.log('🟡 Moyasar: Already selected on page load');
                        moyasarSelected = true;
                        // Don't show form immediately, just track selection
                    }

                    moyasarRadio.addEventListener('change', function() {
                        console.log('🔵 Moyasar: Radio button change event fired');
                        console.log('🔵 Moyasar: New state - checked:', this.checked);

                        if (this.checked) {
                            console.log('🟢 Moyasar: Gateway selected by user');
                            moyasarSelected = true;
                            // Don't show form immediately, just track selection
                        } else {
                            console.log('🟡 Moyasar: Gateway deselected by user');
                            moyasarSelected = false;
                        }

                        console.log('🔵 Moyasar: Current moyasarSelected state:', moyasarSelected);
                    });

                    // Hide Moyasar form when other gateways are selected
                    const otherGateways = document.querySelectorAll('input[name="gateway"]:not([data-class="Moyasar"])');
                    console.log('🔵 Moyasar: Found', otherGateways.length, 'other payment gateways');

                    otherGateways.forEach((gateway, index) => {
                        console.log('🔵 Moyasar: Other gateway', index, ':', {
                            id: gateway.id,
                            value: gateway.value,
                            dataClass: gateway.dataset.class
                        });

                        gateway.addEventListener('change', function() {
                            if (this.checked) {
                                console.log('🟡 Moyasar: Other gateway selected, hiding Moyasar form', {
                                    gateway_id: this.id,
                                    gateway_value: this.value,
                                    gateway_class: this.dataset.class
                                });
                                moyasarSelected = false;
                                console.log('🔵 Moyasar: moyasarSelected set to false');
                            }
                        });
                    });
                } else {
                    if (!moyasarRadio) {
                        console.warn('🟡 Moyasar: Gateway radio button not found');
                        // Let's search for all gateway radio buttons to debug
                        const allGateways = document.querySelectorAll('input[name="gateway"]');
                        console.log('🔵 Moyasar: All gateway radio buttons found:', allGateways.length);
                        allGateways.forEach((gateway, index) => {
                            console.log('🔵 Moyasar: Gateway', index, ':', {
                                id: gateway.id,
                                value: gateway.value,
                                dataClass: gateway.dataset.class,
                                checked: gateway.checked
                            });
                        });

                        // Last resort: try to find Moyasar by checking all gateways on every click
                        console.log('🔵 Moyasar: Setting up fallback detection on Pay Now button click');
                    }
                    if (!moyasarFormContainer) {
                        console.warn('🟡 Moyasar: Form container not found');
                    }
                }

                // Modal functionality
                const moyasarModal = document.getElementById('moyasar-modal');
                const moyasarModalClose = document.getElementById('moyasar-modal-close');

                if (moyasarModal && moyasarModalClose) {
                    console.log('🔵 Moyasar: Modal and close button found, setting up event listeners');

                    // Close modal when clicking close button
                    moyasarModalClose.addEventListener('click', function(e) {
                        console.log('🔵 Moyasar: Modal close button clicked');
                        e.preventDefault();
                        e.stopPropagation();
                        hideMoyasarModal();
                    });

                    // Close modal when clicking overlay
                    moyasarModal.addEventListener('click', function(e) {
                        if (e.target === moyasarModal) {
                            console.log('🔵 Moyasar: Modal overlay clicked');
                            hideMoyasarModal();
                        }
                    });

                    // Close modal with Escape key
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape' && moyasarModal.classList.contains('active')) {
                            console.log('🔵 Moyasar: Escape key pressed, closing modal');
                            hideMoyasarModal();
                        }
                    });
                }

                                // Functions to show/hide modal
                function showMoyasarModal() {
                    const moyasarModal = document.getElementById('moyasar-modal');
                    if (moyasarModal) {
                        console.log('🔵 Moyasar: Showing modal');

                        // Show modal
                        moyasarModal.style.display = 'block';
                        moyasarModal.classList.add('active');
                        document.body.style.overflow = 'hidden';

                        // Initialize Moyasar form when modal is shown
                        setTimeout(() => {
                            console.log('🔵 Moyasar: Initializing payment form in modal');
                            initMoyasar();
                        }, 100);
                    } else {
                        console.error('🔴 Moyasar: Modal element not found!');
                    }
                }

                function hideMoyasarModal() {
                    const moyasarModal = document.getElementById('moyasar-modal');
                    if (moyasarModal) {
                        console.log('🔵 Moyasar: Hiding modal');
                        moyasarModal.classList.remove('active');
                        moyasarModal.style.display = 'none';
                        document.body.style.overflow = '';
                        console.log('🔵 Moyasar: Modal hidden successfully');
                    } else {
                        console.error('🔴 Moyasar: Modal element not found in hideMoyasarModal');
                    }
                }

                                // Integrate with Pay Now button
                const payNowButton = document.querySelector('.js-cart-payment-btn');
                if (payNowButton) {
                    console.log('🔵 Moyasar: Pay Now button found, setting up click handler');

                    payNowButton.addEventListener('click', function(e) {
                        console.log('🔵 Moyasar: Pay Now button clicked');
                        console.log('🔵 Moyasar: Current moyasarSelected state:', moyasarSelected);
                        console.log('🔵 Moyasar: Event details:', {
                            type: e.type,
                            target: e.target,
                            currentTarget: e.currentTarget,
                            defaultPrevented: e.defaultPrevented
                        });

                        // Fallback detection: check if Moyasar is selected right now
                        if (!moyasarSelected) {
                            console.log('🔵 Moyasar: Fallback detection - checking current gateway selection');
                            const selectedGateway = document.querySelector('input[name="gateway"]:checked');
                            if (selectedGateway) {
                                const dataClass = selectedGateway.dataset.class;
                                const value = selectedGateway.value;
                                const id = selectedGateway.id;

                                console.log('🔵 Moyasar: Selected gateway details:', { dataClass, value, id });

                                // Check if this is Moyasar
                                if (dataClass && dataClass.toLowerCase().includes('moyasar')) {
                                    console.log('🟢 Moyasar: Fallback detection found Moyasar gateway!');
                                    moyasarSelected = true;
                                    moyasarRadio = selectedGateway;
                                } else if (value && value.toString().toLowerCase().includes('moyasar')) {
                                    console.log('🟢 Moyasar: Fallback detection found Moyasar gateway by value!');
                                    moyasarSelected = true;
                                    moyasarRadio = selectedGateway;
                                } else if (id && id.toLowerCase().includes('moyasar')) {
                                    console.log('🟢 Moyasar: Fallback detection found Moyasar gateway by id!');
                                    moyasarSelected = true;
                                    moyasarRadio = selectedGateway;
                                }

                                // Also check label text
                                const label = document.querySelector(`label[for="${selectedGateway.id}"]`);
                                if (label && label.textContent.toLowerCase().includes('moyasar')) {
                                    console.log('🟢 Moyasar: Fallback detection found Moyasar gateway by label text!');
                                    moyasarSelected = true;
                                    moyasarRadio = selectedGateway;
                                }
                            } else {
                                console.log('🔵 Moyasar: No gateway currently selected');
                            }
                        }

                        // Check if Moyasar is selected
                        if (moyasarSelected) {
                            console.log('🟢 Moyasar: Moyasar selected, showing modal');
                            e.preventDefault(); // Prevent form submission
                            e.stopPropagation(); // Stop event bubbling
                            showMoyasarModal();
                            return false; // Ensure form doesn't submit
                        } else {
                            console.log('🟡 Moyasar: Moyasar not selected, allowing normal form submission');
                            console.log('🔵 Moyasar: Available payment gateways:');
                            const allGateways = document.querySelectorAll('input[name="gateway"]');
                            allGateways.forEach((gateway, index) => {
                                console.log('🔵 Moyasar: Gateway', index, ':', {
                                    id: gateway.id,
                                    value: gateway.value,
                                    dataClass: gateway.dataset.class,
                                    checked: gateway.checked
                                });
                            });
                            // Allow normal form submission for other payment methods
                        }
                    });
                } else {
                    console.warn('🟡 Moyasar: Pay Now button not found');
                }

                                console.log('🔵 Moyasar: Event listeners setup completed');
                console.log('🔵 Moyasar: Ready for payment processing');

                // Summary of Moyasar integration
                console.log('🔵 Moyasar: Integration Summary', {
                    gateway_detection: 'Enhanced detection by data-class, value, and id',
                    modal_functionality: 'Modal shows when Moyasar is selected and Pay Now is clicked',
                    form_initialization: 'Moyasar form initializes in modal with proper configuration',
                    payment_methods: ['creditcard', 'stcpay', 'applepay', 'samsungpay'],
                    fallback_detection: 'Fallback detection on Pay Now button click'
                });



                // STC Pay Debug Information
                console.log('🔵 STC Pay: Debug information loaded', {
                    payment_methods: ['creditcard', 'stcpay', 'applepay', 'samsungpay'],
                    stcpay_flow: {
                        step1: 'User enters mobile number',
                        step2: 'SMS OTP sent to mobile',
                        step3: 'User enters OTP code',
                        step4: 'Payment processed and confirmed'
                    },
                    stcpay_requirements: {
                        mobile_number: 'STC Pay registered mobile',
                        otp_verification: 'Required for security',
                        sar_currency: 'Only SAR supported',
                        merchant_id: 'Required from STC Pay portal'
                    },
                    applepay_flow: {
                        step1: 'User clicks Apple Pay button',
                        step2: 'Touch ID/Face ID authentication',
                        step3: 'Payment processed and confirmed'
                    },
                    applepay_requirements: {
                        apple_device: 'T1 security chip required',
                        touchid_faceid: 'Required for authentication',
                        sar_currency: 'Only SAR supported',
                        merchant_validation: 'Required from Apple'
                    },
                    samsungpay_flow: {
                        step1: 'User clicks Samsung Pay button',
                        step2: 'Samsung Pay authentication',
                        step3: 'Payment processed and confirmed'
                    },
                    samsungpay_requirements: {
                        samsung_device: 'Samsung device required',
                        saudi_cards: 'Cards must be issued in Saudi Arabia',
                        sar_currency: 'Only SAR supported',
                        service_id: 'Required from Samsung Pay portal'
                    },
                    moyasar_integration: {
                        library_version: '2.0.17',
                        stcpay_enabled: true,
                        applepay_enabled: true,
                        samsungpay_enabled: true,
                        callback_handling: 'Automatic',
                        payment_verification: 'Backend verification'
                    }
                });
            });
        </script>
    @endif

@endsection

@push('scripts_bottom')
    <script>
        var hasErrors = '{{ (!empty($errors) and count($errors)) ? 'true' : 'false' }}';
        var hasErrorsHintLang = '{{ trans('update.please_check_the_errors_in_the_shipping_form') }}';
        var selectPaymentGatewayLang = '{{ trans('update.select_a_payment_gateway') }}';
        var pleaseWaitLang = '{{ trans('update.please_wait') }}';
        var transferringToLang = '{{ trans('update.transferring_to_the_payment_gateway') }}';

        // Global payment method selection function
        function selectPaymentMethod(method) {
            console.log('🔵 Payment method selected:', method);
            // Remove active class from all cards
            document.querySelectorAll('.payment-method-card').forEach(card => {
                card.classList.remove('active');
            });
            // Add active class to selected card
            event.currentTarget.classList.add('active');
            // Store selected method
            window.selectedPaymentMethod = method;
        }

        // Function to process working payment
        function processWorkingPayment() {
            const method = window.selectedPaymentMethod;
            if (!method) {
                alert('Please select a payment method first');
                return;
            }
            console.log('🔵 Moyasar: Processing working payment with method:', method);

            // Initialize Moyasar payment form directly
            initMoyasar();
        }

        // Function to process fallback payment
        function processFallbackPayment() {
            const method = window.selectedPaymentMethod;
            if (!method) {
                alert('Please select a payment method first');
                return;
            }
            console.log('🔵 Moyasar: Processing fallback payment with method:', method);
            alert('Fallback payment processing for ' + method + '. This is a demo implementation.');
        }

        // Moyasar initialization is handled by the main function above
        // The initMoyasar function is defined in the main script section above







        // BNPL functionality
        document.addEventListener('DOMContentLoaded', function() {
            const bnplRadio = document.getElementById('gateway_bnpl');
            const bnplProviderSelection = document.getElementById('bnpl-provider-selection');
            const bnplProviderRadios = document.querySelectorAll('input[name="bnpl_provider"]');

            if (bnplRadio && bnplProviderSelection) {
                bnplRadio.addEventListener('change', function() {
                    if (this.checked) {
                        bnplProviderSelection.style.display = 'block';
                        // Require BNPL provider selection
                        bnplProviderRadios.forEach(radio => {
                            radio.required = true;
                        });
                    } else {
                        bnplProviderSelection.style.display = 'none';
                        // Remove required attribute
                        bnplProviderRadios.forEach(radio => {
                            radio.required = false;
                        });
                    }
                });

                // Hide BNPL provider selection when other gateways are selected
                const otherGateways = document.querySelectorAll('input[name="gateway"]:not(#gateway_bnpl)');
                otherGateways.forEach(gateway => {
                    gateway.addEventListener('change', function() {
                        if (this.checked) {
                            bnplProviderSelection.style.display = 'none';
                            bnplProviderRadios.forEach(radio => {
                                radio.required = false;
                            });
                        }
                    });
                });

                // Handle BNPL provider selection
                bnplProviderRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (this.checked) {
                            console.log('BNPL provider selected:', this.value);

                            // Check if it's Tabby and show popup
                            if (this.getAttribute('data-provider-name') === 'Tabby') {
                                showTabbyModal();
                            }
                            // Check if it's MisPay and show popup
                            else if (this.getAttribute('data-provider-name') === 'MisPay') {
                                showMisPayModal();
                            }
                        }
                    });
                });
            }
        });

        // Tabby Modal Functions
        function showTabbyModal() {
            const modal = document.getElementById('tabby-modal');
            modal.classList.add('active');
            modal.style.display = 'block';

            // Check eligibility
            checkTabbyEligibility();
        }

        function showMisPayModal() {
            const modal = document.getElementById('mispay-modal');
            modal.classList.add('active');
            modal.style.display = 'block';

            // Check eligibility
            checkMisPayEligibility();
        }

        function hideTabbyModal() {
            const modal = document.getElementById('tabby-modal');
            modal.classList.remove('active');
            modal.style.display = 'none';
        }

        function checkTabbyEligibility() {
            const container = document.getElementById('tabby-form-container');

            // Show loading state
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ trans('update.tabby_eligibility_check') }}</span>
                    </div>
                    <p class="mt-3 text-muted">{{ trans('update.tabby_eligibility_check') }}</p>
                </div>
            `;

            // Get order details
            const orderId = document.querySelector('input[name="order_id"]').value;
            const totalAmount = {{ $calculatePrices["total"] ?? 0 }};
            const currency = '{{ $order->currency ?? "SAR" }}';

            // Make AJAX call to check eligibility
            fetch('/api/tabby/check-eligibility', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    amount: totalAmount,
                    currency: currency
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Tabby eligibility response:', data);
                if (data.success && data.eligible) {
                    showTabbyEligibleState(data);
                } else {
                    showTabbyIneligibleState(data);
                }
            })
            .catch(error => {
                console.error('Tabby eligibility check failed:', error);
                showTabbyErrorState();
            });
        }

        function showTabbyEligibleState(data) {
            const container = document.getElementById('tabby-form-container');
            const totalAmount = {{ $calculatePrices["total"] ?? 0 }};
            const installmentCount = 4; // Tabby default
            const monthlyPayment = (totalAmount / installmentCount).toFixed(2);

            container.innerHTML = `
                <div class="tabby-logo">T</div>
                <h5>{{ trans('update.tabby_pay_later') }}</h5>
                <p class="text-muted">{{ trans('update.tabby_use_any_card') }}</p>

                <div class="tabby-installment-info">
                    <h5>Installment Details</h5>
                    <div class="tabby-installment-detail">
                        <span class="label">Total Amount:</span>
                        <span class="value">${totalAmount} {{ $order->currency ?? "SAR" }}</span>
                    </div>
                    <div class="tabby-installment-detail">
                        <span class="label">Installments:</span>
                        <span class="value">${installmentCount}</span>
                    </div>
                    <div class="tabby-installment-detail">
                        <span class="label">Monthly Payment:</span>
                        <span class="value">${monthlyPayment} {{ $order->currency ?? "SAR" }}</span>
                    </div>
                    <div class="tabby-installment-detail">
                        <span class="label">Fees:</span>
                        <span class="value text-success">No fees</span>
                    </div>
                </div>

                <div class="tabby-eligibility-status success">
                    <i class="fas fa-check-circle"></i> You are eligible for Tabby installments!
                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-primary btn-lg" onclick="proceedWithTabby()">
                        {{ trans('update.tabby_processing') }}
                    </button>
                </div>
            `;
        }

        function showTabbyIneligibleState(data) {
            const container = document.getElementById('tabby-form-container');
            const reason = data.rejection_reason || 'not_available';
            const error = data.error || '';
            let message = '';

            // Log the rejection data for debugging
            console.log('Tabby rejection data:', data);

            switch(reason) {
                case 'order_amount_too_high':
                    message = 'This purchase is above your current spending limit with Tabby, try a smaller cart or use another payment method';
                    break;
                case 'order_amount_too_low':
                    message = 'The purchase amount is below the minimum amount required to use Tabby, try adding more items or use another payment method';
                    break;
                case 'basic_criteria_not_met':
                    message = 'Sorry, you do not meet the basic eligibility criteria for Tabby installments.';
                    break;
                default:
                    if (error) {
                        message = `Tabby eligibility check failed: ${error}`;
                    } else {
                        message = 'Sorry, Tabby is unable to approve this purchase. Please use an alternative payment method for your order.';
                    }
            }

            container.innerHTML = `
                <div class="tabby-logo">T</div>
                <h5>{{ trans('update.tabby_pay_later') }}</h5>

                <div class="tabby-eligibility-status error">
                    <i class="fas fa-times-circle"></i> ${message}
                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-secondary" onclick="hideTabbyModal()">
                        Close
                    </button>
                </div>
            `;
        }

        function showTabbyErrorState() {
            const container = document.getElementById('tabby-form-container');

            container.innerHTML = `
                <div class="tabby-logo">T</div>
                <h5>{{ trans('update.tabby_pay_later') }}</h5>

                <div class="tabby-eligibility-status warning">
                    <i class="fas fa-exclamation-triangle"></i> Unable to check eligibility at this time. Please try again later.
                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-secondary" onclick="hideTabbyModal()">
                        Close
                    </button>
                </div>
            </div>
            `;
        }

        function proceedWithTabby() {
            const container = document.getElementById('tabby-form-container');

            // Show processing state
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ trans('update.tabby_processing') }}</span>
                    </div>
                    <p class="mt-3 text-muted">{{ trans('update.tabby_processing') }}</p>
                </div>
            `;

            // Get order details
            const orderId = document.querySelector('input[name="order_id"]').value;
            const bnplProviderId = document.querySelector('input[name="bnpl_provider"]:checked').value;

            // Submit the form with BNPL provider
            const form = document.querySelector('form[action="/payments/payment-request"]');
            const gatewayInput = form.querySelector('input[name="gateway"]');
            const bnplInput = form.querySelector('input[name="bnpl_provider"]');

            // Set values
            gatewayInput.value = 'bnpl';
            bnplInput.value = bnplProviderId;

            // Submit form
            form.submit();
        }

        // Tabby Modal Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            const tabbyModalClose = document.getElementById('tabby-modal-close');
            const tabbyModalOverlay = document.querySelector('.tabby-modal-overlay');

            if (tabbyModalClose) {
                tabbyModalClose.addEventListener('click', hideTabbyModal);
            }

            if (tabbyModalOverlay) {
                tabbyModalOverlay.addEventListener('click', hideTabbyModal);
            }
        });

        // MisPay Modal Functions
        function hideMisPayModal() {
            const modal = document.getElementById('mispay-modal');
            modal.classList.remove('active');
            modal.style.display = 'none';
        }

        function checkMisPayEligibility() {
            const container = document.getElementById('mispay-form-container');

            // Show loading state
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ trans('update.mispay_eligibility_check') }}</span>
                    </div>
                    <p class="mt-3 text-muted">{{ trans('update.mispay_eligibility_check') }}</p>
                </div>
            `;

            // Get order details
            const orderId = document.querySelector('input[name="order_id"]').value;
            const totalAmount = {{ $calculatePrices["total"] ?? 0 }};
            const currency = '{{ $order->currency ?? "SAR" }}';

            // Make AJAX call to check eligibility
            fetch('/api/mispay/check-eligibility', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    amount: totalAmount,
                    currency: currency
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.eligible) {
                    showMisPayEligibleState(data);
                } else {
                    showMisPayIneligibleState(data);
                }
            })
            .catch(error => {
                console.error('MisPay eligibility check failed:', error);
                showMisPayErrorState();
            });
        }

        function showMisPayEligibleState(data) {
            const container = document.getElementById('mispay-form-container');
            const totalAmount = {{ $calculatePrices["total"] ?? 0 }};
            const installmentOptions = data.installment_options || [];
            let installmentHtml = '';

            if (installmentOptions.length > 0) {
                installmentHtml = `
                    <div class="tabby-installment-info">
                        <h5>Installment Options</h5>
                        ${installmentOptions.map(option => `
                            <div class="tabby-installment-detail">
                                <span class="label">${option.months} Months:</span>
                                <span class="value">${option.monthly_payment} ${option.currency || 'SAR'}/month</span>
                            </div>
                        `).join('')}
                        <div class="tabby-installment-detail">
                            <span class="label">Total Amount:</span>
                            <span class="value">${totalAmount} {{ $order->currency ?? "SAR" }}</span>
                        </div>
                        <div class="tabby-installment-detail">
                            <span class="label">Fees:</span>
                            <span class="value text-success">No fees</span>
                        </div>
                    </div>
                `;
            }

            container.innerHTML = `
                <div class="tabby-logo">M</div>
                <h5>{{ trans('update.mispay_pay_later') }}</h5>
                <p class="text-muted">{{ trans('update.mispay_use_any_card') }}</p>

                ${installmentHtml}

                <div class="tabby-eligibility-status success">
                    <i class="fas fa-check-circle"></i> You are eligible for MisPay installments!
                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-primary btn-lg" onclick="proceedWithMisPay()">
                        {{ trans('update.mispay_processing') }}
                    </button>
                </div>
            `;
        }

        function showMisPayIneligibleState(data) {
            const container = document.getElementById('mispay-form-container');
            const reason = data.rejection_reason || 'not_available';
            let message = '';

            switch(reason) {
                case 'amount_too_low':
                    message = 'The purchase amount is below the minimum amount required to use MisPay (SAR 50). Please add more items or use another payment method.';
                    break;
                case 'amount_too_high':
                    message = 'This purchase is above the maximum amount allowed for MisPay (SAR 50,000). Please reduce your cart or use another payment method.';
                    break;
                case 'missing_contact_info':
                    message = 'Please provide valid email and phone number to use MisPay installments.';
                    break;
                default:
                    message = 'Sorry, you are not eligible for MisPay installments at this time. Please use an alternative payment method.';
            }

            container.innerHTML = `
                <div class="tabby-logo">M</div>
                <h5>{{ trans('update.mispay_pay_later') }}</h5>

                <div class="tabby-eligibility-status error">
                    <i class="fas fa-times-circle"></i> ${message}
                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-secondary" onclick="hideMisPayModal()">
                        Close
                    </button>
                </div>
            `;
        }

        function showMisPayErrorState() {
            const container = document.getElementById('mispay-form-container');

            container.innerHTML = `
                <div class="tabby-logo">M</div>
                <h5>{{ trans('update.mispay_pay_later') }}</h5>

                <div class="tabby-eligibility-status warning">
                    <i class="fas fa-exclamation-triangle"></i> Unable to check eligibility at this time. Please try again later.
                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-secondary" onclick="hideMisPayModal()">
                        Close
                    </button>
                </div>
            `;
        }

        function proceedWithMisPay() {
            const container = document.getElementById('mispay-form-container');

            // Show processing state
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ trans('update.mispay_processing') }}</span>
                    </div>
                    <p class="mt-3 text-muted">{{ trans('update.mispay_processing') }}</p>
                </div>
            `;

            // Get order details
            const orderId = document.querySelector('input[name="order_id"]').value;
            const bnplProviderId = document.querySelector('input[name="bnpl_provider"]:checked').value;

            // Submit the form with BNPL provider
            const form = document.querySelector('form[action="/payments/payment-request"]');
            const gatewayInput = form.querySelector('input[name="gateway"]');
            const bnplInput = form.querySelector('input[name="bnpl_provider"]');

            // Set values
            gatewayInput.value = 'bnpl';
            bnplInput.value = bnplProviderId;

            // Submit form
            form.submit();
        }

        // MisPay Modal Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            const mispayModalClose = document.getElementById('mispay-modal-close');
            const mispayModalOverlay = document.querySelector('#mispay-modal .tabby-modal-overlay');

            if (mispayModalClose) {
                mispayModalClose.addEventListener('click', hideMisPayModal);
            }

            if (mispayModalOverlay) {
                mispayModalOverlay.addEventListener('click', hideMisPayModal);
            }
        });
    </script>
<script src="{{ getDesign1ScriptPath('cart_page') }}"></script>

@endpush
