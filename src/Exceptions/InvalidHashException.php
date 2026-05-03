<?php

namespace Sslcommerz\Laravel\Exceptions;

/**
 * Thrown when callback hash/signature verification fails.
 */
class InvalidHashException extends SslcommerzException
{
    public static function verificationFailed(): self
    {
        return new self('SSLCOMMERZ hash verification failed. The callback data may have been tampered with.', 403);
    }
}
