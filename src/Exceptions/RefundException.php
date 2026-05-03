<?php

namespace Sslcommerz\Laravel\Exceptions;

/**
 * Thrown when a refund operation fails.
 */
class RefundException extends SslcommerzException
{
    public static function fromResponse(array $response): self
    {
        $reason = $response['errorReason'] ?? 'Unknown error';
        return new self("Refund failed: {$reason}", 422);
    }
}
