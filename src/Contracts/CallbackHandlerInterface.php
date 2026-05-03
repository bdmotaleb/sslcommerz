<?php

namespace Sslcommerz\Laravel\Contracts;

use Illuminate\Http\Request;

/**
 * Callback Handler Contract
 *
 * Implement this interface to customize how payment callbacks
 * are handled in your application. Bind your implementation
 * in the service container to override the default behavior.
 */
interface CallbackHandlerInterface
{
    /**
     * Handle a successful payment callback.
     */
    public function handleSuccess(Request $request): mixed;

    /**
     * Handle a failed payment callback.
     */
    public function handleFail(Request $request): mixed;

    /**
     * Handle a cancelled payment callback.
     */
    public function handleCancel(Request $request): mixed;

    /**
     * Handle an IPN (Instant Payment Notification) callback.
     */
    public function handleIpn(Request $request): mixed;
}
