<?php

namespace Sslcommerz\Laravel\Contracts;

use Sslcommerz\Laravel\DTOs\PaymentRequestDTO;
use Sslcommerz\Laravel\DTOs\PaymentResponseDTO;
use Sslcommerz\Laravel\DTOs\RefundRequestDTO;
use Sslcommerz\Laravel\DTOs\RefundResponseDTO;
use Sslcommerz\Laravel\DTOs\TransactionQueryDTO;
use Sslcommerz\Laravel\DTOs\ValidationResponseDTO;

/**
 * Payment Gateway Contract
 *
 * This interface defines the standard operations for any payment gateway.
 * Implement this interface to add support for additional gateways
 * such as bKash, Nagad, Stripe, etc.
 */
interface PaymentGatewayInterface
{
    /**
     * Initiate a payment session with the gateway.
     *
     * @param PaymentRequestDTO $request Payment details
     * @return PaymentResponseDTO Gateway response with redirect URL
     *
     * @throws \Sslcommerz\Laravel\Exceptions\PaymentInitiationException
     */
    public function initiate(PaymentRequestDTO $request): PaymentResponseDTO;

    /**
     * Validate a completed transaction using the validation ID.
     *
     * @param string $valId The validation ID received from callback/IPN
     * @return ValidationResponseDTO Validation result
     *
     * @throws \Sslcommerz\Laravel\Exceptions\PaymentValidationException
     */
    public function validate(string $valId): ValidationResponseDTO;

    /**
     * Initiate a refund for a previously successful transaction.
     *
     * @param RefundRequestDTO $request Refund details
     * @return RefundResponseDTO Refund result
     *
     * @throws \Sslcommerz\Laravel\Exceptions\RefundException
     */
    public function refund(RefundRequestDTO $request): RefundResponseDTO;

    /**
     * Query transaction status by merchant transaction ID.
     *
     * @param string $tranId Your unique transaction ID
     * @return TransactionQueryDTO Transaction details
     */
    public function queryTransaction(string $tranId): TransactionQueryDTO;

    /**
     * Verify the hash/signature integrity of callback data.
     *
     * @param array $data The callback POST data
     * @return bool True if hash is valid
     */
    public function verifyHash(array $data): bool;
}
