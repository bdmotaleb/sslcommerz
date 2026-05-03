<?php

namespace Sslcommerz\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Sslcommerz\Laravel\Services\HashValidator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify SSLCOMMERZ callback hash integrity.
 *
 * Apply this middleware to callback routes to ensure that the incoming
 * request data has not been tampered with during transmission.
 */
class VerifySslcommerzCallback
{
    public function __construct(
        private readonly HashValidator $hashValidator,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Only verify hash for POST requests with verify_sign
        if ($request->isMethod('post') && $request->has('verify_sign')) {
            if (! $this->hashValidator->verify($request->all())) {
                return response()->json([
                    'error'   => 'Invalid hash signature',
                    'message' => 'The callback data integrity check failed.',
                ], 403);
            }
        }

        return $next($request);
    }
}
