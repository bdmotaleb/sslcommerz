<?php

namespace Sslcommerz\Laravel\Exceptions;

/**
 * Thrown when transaction validation fails or returns unexpected results.
 */
class PaymentValidationException extends SslcommerzException
{
    public static function invalidTransaction(string $valId): self
    {
        return new self("Transaction validation failed for val_id: {$valId}", 422);
    }

    public static function amountMismatch(string $expected, string $actual): self
    {
        return new self("Amount mismatch: expected {$expected}, got {$actual}", 422);
    }
}
