# MisPay BNPL Integration

This document describes the implementation of MisPay Buy Now, Pay Later (BNPL) integration alongside the existing Tabby integration in the Rocket LMS system.

## Overview

MisPay is a BNPL provider that allows customers to pay for purchases in installments over 3, 6, or 12 months with no additional fees. The integration follows the same pattern as Tabby for consistency.

## Features

- **Background Eligibility Check**: Pre-scoring to determine customer eligibility
- **Dynamic UI**: Shows/hides MisPay option based on eligibility
- **Installment Options**: Displays available installment plans (3, 6, 12 months)
- **Secure Payment Flow**: Redirects to MisPay hosted payment page
- **Payment Verification**: Handles success, cancel, and failure scenarios
- **Database Configuration**: Admin-configurable settings stored in database

## Architecture

### 1. Service Layer (`MisPayService`)

Located at `app/Services/MisPayService.php`, this service handles:

- Configuration management (database + fallback to config files)
- API communication with MisPay
- Eligibility checking
- Checkout session creation
- Payment verification
- Error handling and logging

### 2. Payment Channel Driver (`Channel`)

Located at `app/PaymentChannels/Drivers/MisPay/Channel.php`, implements the `IChannel` interface:

- Payment request handling
- Eligibility validation
- Checkout session creation
- Payment verification
- Configuration management

### 3. API Controller (`MisPayController`)

Located at `app/Http/Controllers/Api/MisPayController.php`, provides:

- REST API endpoint for eligibility checks
- Input validation
- Error handling
- JSON responses

### 4. Web Controller Integration

Updated `PaymentController` to handle MisPay BNPL payments alongside Tabby.

## Configuration

### Database Configuration

MisPay configuration is stored in the `bnpl_providers` table:

```sql
INSERT INTO bnpl_providers (name, title, description, secret_api_key, merchant_code, config, is_active, created_at, updated_at) 
VALUES (
    'MisPay',
    'MisPay Installments',
    'Pay in installments with MisPay - 3, 6, or 12 months with no fees',
    'your_app_id_here',
    'your_app_secret_here',
    '{"api_endpoint": "https://api.mispay.co/sandbox/v1/api", "test_mode": true}',
    1,
    NOW(),
    NOW()
);
```

### Configuration Fields

- **`secret_api_key`**: Your MisPay App ID
- **`merchant_code`**: Your MisPay App Secret
- **`config`**: JSON configuration object containing:
  - `api_endpoint`: MisPay API endpoint (default: sandbox)
  - `test_mode`: Enable/disable test mode

### Environment Variables (Fallback)

If no database record is found, the system falls back to environment variables:

```env
MISPAY_APP_ID=your_app_id
MISPAY_APP_SECRET=your_app_secret
MISPAY_BASE_URL=https://api.mispay.co/sandbox/v1/api
MISPAY_TEST_MODE=true
```

## API Endpoints

### 1. Eligibility Check

**Endpoint**: `POST /api/mispay/check-eligibility`

**Request Body**:
```json
{
    "order_id": 123,
    "amount": 500.00,
    "currency": "SAR"
}
```

**Response**:
```json
{
    "success": true,
    "eligible": true,
    "message": "Customer is eligible for MisPay installments",
    "installment_options": [
        {
            "months": 3,
            "monthly_payment": 166.67,
            "total_amount": 500.00,
            "fees": 0
        },
        {
            "months": 6,
            "monthly_payment": 83.33,
            "total_amount": 500.00,
            "fees": 0
        }
    ]
}
```

### 2. Payment Verification

**Endpoint**: `GET /payments/verify/MisPay?checkout_id={id}`

**Response**: Redirects to appropriate success/failure page based on payment status.

## Frontend Integration

### Modal Structure

MisPay uses the same modal structure as Tabby for consistency:

```html
<div id="mispay-modal" class="tabby-modal" style="display: none;">
    <div class="tabby-modal-overlay"></div>
    <div class="tabby-modal-content">
        <div class="tabby-modal-header">
            <h4 class="tabby-modal-title">Pay Later with MisPay</h4>
            <button type="button" class="tabby-modal-close" id="mispay-modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="tabby-modal-body">
            <div id="mispay-form-container" class="tabby-form-container">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>
</div>
```

### JavaScript Functions

- `showMisPayModal()`: Display the modal and initiate eligibility check
- `hideMisPayModal()`: Close the modal
- `checkMisPayEligibility()`: Make AJAX call to check eligibility
- `showMisPayEligibleState()`: Show eligible state with installment options
- `showMisPayIneligibleState()`: Show rejection reason
- `showMisPayErrorState()`: Show error state
- `proceedWithMisPay()`: Submit payment form

