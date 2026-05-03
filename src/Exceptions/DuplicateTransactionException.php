<?php

namespace Sslcommerz\Laravel\Exceptions;

/**
 * Thrown when attempting to process an already-processed transaction.
 */
class DuplicateTransactionException extends SslcommerzException
{
    public static function forTranId(string $tranId): self
    {
        return new self("Transaction [{$tranId}] has already been processed.", 409);
    }
}
