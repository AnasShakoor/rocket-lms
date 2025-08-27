(function () {
    "use strict";

    $('document').ready(function () {
        if (typeof hasErrors !== "undefined" && hasErrors === 'true') {
            showToast('error', oopsLang, hasErrorsHintLang);
        }
    })

    $('body').on('click', '.js-cart-checkout', function (e) {
        e.preventDefault();

        const $this = $(this);
        const $form = $this.closest('form');

        $this.addClass('loadingbar').prop('disabled', true);

        $form.trigger('submit')
    });

    $('body').on('click', '.js-cart-payment-btn', function (e) {
        e.preventDefault();

        const $this = $(this);
        const $form = $this.closest('form');
        const $selectedChannel = $form.find('input[name="gateway"]:checked');

        if ($selectedChannel.length) {
            $this.addClass('loadingbar').prop('disabled', true);

            showToast("success", pleaseWaitLang, transferringToLang)

            const channelName = $selectedChannel.attr('data-class');

                    if (channelName === 'Razorpay') {
            $('.razorpay-payment-button').trigger('click');
        } else if (channelName === 'Moyasar') {
            // Handle Moyasar payment form display
            e.preventDefault();
            $this.removeClass('loadingbar').prop('disabled', false);

            // Get order ID from form
            const orderId = $form.find('input[name="order_id"]').val();

            // Fetch Moyasar payment form data
            $.ajax({
                url: '/payments/moyasar-form-data',
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: JSON.stringify({
                    order_id: orderId,
                    gateway: $selectedChannel.val()
                }),
                success: function(data) {
                    if (data.success) {
                        // Store form data globally and show form
                        if (typeof moyasarFormData !== 'undefined') {
                            moyasarFormData = data.payment_form_data;
                            if (typeof showMoyasarForm === 'function') {
                                showMoyasarForm();
                            }
                        }
                    } else {
                        showToast('error', 'Error', data.message || 'Failed to load payment form');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching Moyasar form data:', error);
                    showToast('error', 'Error', 'Failed to load payment form. Please try again.');
                }
            });
        } else {
            $form.trigger('submit');
        }
        } else {
            showToast('error', '', selectPaymentGatewayLang)
        }
    });


    $('body').on('click', '.js-validate-coupon-btn', function (e) {
        e.preventDefault();

        const $this = $(this);
        const $parent = $this.parent();
        const coupon = $parent.find('input[name="coupon"]').val();
        const path = "/cart/coupon/validate";

        if (coupon) {
            const $cartSummaryCard = $('.js-cart-summary-container');
            $this.addClass('loadingbar').prop('disabled', true);

            const data = {
                coupon: coupon
            }

            $.post(path, data, function (result) {
                $this.removeClass('loadingbar').prop('disabled', false);

                if (result.code === 200) {
                    $cartSummaryCard.html(result.html);

                    $this.addClass('d-none');
                    $parent.find('.js-remove-coupon-btn').removeClass('d-none')
                }

            }).fail(err => {
                $this.removeClass('loadingbar').prop('disabled', false);
                const errors = err.responseJSON;

                if (errors.error) {
                    showToast('error', errors.error.title, errors.error.msg)
                }
            })
        } else {
            showToast('error', couponLang, enterCouponLang)
        }
    })


    $('body').on('click', '.js-remove-coupon-btn', function (e) {
        e.preventDefault();

        var html = '<div class="px-16 pb-24 pt-16">\n' +
            '    <p class="text-center">' + removeCouponHintLang + '</p>\n' +
            '    <div class="mt-24 d-flex align-items-center justify-content-center">\n' +
            '        <a href="/cart" class="btn btn-sm btn-primary">' + removeLang + '</a>\n' +
            '        <button type="button" class="btn btn-sm btn-danger ml-12 close-swl">' + cancelLang + '</button>\n' +
            '    </div>\n' +
            '</div>';

        Swal.fire({
            title: removeCouponTitleLang,
            html: html,
            icon: 'warning',
            showConfirmButton: false,
            showCancelButton: false,
            allowOutsideClick: () => !Swal.isLoading(),
        })
    });

})(jQuery);
