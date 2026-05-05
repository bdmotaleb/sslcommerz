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

$controller = config('sslcommerz.routes.controller', SslcommerzCallbackController::class);

Route::prefix(config('sslcommerz.routes.prefix', 'ssl'))->group(function () use ($controller) {
    Route::post('/success', [$controller, 'success'])
        ->name('sslcommerz.success');

    Route::post('/fail', [$controller, 'fail'])
        ->name('sslcommerz.fail');

    Route::post('/cancel', [$controller, 'cancel'])
        ->name('sslcommerz.cancel');

    Route::post('/ipn', [$controller, 'ipn'])
        ->name('sslcommerz.ipn');
});
