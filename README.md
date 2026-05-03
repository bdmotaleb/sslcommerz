# SSLCOMMERZ Laravel Payment Gateway

A production-ready Laravel package for integrating the **SSLCOMMERZ** payment gateway (API v4). Built with clean architecture, fully typed DTOs, event-driven callbacks, and extensible contracts.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![Laravel](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x-FF2D20.svg)](https://laravel.com)
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

### Publish Migrations

```bash
php artisan vendor:publish --tag=sslcommerz-migrations
php artisan migrate
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
SSLCOMMERZ_SANDBOX=true
SSLCOMMERZ_STORE_ID=your_store_id
SSLCOMMERZ_STORE_PASSWORD=your_store_password
SSLCOMMERZ_CURRENCY=BDT
SSLCOMMERZ_LOG_ENABLED=true

# Redirect URLs after payment processing
SSLCOMMERZ_REDIRECT_SUCCESS=/payment/success
SSLCOMMERZ_REDIRECT_FAIL=/payment/fail
SSLCOMMERZ_REDIRECT_CANCEL=/payment/cancel
```

### Sandbox Test Credentials

Register at [https://developer.sslcommerz.com/registration/](https://developer.sslcommerz.com/registration/) to get your sandbox credentials.

**Test Card Numbers:**

| Card Type   | Number             | Expiry | CVV |
|------------|-------------------|--------|-----|
| VISA       | 4111111111111111  | 12/26  | 111 |
| Mastercard | 5111111111111111  | 12/26  | 111 |
| Amex       | 371111111111111   | 12/26  | 111 |

**Mobile OTP:** `111111` or `123456`

---

## Quick Start

```php
use Sslcommerz\Laravel\Facades\Sslcommerz;
use Sslcommerz\Laravel\DTOs\PaymentRequestDTO;

// In your controller
public function checkout(Request $request)
{
    $paymentRequest = PaymentRequestDTO::fromArray([
        'tran_id'      => 'ORDER_' . uniqid(),
        'total_amount' => 1500.00,
        'currency'     => 'BDT',
        'cus_name'     => $request->name,
        'cus_email'    => $request->email,
        'cus_phone'    => $request->phone,
        'cus_add1'     => $request->address,
        'cus_city'     => $request->city,
        'cus_postcode' => $request->postcode,
        'cus_country'  => 'Bangladesh',
        'product_name' => 'Premium Subscription',
        'value_a'      => $order->id, // Custom reference
    ]);

    $response = SSLCOMMERZ::initiate($paymentRequest);

    // Redirect to SSLCOMMERZ payment page
    return redirect($response->gatewayPageUrl);
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

**Using Facade:**

```php
use Sslcommerz\Laravel\Facades\Sslcommerz;
use Sslcommerz\Laravel\DTOs\PaymentRequestDTO;

$request = PaymentRequestDTO::fromArray([
    'tran_id'          => 'ORDER_001',
    'total_amount'     => 1000.00,
    'currency'         => 'BDT',
    'cus_name'         => 'John Doe',
    'cus_email'        => 'john@example.com',
    'cus_phone'        => '01711111111',
    'cus_add1'         => 'Dhaka',
    'cus_city'         => 'Dhaka',
    'cus_postcode'     => '1000',
    'cus_country'      => 'Bangladesh',
    'product_name'     => 'Widget Pack',
    'product_category' => 'electronics',
    'product_profile'  => 'physical-goods',
    'shipping_method'  => 'Courier',
    'value_a'          => 'order_ref_001',
]);

$response = SSLCOMMERZ::initiate($request);

if ($response->isSuccessful()) {
    return redirect($response->gatewayPageUrl);
}
```

**Using Dependency Injection:**

```php
use Sslcommerz\Laravel\Contracts\PaymentGatewayInterface;

public function __construct(
    private PaymentGatewayInterface $gateway,
) {}

public function pay()
{
    $response = $this->gateway->initiate($request);
    return redirect($response->gatewayPageUrl);
}
```

### Validate Transaction

```php
$validation = SSLCOMMERZ::validate($valId);

if ($validation->isSuccessful()) {
    // Payment confirmed
    echo "Amount: " . $validation->amount;
    echo "Bank Transaction: " . $validation->bankTranId;
}
```

### Refund

```php
use Sslcommerz\Laravel\DTOs\RefundRequestDTO;

$refund = SSLCOMMERZ::refund(RefundRequestDTO::fromArray([
    'bank_tran_id'   => $bankTranId,
    'refund_amount'  => 500.00,
    'refund_remarks' => 'Customer requested refund',
]));

if ($refund->isSuccessful()) {
    echo "Refund Reference: " . $refund->refundRefId;
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
4. Prevents duplicate processing
5. Updates the `sslcommerz_transactions` table
6. Dispatches events
7. Redirects to configured URLs

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

**Example Listener:**

```php
class UpdateOrderStatus
{
    public function handle(PaymentSucceeded $event): void
    {
        $tranId = $event->payment->tranId;
        $amount = $event->validation->amount;
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

Sslcommerz::shouldReceive('initiate')
    ->once()
    ->andReturn(PaymentResponseDTO::fromApiResponse([
        'status'         => 'SUCCESS',
        'GatewayPageURL' => 'https://sandbox.sslcommerz.com/gw.php',
        'sessionkey'     => 'TEST_SESSION',
    ]));
```

---

## API Reference

### `SSLCOMMERZ::initiate(PaymentRequestDTO $request): PaymentResponseDTO`

Creates a payment session and returns the gateway redirect URL.

### `SSLCOMMERZ::validate(string $valId): ValidationResponseDTO`

Validates a transaction using the validation ID from callback/IPN.

### `SSLCOMMERZ::refund(RefundRequestDTO $request): RefundResponseDTO`

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
3. **Duplicate Prevention**: Transactions are tracked in the database; already-processed transactions are not reprocessed
4. **CSRF Exemption**: Only callback routes from SSLCOMMERZ are CSRF-exempt
5. **Logging**: All gateway interactions are logged for audit trails
6. **Environment Isolation**: Separate endpoints for sandbox and production

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
