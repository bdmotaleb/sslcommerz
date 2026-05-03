<?php

namespace Sslcommerz\Laravel\Exceptions;

/**
 * Thrown when payment session initiation fails.
 */
class PaymentInitiationException extends SslcommerzException
{
    public static function fromResponse(array $response): self
    {
        $reason = $response['failedreason'] ?? 'Unknown error';
        return new self("Payment initiation failed: {$reason}", 422);
    }

    public static function connectionFailed(string $message = ''): self
    {
        return new self("Failed to connect with SSLCOMMERZ API. {$message}", 503);
    }
}
