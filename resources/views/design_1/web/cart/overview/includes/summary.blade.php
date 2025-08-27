<div class="card-with-mask position-relative">
    <div class="mask-8-white"></div>

    <div class="position-relative z-index-2 bg-white rounded-16 p-16 w-100 h-100">
        <h5 class="font-14">{{ trans('home.order_summary') }}</h5>

        <div class="d-flex align-items-center justify-content-between mt-20">
            <span class="text-gray-500">{{ trans('update.subtotal') }}</span>
            <span class="js-cart-subtotal">{{ handlePrice($calculatePrices["sub_total"]) }}</span>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-16">
            <span class="text-gray-500">{{ trans('update.discount') }}</span>
            <span class="js-cart-discount">{{ !empty($calculatePrices["total_discount"]) ? handlePrice($calculatePrices["total_discount"]) : 0 }}</span>
        </div>

        @if(!empty($calculatePrices['discountCoupon']))
            <input type="hidden" name="discount_id" value="{{ $calculatePrices['discountCoupon']->id }}">

            <div class="js-coupon-card-in-summary d-flex align-items-center justify-content-between mt-12 p-12 rounded-8 bg-gray-100 border-gray-300">
                <div class="d-flex align-items-center font-12 text-gray-500">
                    <span class="">{{ $calculatePrices['discountCoupon']->code }}</span>
                    <span class="ml-4 font-weight-bold">({{ $calculatePrices['discountCoupon']->percent }}%)</span>
                </div>

                <button type="button" class="js-remove-coupon-btn btn-transparent">
                    <x-iconsax-lin-add class="close-icon text-danger" width="14px" height="14px"/>
                </button>
            </div>
        @endif

        <div class="d-flex align-items-center justify-content-between mt-16">
            <div class="d-flex align-items-center gap-4 text-gray-500">
                <span class="">{{ trans('cart.tax') }}</span>

                @if(empty($calculatePrices["tax_is_different"]))
                    <span class="">({{ $calculatePrices["tax"] }}%)</span>
                @endif
            </div>

            <span class="js-cart-tax">{{ !empty($calculatePrices["tax_price"]) ? handlePrice($calculatePrices["tax_price"]) : 0 }}</span>
        </div>

        @if(!empty($calculatePrices["product_delivery_fee"]))
            <div class="d-flex align-items-center justify-content-between mt-16">
                <span class="text-gray-500">{{ trans('update.delivery_fee') }}</span>
                <span class="js-cart-delivery_fee">{{ handlePrice($calculatePrices["product_delivery_fee"]) }}</span>
            </div>
        @endif

        {{-- BNPL Information --}}
        @if(!empty($carts) && $carts->where('bnpl_provider')->count() > 0)
            <div class="cart-summary-divider"></div>

            <div class="d-flex align-items-center justify-content-between mt-16">
                <span class="text-gray-500">{{ trans('update.bnpl_selected') }}</span>
                <span class="js-cart-bnpl-info font-12 text-primary">
                    @php
                        $bnplCarts = $carts->where('bnpl_provider');
                        $bnplProvider = $bnplCarts->first()->bnpl_provider;
                        $bnplInstallments = $bnplCarts->first()->bnpl_installments;
                    @endphp
                    {{ $bnplProvider }} ({{ $bnplInstallments }} {{ trans('update.installments') }})
                </span>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-12">
                <span class="text-gray-500">{{ trans('update.bnpl_fee') }}</span>
                <span class="js-cart-bnpl-fee text-primary">
                    @php
                        $totalBnplFee = 0;
                        foreach($bnplCarts as $cart) {
                            if ($cart->bnpl_fee) {
                                $totalBnplFee += $cart->bnpl_fee * ($cart->productOrder->quantity ?? 1);
                            }
                        }
                    @endphp
                    +{{ handlePrice($totalBnplFee) }}
                </span>
            </div>
        @endif

        <div class="cart-summary-divider"></div>

        <div class="d-flex align-items-center justify-content-between mt-16">
            <span class="text-gray-500">{{ trans('cart.total') }}</span>
            <span class="js-cart-total font-16 font-weight-bold">
                @php
                    $totalWithBnpl = $calculatePrices["total"];
                    if (!empty($carts) && $carts->where('bnpl_provider')->count() > 0) {
                        $totalBnplFee = 0;
                        foreach($carts->where('bnpl_provider') as $cart) {
                            if ($cart->bnpl_fee) {
                                $totalBnplFee += $cart->bnpl_fee * ($cart->productOrder->quantity ?? 1);
                            }
                        }
                        $totalWithBnpl += $totalBnplFee;
                    }
                @endphp
                {{ handlePrice($totalWithBnpl) }}
            </span>
        </div>

        @if(!empty($carts) && $carts->where('bnpl_provider')->count() > 0)
            <div class="d-flex align-items-center justify-content-between mt-8">
                <span class="text-gray-500 font-12">{{ trans('update.total_with_bnpl') }}</span>
                <span class="js-cart-total-with-bnpl font-12 font-weight-bold text-primary">{{ handlePrice($totalWithBnpl) }}</span>
            </div>
        @endif

        <button type="button" class="{{ !empty($isCartPaymentPage) ? 'js-cart-payment-btn' : 'js-cart-checkout' }} btn btn-lg btn-block btn-primary mt-20">
            @if(!empty($isCartPaymentPage))
                {{ trans('update.pay_with_gateway') }}
            @else
                {{ trans('cart.checkout') }}
            @endif
        </button>

        @if(!empty(getOthersPersonalizationSettings("show_secure_payment_text")))
            <div class="d-flex-center mt-20">
                <x-iconsax-lin-shield-tick class="icons text-gray-500" width="24px" height="24px"/>
                <span class="ml-4 font-12 font-weight-bold text-gray-500">{{ trans('update.secure_payments_provided') }}</span>
            </div>
        @endif

        @if(!empty(getOthersPersonalizationSettings("secure_payment_image")))
            <div class="d-flex-center mt-16">
                <img src="{{ getOthersPersonalizationSettings("secure_payment_image") }}" alt="{{ trans('update.secure_payments_provided') }}" class="img-fluid" height="24px">
            </div>
        @endif

    </div>
</div>
