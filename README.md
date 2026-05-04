# SSLCOMMERZ Laravel Payment Gateway

A production-ready Laravel package for integrating the **SSLCOMMERZ** payment gateway (API v4). Built with clean architecture, fully typed DTOs, event-driven callbacks, and extensible contracts.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![Laravel](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x%20%7C%2013.x-FF2D20.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

---

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Payment Flow](#payment-flow)
- [Usage](#usage)
  - [Initiate Payment](#initiate-payment)
  - [Validate Transaction](#validate-transaction)
  - [Refund](#refund)
  - [Query Transaction](#query-transaction)
- [Callback Handling](#callback-handling)
  - [Redirect URLs](#redirect-urls-after-payment)
- [Events](#events)
- [Hash Verification](#hash-verification)
- [Custom Controllers](#custom-controllers)
- [Testing](#testing)
- [API Reference](#api-reference)
- [Security](#security)
- [Troubleshooting](#troubleshooting)

---

## Installation

```bash
composer require sslcommerz/laravel
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=sslcommerz-config
```


### Publish Routes (Optional)

```bash
php artisan vendor:publish --tag=sslcommerz-routes
```

### Publish Everything

```bash
php artisan vendor:publish --tag=sslcommerz
```

---

## Configuration

Add these variables to your `.env` file:

```env
# Your application URL (IMPORTANT: SSLCOMMERZ sends callbacks to this URL)
APP_URL=https://yourdomain.com

SSLCOMMERZ_SANDBOX=true
SSLCOMMERZ_STORE_ID=your_store_id
SSLCOMMERZ_STORE_PASSWORD=your_store_password
SSLCOMMERZ_CURRENCY=BDT
SSLCOMMERZ_LOG_ENABLED=true

# Redirect URLs after payment processing
SSLCOMMERZ_SUCCESS=/payment/success
SSLCOMMERZ_FAIL=/payment/fail
SSLCOMMERZ_CANCEL=/payment/cancel
```

> **⚠️ Important:** `APP_URL` must be set to your publicly accessible domain (e.g. `https://yourdomain.com`). SSLCOMMERZ uses this to send POST callbacks (success, fail, cancel, IPN) to your server. If `APP_URL` is `http://localhost`, callbacks from SSLCOMMERZ will fail in production.

### Sandbox Test Credentials

Register at [https://developer.sslcommerz.com/registration/](https://developer.sslcommerz.com/registration/) to get your sandbox credentials.

**Test Card Numbers:**

| Card Type   | Number             | Expiry | CVV |
|------------|-------------------|--------|-----|
| VISA       | 4111111111111111  | 12/36  | 111 |
| Mastercard | 5111111111111111  | 12/36  | 111 |
| Amex       | 371111111111111   | 12/36   | 111 |

**Mobile OTP:** `111111` or `123456`

---

## Quick Start

```php
use Sslcommerz\Laravel\Facades\SSLCOMMERZ;

// In your controller
public function checkout(Request $request)
{
    // Simply pass an array of parameters to initiate payment
    $response = SSLCOMMERZ::initiate([
        'tran_id'      => 'ORDER_' . uniqid(),
        'total_amount' => 1500.00,
        'cus_name'     => $request->name,
        'cus_email'    => $request->email,
        // ... other parameters as needed ...
    ]);

    // Check if initiation was successful
    if ($response->isSuccessful()) {
        return $response->redirect(); // Fluent redirect to SSLCOMMERZ
    }

    return back()->with('error', $response->failedReason);
}
```

---

## Payment Flow

```
┌──────────┐     ┌──────────────┐     ┌─────────────┐
│  Customer │────▶│  Your Server │────▶│ SSLCOMMERZ  │
│  Browser  │     │  (Laravel)   │     │   API       │
└──────────┘     └──────────────┘     └─────────────┘
     │                  │                     │
     │  1. Checkout     │                     │
     │─────────────────▶│                     │
     │                  │  2. Create Session   │
     │                  │────────────────────▶│
     │                  │  3. GatewayPageURL   │
     │                  │◀────────────────────│
     │  4. Redirect     │                     │
     │◀─────────────────│                     │
     │                  │                     │
     │  5. Pay on SSLCOMMERZ page             │
     │───────────────────────────────────────▶│
     │                  │                     │
     │                  │  6. IPN Notification │
     │                  │◀────────────────────│
     │                  │  7. Validate (API)   │
     │                  │────────────────────▶│
     │                  │  8. VALID            │
     │                  │◀────────────────────│
     │                  │                     │
     │  9. Redirect to success_url            │
     │◀───────────────────────────────────────│
     │                  │                     │
```

---

## Usage

### Initiate Payment

```php
use Sslcommerz\Laravel\Facades\SSLCOMMERZ;

$response = SSLCOMMERZ::initiate([
    'tran_id'          => 'ORDER_' . uniqid(),
    'total_amount'     => 1000.00,
    'cus_name'         => 'John Doe',
    'cus_email'        => 'john@example.com',
    'cus_phone'        => '01711111111',
    // ... other parameters as needed ...
]);

// Response behaves like an array and an object
if ($response['status'] === 'SUCCESS') {
    return $response->redirect();
}
```

#### Specialized Parameters (API v4)

| Category | Parameters |
|----------|------------|
| **Airline** | `pnr`, `hours_till_departure`, `flight_type`, `journey_from_to`, `third_party_booking` |
| **Travel** | `hotel_name`, `length_of_stay`, `check_in_time`, `hotel_city` |
| **Telecom** | `product_type`, `topup_number`, `country_topup` |
| **Logistics**| `logistic_pickup_id`, `logistic_delivery_type` |

### Recurring Payments (Easycheckout)

SSLCOMMERZ support recurring payments via a `schedule` parameter.

**1. Configure SALT Key:**
Add `SSLCOMMERZ_SALT_KEY` to your `.env` file. This key is provided by the SSLCOMMERZ team.

**2. Initiate Recurring Payment:**

```php
use Sslcommerz\Laravel\Facades\SSLCOMMERZ;

// 1. Prepare schedule data
$schedule = json_encode([
    'refer'   => 'REF1234', // Plan ID from Merchant Panel
    'acct_no' => 'CUS_001',  // Customer account reference
    'type'    => 'monthly',
    'dayofmonth' => '24',
]);

// 2. Encrypt the schedule
$encryptedSchedule = SSLCOMMERZ::getEncryptionService()->encrypt($schedule);

// 3. Initiate payment
$response = SSLCOMMERZ::initiate([
    // ... mandatory fields ...
    'schedule'     => $encryptedSchedule,
]);

if ($response->isSuccessful()) {
    // If successful, a subscription_id will be returned in the callback/response
    $subscriptionId = $response->subscriptionId;
}
```

**3. Manage Subscriptions:**

```php
// Check status
$status = SSLCOMMERZ::getSubscriptionStatus($refer, $subscriptionId);

// Disable temporarily
SSLCOMMERZ::disableSubscription($refer, $subscriptionId);

// Cancel permanently
SSLCOMMERZ::cancelSubscription($refer, $subscriptionId);
```

### Validate Transaction

```php
$validation = SSLCOMMERZ::validate($valId);

if ($validation->isSuccessful()) {
    // Payment confirmed - access via array or object
    echo "Amount: " . $validation['amount'];
    echo "Bank Transaction: " . $validation->bankTranId;
}
```

### Refund

```php
// Simply pass an array
$refund = SSLCOMMERZ::refund([
    'bank_tran_id'   => $bankTranId,
    'refund_amount'  => 500.00,
    'refund_remarks' => 'Customer requested refund',
]);

if ($refund->isSuccessful()) {
    echo "Refund Reference: " . $refund['refund_ref_id'];
}
```

### Query Transaction

```php
$result = SSLCOMMERZ::queryTransaction('ORDER_001');

if ($result->hasTransactions()) {
    $latest = $result->getLatestSuccessful();
    echo "Status: " . $latest['status'];
}
```

---

## Callback Handling

The package registers these routes automatically:

| Route            | Name                  | Purpose                    |
|------------------|-----------------------|----------------------------|
| POST `/ssl/success` | `sslcommerz.success` | Successful payment         |
| POST `/ssl/fail`    | `sslcommerz.fail`    | Failed payment             |
| POST `/ssl/cancel`  | `sslcommerz.cancel`  | Cancelled payment          |
| POST `/ssl/ipn`     | `sslcommerz.ipn`     | Instant Payment Notification |

All routes exclude CSRF verification since SSLCOMMERZ sends POST requests.

The default controller:
1. Parses callback data into a `CallbackDTO`
2. Verifies hash integrity (IPN)
3. Validates via SSLCOMMERZ API
4. Dispatches events
5. Redirects to configured URLs

### Redirect URLs (After Payment)

After SSLCOMMERZ processes the payment and sends a callback to your server, the package **redirects the user** to a result page. These redirect URLs are configured in `config/sslcommerz.php`:

```php
'redirect' => [
    'success' => env('SSLCOMMERZ_SUCCESS', '/payment/success'),
    'fail'    => env('SSLCOMMERZ_FAIL', '/payment/fail'),
    'cancel'  => env('SSLCOMMERZ_CANCEL', '/payment/cancel'),
],
```

The package ships with **default GET routes** that handle these paths out of the box:

| Route                  | Name                          | Response |
|------------------------|-------------------------------|----------|
| GET `/payment/success` | `sslcommerz.redirect.success` | JSON with transaction details |
| GET `/payment/fail`    | `sslcommerz.redirect.fail`    | JSON with failure info |
| GET `/payment/cancel`  | `sslcommerz.redirect.cancel`  | JSON with cancellation info |

**Default JSON response example** (`/payment/success`):

```json
{
    "success": true,
    "message": "Payment completed successfully.",
    "tran_id": "ORDER_6651a3f2e4b01",
    "status": "VALID"
}
```

The transaction data is flashed to the session. You can access it via:

```php
session('sslcommerz_tran_id')  // Transaction ID
session('sslcommerz_status')   // Payment status (VALID, FAILED, CANCELLED)
session('sslcommerz_message')  // Optional message
```

#### Custom Redirect Pages

To use your own result pages instead of the default JSON responses, define your own routes and update `.env`:

**1. Define your routes:**

```php
// routes/web.php
Route::get('/order/thank-you', function () {
    return view('order.thank-you', [
        'tran_id' => session('sslcommerz_tran_id'),
        'status'  => session('sslcommerz_status'),
    ]);
});

Route::get('/order/failed', function () {
    return view('order.failed', [
        'tran_id' => session('sslcommerz_tran_id'),
    ]);
});
```

**2. Update your `.env`:**

```env
SSLCOMMERZ_SUCCESS=/order/thank-you
SSLCOMMERZ_FAIL=/order/failed
SSLCOMMERZ_CANCEL=/order/failed
```

> **Note:** When using custom redirect URLs that don't match the default `/payment/*` paths, the package's default routes will still be registered but won't be used. This is harmless.

---

## Events

Listen to these events in your `EventServiceProvider`:

```php
use Sslcommerz\Laravel\Events\PaymentSucceeded;
use Sslcommerz\Laravel\Events\PaymentFailed;
use Sslcommerz\Laravel\Events\PaymentCancelled;
use Sslcommerz\Laravel\Events\IpnReceived;
use Sslcommerz\Laravel\Events\RefundInitiated;

protected $listen = [
    PaymentSucceeded::class => [
        UpdateOrderStatus::class,
        SendPaymentConfirmation::class,
    ],
    PaymentFailed::class => [
        HandleFailedPayment::class,
    ],
    IpnReceived::class => [
        ProcessIpnNotification::class,
    ],
];
```

### Persistence (Handling Orders)

Since this package is database-agnostic, you should handle transaction persistence in your own application using listeners.

**Example Listener:**

```php
namespace App\Listeners;

use Sslcommerz\Laravel\Events\PaymentSucceeded;
use App\Models\Order;

class UpdateOrderStatus
{
    public function handle(PaymentSucceeded $event): void
    {
        // Access callback data via $event->payment
        // Access API validation data via $event->validation
        
        $tranId = $event->payment->tranId;
        $orderId = $event->payment->valueA; // Your custom reference

        Order::where('id', $orderId)->update([
            'status'     => 'paid',
            'paid_at'    => now(),
            'payment_id' => $tranId,
        ]);
    }
}
```

---

## Hash Verification

The package automatically verifies hash signatures on IPN callbacks. You can also verify manually:

```php
$isValid = SSLCOMMERZ::verifyHash($request->all());
```

Or use the middleware on your own routes:

```php
Route::middleware('sslcommerz.verify')
    ->post('/custom-callback', [CustomController::class, 'handle']);
```

---

## Custom Controllers

Override the default callback behavior by publishing routes and pointing to your own controllers:

```bash
php artisan vendor:publish --tag=sslcommerz-routes
```

Then edit `routes/sslcommerz.php`:

```php
Route::post('/success', [YourPaymentController::class, 'success'])
    ->name('sslcommerz.success');
```

---

## Testing

### Run Package Tests

```bash
composer install
./vendor/bin/phpunit
```

### Mock in Your Application Tests

```php
use Sslcommerz\Laravel\Facades\Sslcommerz;
use Sslcommerz\Laravel\DTOs\PaymentResponseDTO;

SSLCOMMERZ::shouldReceive('initiate')
    ->once()
    ->andReturn(PaymentResponseDTO::fromApiResponse([
        'status'         => 'SUCCESS',
        'GatewayPageURL' => 'https://sandbox.sslcommerz.com/gw.php',
        'sessionkey'     => 'TEST_SESSION',
    ]));
```

---

## API Reference

### `SSLCOMMERZ::initiate(PaymentRequestDTO|array $request): PaymentResponseDTO`

Creates a payment session. If an array is passed, it is automatically converted to a DTO with sensible defaults.

### `SSLCOMMERZ::validate(string $valId): ValidationResponseDTO`

Validates a transaction using the validation ID from callback/IPN.

### `SSLCOMMERZ::refund(RefundRequestDTO|array $request): RefundResponseDTO`

Initiates a refund for a previously successful transaction.

### `SSLCOMMERZ::queryTransaction(string $tranId): TransactionQueryDTO`

Queries all transactions associated with a merchant transaction ID.

### `SSLCOMMERZ::queryBySession(string $sessionKey): ValidationResponseDTO`

Queries transaction status by SSLCOMMERZ session key.

### `SSLCOMMERZ::queryRefundStatus(string $refundRefId): RefundResponseDTO`

Checks the current status of a refund request.

### `SSLCOMMERZ::verifyHash(array $data): bool`

Verifies the MD5 hash signature of callback data.

---

## Security

This package implements multiple layers of security:

1. **Hash Verification**: All IPN callbacks are verified using SSLCOMMERZ's MD5 signature algorithm
2. **API Validation**: Every successful payment is validated server-side via the Order Validation API
3. **CSRF Exemption**: Only callback routes from SSLCOMMERZ are CSRF-exempt
4. **Logging**: All gateway interactions are logged for audit trails
5. **Environment Isolation**: Separate endpoints for sandbox and production

### Best Practices

- Always validate transactions via the API, never trust callback data alone
- Monitor `risk_level` in validation responses (0 = Safe, 1 = Risky)
- Use `value_a` through `value_d` to pass your own references
- Set up IPN as the primary notification method (works even if user closes browser)
- Register your production IP at SSLCOMMERZ for refund API access

---

## Troubleshooting

| Issue | Solution |
|-------|---------|
| "Invalid Store ID" | Check `SSLCOMMERZ_STORE_ID` in `.env` |
| CSRF token mismatch | Callback routes already exclude CSRF — check if you overrode routes |
| IPN not received | Ensure your server is reachable from internet on port 80/443 |
| Hash verification fails | Verify `SSLCOMMERZ_STORE_PASSWORD` matches your SSLCOMMERZ dashboard |
| Connection timeout | Whitelist SSLCOMMERZ IPs: `103.26.139.87` (sandbox), `103.26.139.81` (live) |

---

## License

MIT License. See [LICENSE](LICENSE) for details.
