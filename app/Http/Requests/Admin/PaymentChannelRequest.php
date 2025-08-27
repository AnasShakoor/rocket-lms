<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class PaymentChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'image' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'currencies' => 'nullable|array',
            'currencies.*' => 'string|max:10',
        ];

        // Add credential validation rules based on the payment channel class
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $paymentChannelId = $this->route('id');
            $paymentChannel = \App\Models\PaymentChannel::find($paymentChannelId);

            if ($paymentChannel) {
                $credentialRules = $this->getCredentialRules($paymentChannel->class_name);
                $rules = array_merge($rules, $credentialRules);

                Log::info('PaymentChannelRequest validation rules generated', [
                    'payment_channel_id' => $paymentChannelId,
                    'class_name' => $paymentChannel->class_name,
                    'base_rules' => array_keys($rules),
                    'credential_rules' => array_keys($credentialRules),
                    'all_rules' => $rules
                ]);
            }
        }

        return $rules;
    }

    protected function prepareForValidation()
    {
        Log::info('PaymentChannelRequest preparing for validation', [
            'method' => $this->method(),
            'route_id' => $this->route('id'),
            'all_data' => $this->all(),
            'credentials' => $this->input('credentials'),
            'currencies' => $this->input('currencies')
        ]);
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The payment channel title is required.',
            'title.string' => 'The payment channel title must be a string.',
            'title.max' => 'The payment channel title cannot exceed 255 characters.',
            'status.required' => 'The payment channel status is required.',
            'status.in' => 'The payment channel status must be either active or inactive.',
            'currencies.array' => 'The currencies must be an array.',
            'currencies.*.string' => 'Each currency must be a string.',
            'currencies.*.max' => 'Each currency cannot exceed 10 characters.',
        ];
    }

    private function getCredentialRules(string $className): array
    {
        $rules = [];

        switch ($className) {
            case 'Moyasar':
                $rules['credentials.secret_key'] = 'required|string|starts_with:sk_';
                $rules['credentials.publishable_key'] = 'required|string|starts_with:pk_';
                break;

            case 'Paypal':
                $rules['credentials.client_id'] = 'required|string';
                $rules['credentials.secret'] = 'required|string';
                break;

            case 'Payu':
                $rules['credentials.money_key'] = 'required|string';
                $rules['credentials.money_salt'] = 'required|string';
                $rules['credentials.money_auth'] = 'required|string';
                break;

            case 'Razorpay':
                $rules['credentials.key_id'] = 'required|string';
                $rules['credentials.key_secret'] = 'required|string';
                break;

            default:
                // Generic credential validation for unknown payment channels
                $rules['credentials.*'] = 'nullable|string';
                break;
        }

        Log::info('Credential rules generated for payment channel', [
            'class_name' => $className,
            'rules' => $rules
        ]);

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'title' => 'payment channel title',
            'status' => 'payment channel status',
            'currencies' => 'supported currencies',
            'credentials.secret_key' => 'secret key',
            'credentials.publishable_key' => 'publishable key',
            'credentials.client_id' => 'client ID',
            'credentials.secret' => 'secret',
            'credentials.money_key' => 'money key',
            'credentials.money_salt' => 'money salt',
            'credentials.money_auth' => 'money auth',
            'credentials.key_id' => 'key ID',
            'credentials.key_secret' => 'key secret',
        ];
    }
}