## Payment Flow

### 1. Customer Selection

1. Customer selects BNPL payment method
2. Customer selects MisPay as BNPL provider
3. Modal appears with eligibility check

### 2. Eligibility Check

1. Frontend makes AJAX call to `/api/mispay/check-eligibility`
2. Backend validates order and customer data
3. Returns eligibility status and installment options

### 3. Checkout Process

1. If eligible, customer sees installment options
2. Customer clicks "Proceed with MisPay"
3. Form submits to `/payments/payment-request`
4. Backend creates MisPay checkout session
5. Customer redirected to MisPay hosted payment page

### 4. Payment Completion

1. Customer completes payment on MisPay
2. MisPay redirects back to success/cancel/failure URLs
3. System verifies payment status
4. Order status updated accordingly

## Eligibility Criteria

### Basic Requirements

- **Order Amount**: Between SAR 50 and SAR 50,000
- **Customer Contact**: Valid email and phone number required
- **Order Status**: Must be a valid, unpaid order

### Installment Options

- **3 Months**: Available for orders ≥ SAR 100
- **6 Months**: Available for orders ≥ SAR 200  
- **12 Months**: Available for orders ≥ SAR 500

## Error Handling

### Common Error Scenarios

1. **Configuration Missing**: App ID or Secret not configured
2. **API Communication**: Network issues or MisPay API errors
3. **Invalid Order**: Order not found or already processed
4. **Eligibility Rejection**: Customer doesn't meet criteria

### Error Messages

All error messages are localized and stored in language files:

```php
// English
'mispay_payment_failed' => 'MisPay payment failed',
'mispay_payment_cancelled' => 'MisPay payment was cancelled',

// Arabic  
'mispay_payment_failed' => 'فشل الدفع عبر MisPay',
'mispay_payment_cancelled' => 'تم إلغاء الدفع عبر MisPay',
```

## Security Features

### Data Encryption

- Uses MisPay's encryption/decryption methods for sensitive data
- API keys stored securely in database
- HTTPS communication for all API calls

### Validation

- Input validation on all API endpoints
- Order ownership verification
- Payment status verification before order updates

## Testing

### Sandbox Environment

- Default configuration uses MisPay sandbox API
- Test orders with amounts between SAR 50-500
- Verify all payment scenarios (success, cancel, failure)

### Test Data

```php
// Test order data
$order = [
    'id' => 123,
    'total_amount' => 250.00,
    'currency' => 'SAR',
    'user' => [
        'email' => 'test@example.com',
        'mobile' => '+966501234567',
        'full_name' => 'Test User'
    ]
];
```

## Monitoring and Logging

### Log Entries

All MisPay operations are logged with detailed information:

```php
Log::info('MisPay checkout session created', [
    'order_id' => $order->id,
    'checkout_id' => $checkoutId,
    'amount' => $order->total_amount
]);
```

### Error Tracking

- API failures logged with response details
- Payment verification errors tracked
- Customer eligibility rejections monitored

## Troubleshooting

### Common Issues

1. **"MisPay is not properly configured"**
   - Check database configuration
   - Verify App ID and Secret are correct
   - Ensure MisPay provider is active

2. **"Failed to obtain MisPay access token"**
   - Check API credentials
   - Verify API endpoint is accessible
   - Check network connectivity

3. **"Customer is not eligible"**
   - Verify order amount is within limits
   - Check customer has valid email/phone
   - Review eligibility criteria

### Debug Mode

Enable debug logging by setting log level to DEBUG in your Laravel configuration.

## Future Enhancements

### Planned Features

1. **Advanced Eligibility**: Integration with credit scoring systems
2. **Dynamic Limits**: Customer-specific spending limits
3. **Analytics Dashboard**: Payment success rates and customer behavior
4. **Webhook Support**: Real-time payment status updates

### Integration Points

- Customer credit history
- Order analytics
- Payment gateway monitoring
- Customer support system

## Support

For technical support or questions about the MisPay integration:

1. Check the logs for detailed error information
2. Verify configuration settings
3. Test with sandbox environment
4. Contact development team with specific error details

## Changelog

### Version 1.0.0
- Initial MisPay integration
- Basic eligibility checking
- Payment flow implementation
- Frontend modal integration
- Database configuration support
- Error handling and logging
- Multi-language support
