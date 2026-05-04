<?php

namespace Sslcommerz\Laravel\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sslcommerz\Laravel\Contracts\PaymentGatewayInterface;
use Sslcommerz\Laravel\DTOs\PaymentRequestDTO;
use Sslcommerz\Laravel\DTOs\PaymentResponseDTO;
use Sslcommerz\Laravel\DTOs\RefundRequestDTO;
use Sslcommerz\Laravel\DTOs\RefundResponseDTO;
use Sslcommerz\Laravel\DTOs\TransactionQueryDTO;
use Sslcommerz\Laravel\DTOs\ValidationResponseDTO;
use Sslcommerz\Laravel\Exceptions\PaymentInitiationException;
use Sslcommerz\Laravel\Exceptions\PaymentValidationException;
use Sslcommerz\Laravel\Exceptions\RefundException;

/**
 * SSLCOMMERZ Service
 *
 * Core service implementing the PaymentGatewayInterface for SSLCOMMERZ.
 * Handles all API communication with the SSLCOMMERZ payment gateway.
 */
class SslcommerzService implements PaymentGatewayInterface
{
    private HashValidator $hashValidator;
    private EncryptionService $encryption;

    public function __construct()
    {
        $this->hashValidator = new HashValidator(
            config('sslcommerz.store_password')
        );
        $this->encryption = new EncryptionService();
    }

    // ---------------------------------------------------------------
    //  Payment Initiation
    // ---------------------------------------------------------------

