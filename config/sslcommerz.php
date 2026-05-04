<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sandbox Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, all API requests will be directed to the SSLCOMMERZ
    | sandbox environment. Disable this for production transactions.
    |
    */
    'sandbox' => env('SSLCOMMERZ_SANDBOX', true),

    /*
    |--------------------------------------------------------------------------
    | Store Credentials
    |--------------------------------------------------------------------------
    |
    | Your SSLCOMMERZ store credentials. These are provided when you
    | register at https://developer.sslcommerz.com/registration/
    | IMPORTANT: Never use your merchant panel login password here.
    |
    */
    'store_id' => env('SSLCOMMERZ_STORE_ID', ''),
    'store_password' => env('SSLCOMMERZ_STORE_PASSWORD', ''),
    'salt_key' => env('SSLCOMMERZ_SALT_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for transactions. SSLCOMMERZ supports
    | BDT, USD, EUR, GBP, AUD, CAD, SGD, and more.
    |
    */
    'currency' => env('SSLCOMMERZ_CURRENCY', 'BDT'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | SSLCOMMERZ API endpoints for sandbox and live environments.
    | These follow the official SSLCOMMERZ API v4 specification.
    |
    */
    'endpoints' => [
        'sandbox' => [
            'base'        => 'https://sandbox.sslcommerz.com',
            'initiate'    => 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php',
            'validation'  => 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php',
            'transaction' => 'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php',
            'recurring'   => 'https://sandbox.sslcommerz.com/validator/api/v4/',
        ],
        'live' => [
            'base'        => 'https://securepay.sslcommerz.com',
            'initiate'    => 'https://securepay.sslcommerz.com/gwprocess/v4/api.php',
            'validation'  => 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php',
            'transaction' => 'https://securepay.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php',
            'recurring'   => 'https://securepay.sslcommerz.com/validator/api/v4/',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the callback route prefix, middleware, and paths.
    | These routes handle SSLCOMMERZ payment callbacks.
    |
    */
    'routes' => [
        'prefix'  => 'ssl',
        'success' => '/ssl/success',
        'fail'    => '/ssl/fail',
        'cancel'  => '/ssl/cancel',
        'ipn'     => '/ssl/ipn',
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect URLs After Payment
    |--------------------------------------------------------------------------
    |
    | Where to redirect the user after the callback controller processes
    | the payment result. Set these to your application's pages.
    |
    */
    'redirect' => [
        'success' => env('SSLCOMMERZ_SUCCESS', '/payment/success'),
        'fail'    => env('SSLCOMMERZ_FAIL', '/payment/fail'),
        'cancel'  => env('SSLCOMMERZ_CANCEL', '/payment/cancel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging of all gateway interactions.
    | Useful for debugging and audit trails.
    |
    */
    'logging' => [
        'enabled' => env('SSLCOMMERZ_LOG_ENABLED', true),
        'channel' => env('SSLCOMMERZ_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Defaults
    |--------------------------------------------------------------------------
    |
    | Default values for product information sent to SSLCOMMERZ.
    | These can be overridden per-transaction.
    |
    */
    'product' => [
        'profile'  => 'general',   // general, physical-goods, non-physical-goods, airline-tickets, travel-vertical, telecom-vertical
        'category' => 'general',
    ],

];
