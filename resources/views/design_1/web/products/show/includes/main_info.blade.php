@php
    $productAvailability = $product->getAvailability();
    $hasInventory = ($productAvailability > 0);
@endphp

<div class="card-with-mask">
    <div class="mask-8-white"></div>

    <div class="position-relative bg-white p-16 rounded-24 z-index-2">
        {{-- Special Offer  --}}
        @include('design_1.web.products.show.includes.main_info.special_offer')

        {{-- Breadcrumb --}}
        <div class="breadcrumb d-flex align-items-center">
            <a href="/" class="breadcrumb-item font-14 text-gray-500">{{ getPlatformName() }}</a>
            <x-iconsax-lin-arrow-right-1 class="icons text-gray-500 mx-8" width="14px" height="14px"/>
            <a href="/products" class="breadcrumb-item font-14 text-gray-500">{{ trans('update.store') }}</a>

            @if(!empty($product->category))
                <x-iconsax-lin-arrow-right-1 class="icons text-gray-500 mx-8" width="14px" height="14px"/>
                <a href="{{ $product->category->getUrl() }}" class="breadcrumb-item font-14 text-gray-500">{{ $product->category->title }}</a>
            @endif
        </div>

        <div class="d-flex align-items-center flex-wrap gap-12 mt-12">
            <h1 class="course-hero__title font-24 font-weight-bold text-dark text-ellipsis">{{ $product->title }}</h1>

            @php
                $productAllBadges = $product->allBadges();
            @endphp

            {{-- Badges --}}
            @if(count($productAllBadges))
                <div class="d-flex align-items-center flex-wrap gap-12">
                    @foreach($productAllBadges as $productBadge)
                        <div class="d-flex-center gap-4 p-4 pr-8 rounded-32" style="background-color: {{ $productBadge->background }}; color: {{ $productBadge->color }};">
                            @if(!empty($productBadge->icon))
                                <div class="size-24">
                                    <img src="{{ $productBadge->icon }}" alt="{{ $productBadge->title }}" class="img-cover">
                                </div>
                            @endif
                            <span class="font-12">{{ $productBadge->title }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-lg-between mt-12">
            <div class="d-flex align-items-center flex-wrap gap-24">
                {{-- Rate --}}
                @include('design_1.web.components.rate', [
                     'rate' => $product->getRate(),
                     'rateCount' => $product->getRateCount(),
                     'rateClassName' => '',
                     'rateCountFont' => 'font-12',
                 ])

                <a href="{{ $product->creator->getProfileUrl() }}" target="_blank" class="d-flex align-items-center text-gray-500">
                    <x-iconsax-lin-profile class="icons text-gray-500" width="16px" height="16px"/>
                    <span class="ml-4 font-12 font-weight-bold">{{ truncate($product->creator->full_name, 15) }}</span>
                </a>

                <div class="d-flex align-items-center text-gray-500">
                    <x-iconsax-lin-money-2 class="icons text-gray-500" width="16px" height="16px"/>
                    <span class="mx-4 font-12 font-weight-bold">{{ $product->salesCount() }}</span>
                    <span class="font-12 ">{{ trans('panel.sales') }}</span>
                </div>

                @if($product->isPhysical() and !empty($product->delivery_estimated_time))
                    <div class="d-flex align-items-center text-gray-500">
                        <x-iconsax-lin-truck-time class="icons text-gray-500" width="16px" height="16px"/>
                        <span class="mx-4 font-12 font-weight-bold">{{ $product->delivery_estimated_time }}</span>
                        <span class="font-12 ">{{ trans('update.shipping_days') }}</span>
                    </div>
                @endif
            </div>

            @if(!$hasInventory)
                <div class="d-inline-flex-center px-8 py-4 rounded-32 bg-danger font-12 text-white mt-16 mt-lg-0">{{ trans('update.out_of_stock') }}</div>
            @endif
        </div>

    </div>
</div>

{{-- Summary --}}
@if(!empty($product->summary))
    <div class="mt-28">
        @php
            $walletHints = explode("\n", $product->summary);
        @endphp

        <ul class="text-gray-500 list-style-disc">
            @foreach ($walletHints as $hint)
                @if (!empty(trim($hint)))
                    <li class="{{ (!$loop->first) ? 'mt-12' : '' }}">{{ $hint }}</li>
                @endif
            @endforeach
        </ul>
    </div>
@endif


{{-- Specifications --}}
@if(!empty($selectableSpecifications) and count($selectableSpecifications))
    <div class="js-product-specifications">
        @foreach($selectableSpecifications as $selectableSpecification)
            <div class="mt-16">
                <h6 class="font-14 font-weight-bold text-dark">{{ $selectableSpecification->specification->title }}</h6>

                <div class="position-relative d-flex align-items-center gap-8 flex-wrap mt-12">
                    @foreach($selectableSpecification->selectedMultiValues as $specificationValue)
                        @if(!empty($specificationValue->multiValue))
                            <div class="product-show__selectable-specification-item">
                                <input type="radio" name="specifications[{{ $selectableSpecification->specification->createName() }}]" value="{{ $specificationValue->multiValue->createName() }}" id="{{ $specificationValue->multiValue->createName() }}" class="js-selectable-specification-item" {{ ($loop->iteration == 1) ? 'checked' : '' }} >
                                <label class="d-inline-flex-center px-12 py-8 rounded-8 font-12 cursor-pointer" for="{{ $specificationValue->multiValue->createName() }}">{{ $specificationValue->multiValue->title }}</label>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif

@if($hasInventory)
    {{-- Price --}}
    <div class="d-flex align-items-center font-24 font-weight-bold text-primary mt-24">
        @if(!empty($product->price) and $product->price > 0)
            @if($product->getPriceWithActiveDiscountPrice() < $product->price)
                <span class="">{{ handlePrice($product->getPriceWithActiveDiscountPrice(), true, true, false, null, true, 'store') }}</span>
                <span class="font-14 font-weight-400 text-gray-500 ml-8 text-decoration-line-through">{{ handlePrice($product->price, true, true, false, null, true, 'store') }}</span>
            @else
                <span class="">{{ handlePrice($product->price, true, true, false, null, true, 'store') }}</span>
            @endif
        @else
            <span class="">{{ trans('public.free') }}</span>
        @endif
    </div>

    @if($product->isPhysical())
        <div class="d-flex align-items-center mt-8 text-gray-500">
            <x-iconsax-lin-group-7 class="icons text-gray-500" width="16px" height="16px"/>

            @if(!empty($product->delivery_fee) and $product->delivery_fee > 0)
                <span class="ml-4">+ {{ trans('update.n_shipping_fee',['amount' => handlePrice($product->delivery_fee)]) }}</span>
            @else
                <span class="ml-4">{{ trans('update.free_shipping') }}</span>
            @endif
        </div>
    @endif

    {{-- BNPL Options --}}
    @if(!empty($bnplProviders) && count($bnplProviders) > 0 && !empty($product->price) && $product->price > 0)
        <div class="mt-20">
            <h4 class="font-14 font-weight-bold text-dark">{{ trans('update.buy_now_pay_later') }}</h4>
            <p class="font-12 text-gray-500 mt-4">{{ trans('update.split_your_payment') }}</p>

            <div class="d-grid grid-columns-2 gap-12 mt-12">
                @foreach($bnplProviders as $provider)
                    <div class="bnpl-option-card p-12 rounded-12 border border-gray-200 cursor-pointer js-bnpl-option"
                         data-provider="{{ $provider->name }}"
                         data-installments="{{ $provider->installment_count }}"
                         data-fee="{{ $provider->fee_percentage }}"
                         data-product-price="{{ $product->getPriceWithActiveDiscountPrice() }}">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="font-12 font-weight-bold text-dark">{{ $provider->name }}</h6>
                                <p class="font-10 text-gray-500 mt-2">{{ $provider->installment_count }} {{ trans('update.installments') }}</p>
                            </div>
                            <div class="text-right">
                                                            <div class="font-12 font-weight-bold text-primary js-bnpl-installment-amount" data-provider="{{ $provider->name }}">
                                {{ currency() }} {{ number_format(((float) $product->getPriceWithActiveDiscountPrice() * (1 + ((float) (getFinancialSettings('tax') ?? 15)) / 100) * (1 + (float) $provider->fee_percentage / 100)) / (float) $provider->installment_count, 2) }}
                            </div>
                                <div class="font-10 text-gray-500 mt-1">{{ trans('update.per_month') }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- BNPL Breakdown Modal Trigger --}}
            <button type="button" class="js-bnpl-breakdown-btn btn btn-outline-primary btn-sm mt-12">
                <x-iconsax-lin-info-circle class="icons" width="16px" height="16px"/>
                <span class="ml-4">{{ trans('update.see_breakdown') }}</span>
            </button>
        </div>
    @endif



    {{-- Quantity --}}
    <div class="mt-20">
        <h4 class="font-14 font-weight-bold">{{ trans('update.quantity') }}</h4>

        <div class="js-product-quantity-card product-show__quantity-card d-inline-flex align-items-center gap-4 mt-12 p-8 rounded-12 bg-white">
            <input type="hidden" id="productAvailabilityCount" value="{{ $productAvailability }}">

            <button type="button" class="minus d-flex-center bg-gray-100 rounded-8" {{ !$hasInventory ? 'disabled' : '' }}>
                <x-iconsax-lin-minus class="icons text-gray-500" width="14px" height="14px"/>
            </button>

            <input type="number" name="quantity" value="1" {{ !$hasInventory ? 'disabled' : '' }} class="bg-white font-14 font-weight-bold" data-item="{{ $product->id }}">

            <button type="button" class="plus d-flex-center bg-gray-100 rounded-8" {{ !$hasInventory ? 'disabled' : '' }}>
                <x-iconsax-lin-add class="icons text-gray-500" width="14px" height="14px"/>
            </button>
        </div>
    </div>

    @if(!empty($product->inventory) and !empty($product->inventory_warning) and $product->inventory_warning > $productAvailability)
        <div class="d-flex align-items-center mt-12 text-warning">
            <x-iconsax-lin-box class="icons text-warning" width="16px" height="16px"/>
            <span class="ml-4">{{ trans('update.only_n_items_are_available', ['count' => $productAvailability]) }}</span>
        </div>
    @endif

    {{-- Actions --}}
    <div class="d-flex align-items-center gap-12 flex-wrap mt-16">

        <button type="button" class="js-add-to-cart-btn btn btn-primary btn-lg">
            <x-iconsax-lin-bag-happy class="icons text-white" width="24px" height="24px"/>
            <span class="ml-4 text-white">{{ trans('public.add_to_cart') }}</span>
        </button>

        @if(!empty(getFeaturesSettings('direct_products_payment_button_status')))
            <button type="button" class="js-add-to-cart-btn btn btn-outline-accent btn-lg">
                <x-iconsax-lin-moneys class="icons " width="24px" height="24px"/>
                <span class="ml-4 ">{{ trans('update.buy_now') }}</span>
            </button>
        @endif

        @if(!empty($product->point) and $product->point > 0)
            <input type="hidden" class="js-product-points" value="{{ $product->point }}">

            <a href="{{ !(auth()->check()) ? '/login' : '#!' }}" class="{{ (auth()->check()) ? 'js-buy-with-point' : '' }} js-buy-with-point-show-btn btn btn-outline-warning btn-lg" rel="nofollow">
                {!! trans('update.buy_with_n_points',['points' => $product->point]) !!}
            </a>
        @endif

        @if($productAvailability > 0 and $hasInstallments)
            <a href="/products/{{ $product->slug }}/installments" class="js-installments-btn btn btn-outline-primary btn-lg">
                {{ trans('update.installments') }}
            </a>
        @endif
    </div>

