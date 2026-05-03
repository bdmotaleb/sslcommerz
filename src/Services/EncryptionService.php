<?php

namespace Sslcommerz\Laravel\Services;

/**
 * Encryption Service
 *
 * Handles AES-256-CBC encryption for SSLCOMMERZ recurring payments.
 * Follows the specific scheme: base64_encode(iv . '|||' . ciphertext)
 */
class EncryptionService
{
    private string $saltKey;

    public function __construct(?string $saltKey = null)
    {
        $this->saltKey = $saltKey ?? config('sslcommerz.salt_key', '');
    }

    /**
     * Encrypt data using AES-256-CBC.
     *
     * @param string $data The plain text or JSON string to encrypt
     * @return string Encrypted string in base64 format with IV
     */
    public function encrypt(string $data): string
    {
        if (empty($this->saltKey)) {
            return $data; // Or throw exception?
        }

        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);

        $ciphertext = openssl_encrypt(
            $data,
            'aes-256-cbc',
            $this->saltKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv . '|||' . $ciphertext);
    }

    /**
     * Decrypt data using AES-256-CBC.
     *
     * @param string $encryptedData The base64 encoded string with IV
     * @return string|false Decrypted string or false on failure
     */
    public function decrypt(string $encryptedData): string|bool
    {
        if (empty($this->saltKey)) {
            return false;
        }

        $decoded = base64_decode($encryptedData);
        if ($decoded === false) {
            return false;
        }

        $parts = explode('|||', $decoded, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$iv, $ciphertext] = $parts;

        return openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            $this->saltKey,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}
