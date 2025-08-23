@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("system_status_pages") }}">
@endpush

@section("content")
    <section class="container mt-96 mb-104 position-relative">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="system-status-page-section position-relative">
                    <div class="system-status-page-section__mask"></div>

                    <div class="position-relative d-flex-center flex-column bg-white rounded-32 px-24 px-lg-40 py-54 py-lg-100 text-center z-index-2">

                        @if($order->status === \App\Models\Order::$paid)
                            <div class="system-status-page-image">
                                <img src="/assets/design_1/img/cart/successful_payment.png" alt="{{ trans('update.successful_payment') }}" class="img-cover">
                            </div>

                            <h1 class="font-16 font-weight-bold mt-14">
                                @if($order->isBnplPayment())
                                    {{ trans('update.bnpl_payment_successful') }}
                                @else
                                    {{ trans('update.successful_payment') }}
                                @endif
                            </h1>

                            <p class="font-14 text-gray-500 mt-4">
                                @if($order->isBnplPayment())
                                    {{ trans('update.bnpl_payment_successful_hint') }}
                                @else
                                    {{ trans('update.successful_payment_hint') }}
                                @endif
                            </p>

                            {{-- BNPL Payment Details --}}
                            @if($order->isBnplPayment())
                                <div class="mt-24 p-20 rounded-16 bg-gray-50 text-left">
                                    <h4 class="font-14 font-weight-bold mb-16">{{ trans('update.payment_details') }}</h4>

                                    <div class="row">
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex justify-content-between align-items-center mb-8">
                                                <span class="font-14 text-gray-600">{{ trans('update.order_id') }}</span>
                                                <span class="font-14 font-weight-bold">#{{ $order->id }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-8">
                                                <span class="font-14 text-gray-600">{{ trans('update.bnpl_provider') }}</span>
                                                <span class="font-14 font-weight-bold">{{ $order->bnpl_provider }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-8">
                                                <span class="font-14 text-gray-600">{{ trans('update.installments') }}</span>
                                                <span class="font-14 font-weight-bold">{{ $order->installment_count }}</span>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex justify-content-between align-items-center mb-8">
                                                <span class="font-14 text-gray-600">{{ trans('update.installment_amount') }}</span>
                                                <span class="font-14 font-weight-bold">{{ currency() }} {{ $order->getInstallmentAmount() }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-8">
                                                <span class="font-14 text-gray-600">{{ trans('update.next_payment') }}</span>
                                                <span class="font-14 font-weight-bold">{{ $order->getNextInstallmentDueDate() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="d-flex align-items-center gap-16 mt-16">
                                <a href="academyapp://payment-success" class="btn btn-lg btn-outline-primary d-flex d-sm-none">{{ trans('update.redirect_to_app') }}</a>

                                <a href="{{ route('panel.dashboard') }}" class="btn btn-primary btn-lg">{{ trans('public.my_panel') }}</a>
                            </div>
                        @else
                            <div class="system-status-page-image">
                                <img src="/assets/design_1/img/cart/failed_payment.png" alt="{{ trans('update.failed_payment') }}" class="img-cover">
                            </div>

                            <h1 class="font-16 font-weight-bold mt-14">{{ trans('update.failed_payment') }}</h1>

                            <p class="font-14 text-gray-500 mt-4">{{ trans('update.failed_payment_hint') }}</p>

                            <div class="d-flex align-items-center gap-16 mt-16">
                                <a href="academyapp://payment-failed" class="btn btn-lg btn-outline-primary d-flex d-sm-none">{{ trans('update.redirect_to_app') }}</a>

                                <a href="{{ route('panel.dashboard') }}" class="btn btn-primary btn-lg">{{ trans('public.my_panel') }}</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts_bottom')

@endpush
