# Tabby Payment Integration

This document describes the Tabby payment integration implemented in the Rocket LMS system.

## Overview

Tabby is a Buy Now, Pay Later (BNPL) service that allows customers to split their purchases into installments. This integration follows the official Tabby integration guide and implements the complete payment flow.

## Features

- **Background Pre-scoring**: Automatic eligibility check before showing Tabby option
- **Checkout Session Creation**: Creates Tabby checkout sessions for eligible customers
- **Payment Verification**: Handles payment status verification and order updates
- **Multi-language Support**: English and Arabic language support
- **Responsive UI**: Modern popup modal for Tabby payment flow

## Configuration

### Database Configuration (Recommended)

Tabby configuration is stored in the database through the admin panel. Follow these steps:

1. **Go to Admin Panel** → **BNPL Providers**
2. **Find or Create Tabby Provider**:
   - Name: `Tabby`
   - Fee Percentage: `0.00` (or your preferred fee)
   - Installment Count: `4` (or your preferred count)
   - Status: `Active`

3. **Configure Tabby API Credentials**:
   - **Secret API Key**: Your Tabby secret key from the Tabby dashboard
   - **Merchant Code**: Your Tabby merchant code
   - **Configuration (JSON)**: 
     ```json
     {
       "api_endpoint": "https://api.tabby.ai",
       "test_mode": true
     }
     ```

### Environment File Configuration (Fallback)

If you prefer to use environment variables, add these to your `.env` file:

```env
# Tabby Payment Gateway
TABBY_SECRET_KEY=your_tabby_secret_key_here
TABBY_MERCHANT_CODE=your_tabby_merchant_code_here
TABBY_API_ENDPOINT=https://api.tabby.ai
TABBY_TEST_MODE=true
```

**Note**: Database configuration takes precedence over environment variables.

### Check Configuration Status

You can check if Tabby is properly configured by calling:

```
GET /admin/bnpl-providers/tabby/status
```

This will return a JSON response with configuration status:

```json
{
  "configured": true,
  "message": "Tabby is properly configured",
  "missing_fields": [],
  "provider": {
    "id": 1,
    "name": "Tabby",
    "secret_api_key": "sk_...",
    "merchant_code": "MC_...",
    "config": {
      "api_endpoint": "https://api.tabby.ai",
      "test_mode": true
    }
  }
}
```

## Files Created/Modified

### New Files
- `app/Services/TabbyService.php` - Tabby API service class
- `app/PaymentChannels/Drivers/Tabby/Channel.php` - Tabby payment channel driver
- `app/Http/Controllers/Api/TabbyController.php` - API controller for eligibility checks
- `TABBY_INTEGRATION.md` - This documentation file

### Modified Files
- `config/services.php` - Added Tabby configuration
- `routes/web.php` - Added Tabby payment routes
- `routes/api.php` - Added Tabby API routes
- `app/Http/Controllers/Web/PaymentController.php` - Added Tabby verification methods
- `resources/views/design_1/web/cart/payment/index.blade.php` - Added Tabby UI components
- `lang/en/update.php` - Added English translations
- `lang/ar/update.php` - Added Arabic translations

## Integration Flow

### 1. Background Pre-scoring
When a user selects Tabby as a BNPL option, the system automatically checks eligibility by calling the Tabby API.

### 2. Eligibility Check
- Customer data is sent to Tabby for pre-scoring
- System receives eligibility status and rejection reasons
- Tabby option is shown/hidden based on eligibility

### 3. Checkout Session
For eligible customers:
- Creates Tabby checkout session
- Redirects to Tabby hosted payment page
- Stores payment ID for verification

### 4. Payment Processing
- Customer completes payment on Tabby
- Tabby redirects back to merchant URLs
- System verifies payment status
- Order is updated accordingly

## API Endpoints

### Check Eligibility
```
POST /api/tabby/check-eligibility
```

**Request Body:**
```json
{
    "order_id": 123,
    "amount": 100.00,
    "currency": "SAR"
}
```

**Response:**
```json
{
    "success": true,
    "eligible": true,
    "status": "created",
    "data": {...}
}
```

### Payment Verification
```
GET /payments/verify/Tabby?payment_id=xxx
```

## UI Components

### Tabby Modal
- **Eligibility Check**: Shows loading state while checking
- **Eligible State**: Displays installment details and proceed button
- **Ineligible State**: Shows rejection reason and close button
- **Error State**: Shows error message with retry option

### Responsive Design
- Mobile-friendly modal design
- Proper z-index layering
- Backdrop blur effects
- Smooth animations

## Error Handling

The integration includes comprehensive error handling for:
- API failures
- Network issues
- Invalid responses
- Payment verification failures
- Order not found scenarios

## Testing

### Test Credentials
Use Tabby's test environment for development:
- API Endpoint: `https://api.tabby.ai`
- Test Mode: Enabled by default
- Test amounts: Follow Tabby's testing guidelines

### Test Scenarios
1. **Eligible Customer**: Should see Tabby option and proceed to checkout
2. **Ineligible Customer**: Should see rejection message
3. **API Failure**: Should show error state
4. **Payment Success**: Should redirect to success page
5. **Payment Failure**: Should show failure message

## Security

- CSRF protection on all endpoints
- User authentication required
- Order ownership verification
- Secure API key storage
- Input validation and sanitization

## Localization

### English Messages
- Payment method name: "Pay later with Tabby"
- Checkout description: "Use any card."
- Error messages for various rejection reasons

### Arabic Messages
- Payment method name: "ادفع لاحقًا عبر تابي"
- Checkout description: "استخدم أي بطاقة."
- Localized error messages

## Troubleshooting

### Common Issues

1. **Modal not showing**: Check JavaScript console for errors
2. **API calls failing**: Verify Tabby credentials and network connectivity
3. **Payment verification issues**: Check payment ID format and order data
4. **Styling issues**: Ensure CSS is properly loaded

### Debug Mode
Enable debug logging in the TabbyService for detailed API interaction logs.

## Support

For Tabby-specific issues, refer to the official Tabby documentation and support channels.

For integration issues, check the Laravel logs and browser console for error messages.
