<?php

namespace App\PaymentChannels;

use Illuminate\Support\Facades\Log;

class BasePaymentChannel
{

    public $show_test_mode_toggle = true;
    protected array $credentialItems;
    protected $test_mode = false;

    public function makeAmountByCurrency($amount, $currency)
    {
        $userCurrencyItem = getUserCurrencyItem(null, $currency);

        return convertPriceToUserCurrency($amount, $userCurrencyItem);
    }

    public function getCredentialItems(): array
    {
        return $this->credentialItems ?? [];
    }

    public function getShowTestModeToggle(): bool
    {
        return $this->show_test_mode_toggle;
    }

    public function setCredentialItems($paymentChannel): void
    {
        $credentialItems = $this->credentialItems ?? [];

        Log::info('BasePaymentChannel setting credentials', [
            'credential_items' => $credentialItems,
            'has_payment_channel' => !empty($paymentChannel),
            'has_credentials' => !empty($paymentChannel->credentials),
            'payment_channel_id' => $paymentChannel->id ?? null,
            'class_name' => $paymentChannel->class_name ?? null
        ]);

        if (!empty($credentialItems) and !empty($paymentChannel->credentials)) {

            foreach ($credentialItems as $credentialKey => $credentialItem) {
                if (is_array($credentialItem)) {
                    if (!empty($paymentChannel->credentials[$credentialKey])) {
                        $this->{$credentialKey} = $paymentChannel->credentials[$credentialKey];
                        Log::info('Array credential set', [
                            'key' => $credentialKey,
                            'value' => $paymentChannel->credentials[$credentialKey]
                        ]);
                    }
                } else {
                    if (!empty($paymentChannel->credentials[$credentialItem])) {
                        $this->{$credentialItem} = $paymentChannel->credentials[$credentialItem];
                        Log::info('String credential set', [
                            'key' => $credentialItem,
                            'value' => $paymentChannel->credentials[$credentialItem]
                        ]);
                    }
                }
            }

            $this->test_mode = false;

            if (!empty($paymentChannel->credentials['test_mode'])) {
                $this->test_mode = true;
                Log::info('Test mode enabled from credentials');
            } else {
                Log::info('Test mode disabled (default)');
            }
        } else {
            Log::info('No credentials to set or no payment channel credentials', [
                'has_credential_items' => !empty($credentialItems),
                'has_payment_channel_credentials' => !empty($paymentChannel->credentials)
            ]);
        }

        Log::info('BasePaymentChannel credentials setup complete', [
            'test_mode' => $this->test_mode,
            'credential_properties_set' => array_filter($credentialItems, function($item) {
                return is_string($item) && isset($this->{$item});
            })
        ]);
    }


}
