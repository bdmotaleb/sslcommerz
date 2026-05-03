<?php

namespace Sslcommerz\Laravel\Services;

use Sslcommerz\Laravel\Exceptions\InvalidHashException;

/**
 * SSLCOMMERZ Hash Validator
 *
 * Validates the integrity of callback/IPN data by verifying the MD5 hash
 * signature provided by SSLCOMMERZ. This prevents data tampering.
 *
 * Hash algorithm:
 * 1. Read verify_key (comma-separated field names)
 * 2. Sort field names alphabetically
 * 3. Concatenate: field1=value1&field2=value2...&store_passwd=<password_md5>
 * 4. MD5 hash the concatenated string
 * 5. Compare with verify_sign
 */
class HashValidator
{
    public function __construct(
        private readonly string $storePassword,
    ) {
    }

    /**
     * Verify the hash signature of callback data.
     *
     * @param array $data The full callback/IPN POST data
     * @return bool True if hash is valid
     */
    public function verify(array $data): bool
    {
        if (empty($data['verify_sign']) || empty($data['verify_key'])) {
            return false;
        }

        $verifySign = $data['verify_sign'];
        $verifyKey = $data['verify_key'];

        // Get the list of fields used to generate the hash
        $fields = explode(',', $verifyKey);
        sort($fields);

        // Build the hash string
        $hashString = '';
        foreach ($fields as $field) {
            $hashString .= $field . '=' . ($data[$field] ?? '') . '&';
        }

        // Append store password (MD5 of the password)
        $hashString .= 'store_passwd=' . md5($this->storePassword);

        // Generate and compare hash
        $generatedHash = md5($hashString);

        return $generatedHash === $verifySign;
    }

    /**
     * Verify the hash or throw an exception.
     *
     * @throws InvalidHashException
     */
    public function verifyOrFail(array $data): void
    {
        if (! $this->verify($data)) {
            throw InvalidHashException::verificationFailed();
        }
    }
}
