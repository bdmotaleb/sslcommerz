<?php

namespace Sslcommerz\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Sslcommerz\Laravel\Contracts\PaymentGatewayInterface;
use Sslcommerz\Laravel\DTOs\PaymentRequestDTO;
use Sslcommerz\Laravel\DTOs\PaymentResponseDTO;
use Sslcommerz\Laravel\DTOs\RefundRequestDTO;
use Sslcommerz\Laravel\DTOs\RefundResponseDTO;
use Sslcommerz\Laravel\DTOs\TransactionQueryDTO;
use Sslcommerz\Laravel\DTOs\ValidationResponseDTO;
use Sslcommerz\Laravel\Services\EncryptionService;

/**
 * SSLCOMMERZ Facade
 * Provides a clean, expressive API for interacting with the SSLCOMMERZ gateway.
 * @method static PaymentResponseDTO initiate(PaymentRequestDTO|array $request)
 * @method static ValidationResponseDTO validate(string $valId)
 * @method static RefundResponseDTO refund(RefundRequestDTO|array $request)
 * @method static TransactionQueryDTO queryTransaction(string $tranId)
 * @method static ValidationResponseDTO queryBySession(string $sessionKey)
 * @method static RefundResponseDTO queryRefundStatus(string $refundRefId)
 * @method static bool verifyHash(array $data)
 * @method static EncryptionService getEncryptionService()
 * @method static array getSubscriptionStatus(string $refer, string $subscriptionId)
 * @method static array disableSubscription(string $refer, string $subscriptionId)
 * @method static array enableSubscription(string $refer, string $subscriptionId)
 * @method static array cancelSubscription(string $refer, string $subscriptionId)
 *
 * @see \Sslcommerz\Laravel\Services\SslcommerzService
 */
class SSLCOMMERZ extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentGatewayInterface::class;
    }
}