    /**
     * {@inheritDoc}
     */
    public function initiate(PaymentRequestDTO|array $request): PaymentResponseDTO
    {
        try {
            if (is_array($request)) {
                $request = PaymentRequestDTO::fromArray($request);
            }

            $payload = $this->buildInitiationPayload($request);

            $this->logInteraction('initiate', [
                'tran_id' => $request->tranId,
                'amount'  => $request->totalAmount,
            ]);

            $response = Http::asForm()
                ->timeout(30)
                ->connectTimeout(30)
                ->post($this->getEndpoint('initiate'), $payload);

            if (! $response->successful()) {
                $this->logInteraction('initiate_failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return PaymentResponseDTO::failed("HTTP {$response->status()}: Connection failed");
            }

            $data = $response->json();

            if (empty($data) || ! is_array($data)) {
                return PaymentResponseDTO::failed('Invalid JSON response from gateway');
            }

            $dto = PaymentResponseDTO::fromApiResponse($data);

            if (! $dto->isSuccessful()) {
                $this->logInteraction('initiate_failed', $data);
                return $dto;
            }


            $this->logInteraction('initiate_success', [
                'tran_id'     => $request->tranId,
                'session_key' => $dto->sessionKey,
            ]);

            return $dto;

        } catch (\Exception $e) {
            $this->logInteraction('initiate_exception', [
                'message' => $e->getMessage(),
            ]);
            return PaymentResponseDTO::failed($e->getMessage());
        }
    }

    // ---------------------------------------------------------------
    //  Transaction Validation
    // ---------------------------------------------------------------

    /**
     * {@inheritDoc}
     */
    public function validate(string $valId): ValidationResponseDTO
    {
        $this->logInteraction('validate', ['val_id' => $valId]);

        $response = Http::timeout(30)
            ->connectTimeout(30)
            ->get($this->getEndpoint('validation'), [
                'val_id'       => $valId,
                'store_id'     => config('sslcommerz.store_id'),
                'store_passwd' => config('sslcommerz.store_password'),
                'format'       => 'json',
                'v'            => '1',
            ]);

        if (! $response->successful()) {
            throw PaymentValidationException::invalidTransaction($valId);
        }

        $data = $response->json();
        $dto = ValidationResponseDTO::fromApiResponse($data);

        $this->logInteraction('validate_result', [
            'val_id' => $valId,
            'status' => $dto->status,
        ]);

        return $dto;
    }

    // ---------------------------------------------------------------
    //  Refund
    // ---------------------------------------------------------------

    /**
     * {@inheritDoc}
     */
    public function refund(RefundRequestDTO|array $request): RefundResponseDTO
    {
        try {
            if (is_array($request)) {
                $request = RefundRequestDTO::fromArray($request);
            }

            $this->logInteraction('refund', [
                'bank_tran_id'  => $request->bankTranId,
                'refund_amount' => $request->refundAmount,
            ]);

            $params = array_merge($request->toApiPayload(), [
                'store_id'     => config('sslcommerz.store_id'),
                'store_passwd' => config('sslcommerz.store_password'),
                'v'            => '1',
                'format'       => 'json',
            ]);

            $response = Http::timeout(30)
                ->connectTimeout(30)
                ->get($this->getEndpoint('transaction'), $params);

            if (! $response->successful()) {
                return RefundResponseDTO::failed("HTTP {$response->status()}: Connection failed");
            }

            $data = $response->json();
            $dto = RefundResponseDTO::fromApiResponse($data);

            $this->logInteraction('refund_result', [
                'status'        => $dto->status,
                'refund_ref_id' => $dto->refundRefId,
            ]);

            return $dto;

        } catch (\Exception $e) {
            $this->logInteraction('refund_exception', [
                'message' => $e->getMessage(),
            ]);
            return RefundResponseDTO::failed($e->getMessage());
        }
    }

    // ---------------------------------------------------------------
    //  Transaction Query
    // ---------------------------------------------------------------

    /**
     * {@inheritDoc}
     */
    public function queryTransaction(string $tranId): TransactionQueryDTO
    {
        $this->logInteraction('query_transaction', ['tran_id' => $tranId]);

        $response = Http::timeout(30)
            ->connectTimeout(30)
            ->get($this->getEndpoint('transaction'), [
                'tran_id'      => $tranId,
                'store_id'     => config('sslcommerz.store_id'),
                'store_passwd' => config('sslcommerz.store_password'),
                'format'       => 'json',
                'v'            => '1',
            ]);

        if (! $response->successful()) {
            return new TransactionQueryDTO('FAILED', 0, [], []);
        }

        $data = $response->json();

        return TransactionQueryDTO::fromApiResponse($data);
    }

    /**
     * Query transaction status by SSLCOMMERZ session key.
     */
    public function queryBySession(string $sessionKey): ValidationResponseDTO
    {
        $response = Http::timeout(30)
            ->connectTimeout(30)
            ->get($this->getEndpoint('transaction'), [
                'sessionkey'   => $sessionKey,
                'store_id'     => config('sslcommerz.store_id'),
                'store_passwd' => config('sslcommerz.store_password'),
                'format'       => 'json',
                'v'            => '1',
            ]);

        $data = $response->json() ?? [];

        return ValidationResponseDTO::fromApiResponse($data);
    }

    /**
     * Query refund status by refund reference ID.
     */
    public function queryRefundStatus(string $refundRefId): RefundResponseDTO
    {
        $response = Http::timeout(30)
            ->connectTimeout(30)
            ->get($this->getEndpoint('transaction'), [
                'refund_ref_id' => $refundRefId,
                'store_id'      => config('sslcommerz.store_id'),
                'store_passwd'  => config('sslcommerz.store_password'),
                'format'        => 'json',
            ]);

        $data = $response->json() ?? [];

        return RefundResponseDTO::fromApiResponse($data);
    }

    // ---------------------------------------------------------------
    //  Recurring Payments
    // ---------------------------------------------------------------

    /**
     * Get the current status of a subscription.
     */
    public function getSubscriptionStatus(string $refer, string $subscriptionId): array
    {
        $this->logInteraction('get_subscription_status', [
            'refer' => $refer,
            'subscription_id' => $subscriptionId,
        ]);

        $response = Http::asForm()->post($this->getEndpoint('recurring'), [
            'store_id'        => config('sslcommerz.store_id'),
            'store_passwd'    => config('sslcommerz.store_password'),
            'refer'           => $refer,
            'subscription_id' => $subscriptionId,
            'action'          => 'getSubscriptionStatus',
        ]);

        return $response->json() ?? [];
    }

    /**
     * Disable a recurring subscription.
     */
    public function disableSubscription(string $refer, string $subscriptionId): array
    {
        return $this->modifySubscription($refer, $subscriptionId, 'disableSubscription');
    }

    /**
     * Enable a recurring subscription.
     */
    public function enableSubscription(string $refer, string $subscriptionId): array
    {
        return $this->modifySubscription($refer, $subscriptionId, 'enableSubscription');
    }

    /**
     * Cancel a recurring subscription permanently.
     */
    public function cancelSubscription(string $refer, string $subscriptionId): array
    {
        return $this->modifySubscription($refer, $subscriptionId, 'cancelSubscription');
    }

    /**
     * Modify subscription state.
     */
    private function modifySubscription(string $refer, string $subscriptionId, string $action): array
    {
        $this->logInteraction($action, [
            'refer' => $refer,
            'subscription_id' => $subscriptionId,
        ]);

        $response = Http::asForm()->post($this->getEndpoint('recurring'), [
            'store_id'        => config('sslcommerz.store_id'),
            'store_passwd'    => config('sslcommerz.store_password'),
            'refer'           => $refer,
            'subscription_id' => $subscriptionId,
            'action'          => $action,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Get the encryption service instance.
     */
    public function getEncryptionService(): EncryptionService
    {
        return $this->encryption;
    }

    // ---------------------------------------------------------------
    //  Hash Verification
    // ---------------------------------------------------------------

    /**
     * {@inheritDoc}
     */
    public function verifyHash(array $data): bool
    {
        return $this->hashValidator->verify($data);
    }

    /**
     * Get the HashValidator instance.
     */
    public function getHashValidator(): HashValidator
    {
        return $this->hashValidator;
    }

    // ---------------------------------------------------------------
    //  Private Helpers
    // ---------------------------------------------------------------

    /**
     * Build the full API payload for payment initiation.
     */
    private function buildInitiationPayload(PaymentRequestDTO $request): array
    {
        $payload = $request->toApiPayload();

        // Add store credentials
        $payload['store_id']     = config('sslcommerz.store_id');
        $payload['store_passwd'] = config('sslcommerz.store_password');

        // Add callback URLs (use request overrides or config defaults)
        $baseUrl = rtrim(config('app.url', ''), '/');

        $payload['success_url'] = $request->successUrl
            ?? $baseUrl . config('sslcommerz.routes.success');

        $payload['fail_url'] = $request->failUrl
            ?? $baseUrl . config('sslcommerz.routes.fail');

        $payload['cancel_url'] = $request->cancelUrl
            ?? $baseUrl . config('sslcommerz.routes.cancel');

        $payload['ipn_url'] = $request->ipnUrl
            ?? $baseUrl . config('sslcommerz.routes.ipn');

        return $payload;
    }

    /**
     * Get the API endpoint URL for the given type.
     */
    private function getEndpoint(string $type): string
    {
        $environment = config('sslcommerz.sandbox', true) ? 'sandbox' : 'live';

        return config("sslcommerz.endpoints.{$environment}.{$type}");
    }

    /**
     * Log a gateway interaction if logging is enabled.
     */
    private function logInteraction(string $action, array $data = []): void
    {
        if (! config('sslcommerz.logging.enabled', true)) {
            return;
        }

        $channel = config('sslcommerz.logging.channel', 'stack');

        Log::channel($channel)->info("SSLCOMMERZ [{$action}]", $data);
    }
}
