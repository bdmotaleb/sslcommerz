<?php

use Illuminate\Support\Facades\Route;
use Sslcommerz\Laravel\Http\Controllers\SslcommerzCallbackController;

/*
|--------------------------------------------------------------------------
| SSLCOMMERZ Callback Routes
|--------------------------------------------------------------------------
|
| These routes handle the payment callbacks from the SSLCOMMERZ gateway.
| SSLCOMMERZ sends POST requests to these endpoints after payment.
|
| All routes exclude CSRF verification because SSLCOMMERZ sends POST
| requests directly from their servers (IPN) or via form redirects.
|
| You can publish and customize these routes:
| php artisan vendor:publish --tag=sslcommerz-routes
|
*/

Route::prefix(config('sslcommerz.routes.prefix', 'ssl'))->group(function () {
    Route::post('/success', [SslcommerzCallbackController::class, 'success'])
        ->name('sslcommerz.success');

    Route::post('/fail', [SslcommerzCallbackController::class, 'fail'])
        ->name('sslcommerz.fail');

    Route::post('/cancel', [SslcommerzCallbackController::class, 'cancel'])
        ->name('sslcommerz.cancel');

    Route::post('/ipn', [SslcommerzCallbackController::class, 'ipn'])
        ->name('sslcommerz.ipn');
});