@else
    <div class="font-24 font-weight-bold text-gray-500 mt-24">{{ trans('update.out_of_stock') }}</div>
@endif

@push('scripts_bottom')
<script>
$(document).ready(function() {
    // BNPL option selection
    $('.js-bnpl-option').on('click', function() {
        $('.js-bnpl-option').removeClass('border-primary bg-primary-10');
        $(this).addClass('border-primary bg-primary-10');

        // Store selected BNPL provider for cart
        var provider = $(this).data('provider');
        var installments = $(this).data('installments');
        var fee = $(this).data('fee');

        // Add to cart with BNPL preference
        addToCartWithBnpl(provider, installments, fee);
    });

    // BNPL breakdown modal
    $('.js-bnpl-breakdown-btn').on('click', function() {
        showBnplBreakdown();
    });

    function addToCartWithBnpl(provider, installments, fee) {
        var productId = {{ $product->id }};
        var quantity = $('input[name="quantity"]').val();

        // Add BNPL data to cart item
        var cartData = {
            product_id: productId,
            quantity: quantity,
            bnpl_provider: provider,
            bnpl_installments: installments,
            bnpl_fee: fee
        };

        // Store in localStorage for cart processing
        localStorage.setItem('bnpl_preference', JSON.stringify(cartData));

        // Show success message
        showBnplSelectedMessage(provider, installments);
    }

    function showBnplSelectedMessage(provider, installments) {
        // Create or update success message
        var messageHtml = `
            <div class="alert alert-success mt-12 p-8 rounded-8">
                <div class="d-flex align-items-center">
                    <x-iconsax-lin-tick-circle class="icons text-success" width="16px" height="16px"/>
                    <span class="ml-4 font-12">{{ trans('update.bnpl_selected') }}: ${provider} (${installments} {{ trans('update.installments') }})</span>
                </div>
            </div>
        `;

        // Remove existing message if any
        $('.bnpl-selected-message').remove();

        // Add new message
        $('.js-bnpl-option').parent().after(messageHtml);
    }

    function showBnplBreakdown() {
        var productPrice = {{ $product->getPriceWithActiveDiscountPrice() }};
        var vatPercentage = {{ getFinancialSettings('tax') ?? 15 }};
        var vatAmount = productPrice * (vatPercentage / 100);
        var priceWithVat = productPrice + vatAmount;

        var breakdownHtml = `
            <div class="bnpl-breakdown-details">
                <h6 class="font-14 font-weight-bold mb-12">{{ trans('update.payment_breakdown') }}</h6>

                <div class="d-flex justify-content-between align-items-center mb-8">
                    <span class="font-14 text-gray-600">{{ trans('update.product_price') }}</span>
                    <span class="font-14 font-weight-bold">{{ currency() }} ${productPrice.toFixed(2)}</span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-8">
                    <span class="font-14 text-gray-600">{{ trans('update.vat') }} (${vatPercentage}%)</span>
                    <span class="font-14 font-weight-bold">{{ currency() }} ${vatAmount.toFixed(2)}</span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-8">
                    <span class="font-14 text-gray-600">{{ trans('update.price_with_vat') }}</span>
                    <span class="font-14 font-weight-bold">{{ currency() }} ${priceWithVat.toFixed(2)}</span>
                </div>

                <hr class="my-12">

                <h6 class="font-14 font-weight-bold mb-12">{{ trans('update.bnpl_options') }}</h6>
        `;

        // Add each provider breakdown
        @foreach($bnplProviders as $provider)
            var providerFee = {{ $provider->fee_percentage }};
            var installments = {{ $provider->installment_count }};
            var bnplFee = priceWithVat * (providerFee / 100);
            var totalWithBnpl = priceWithVat + bnplFee;
            var installmentAmount = totalWithBnpl / installments;

            breakdownHtml += `
                <div class="mb-16 p-12 rounded-8 bg-gray-50">
                    <div class="d-flex justify-content-between align-items-center mb-8">
                        <span class="font-14 font-weight-bold text-dark">{{ $provider->name }}</span>
                        <span class="font-12 text-gray-500">${installments} {{ trans('update.installments') }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="font-12 text-gray-600">{{ trans('update.bnpl_fee') }} (${providerFee}%)</span>
                        <span class="font-12 font-weight-bold">{{ currency() }} ${bnplFee.toFixed(2)}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="font-12 text-gray-600">{{ trans('update.total_with_bnpl') }}</span>
                        <span class="font-12 font-weight-bold">{{ currency() }} ${totalWithBnpl.toFixed(2)}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-12 text-gray-600">{{ trans('update.installment_amount') }}</span>
                        <span class="font-12 font-weight-bold text-primary">{{ currency() }} ${installmentAmount.toFixed(2)}</span>
                    </div>
                </div>
            `;
        @endforeach

        breakdownHtml += '</div>';

        $('#bnpl-breakdown-content').html(breakdownHtml);
        $('#bnplBreakdownModal').modal('show');
    }
});
</script>
@endpush

{{-- BNPL Breakdown Modal --}}
@if(!empty($bnplProviders) && count($bnplProviders) > 0)
    <div class="modal fade" id="bnplBreakdownModal" tabindex="-1" aria-labelledby="bnplBreakdownModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bnplBreakdownModalLabel">{{ trans('update.bnpl_payment_breakdown') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="bnpl-breakdown-content">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('public.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endif
