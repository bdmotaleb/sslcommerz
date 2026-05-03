<?php

namespace Sslcommerz\Laravel\DTOs;

/**
 * Payment Response DTO
 *
 * Wraps the SSLCOMMERZ session initiation API response,
 * providing typed access to the gateway redirect URL and metadata.
 */
final readonly class PaymentResponseDTO
{
    public function __construct(
        public string  $status,
        public ?string $sessionKey,
        public ?string $gatewayPageUrl,
        public ?string $redirectGatewayUrl,
        public ?string $directPaymentUrl,
        public ?string $storeBanner,
        public ?string $storeLogo,
        public ?string $failedReason,
        public array   $gateways = [],
        public array   $rawResponse = [],
    ) {
    }

    /**
     * Create from the raw SSLCOMMERZ API JSON response.
     */
    public static function fromApiResponse(array $response): self
    {
        return new self(
            status:             $response['status'] ?? 'FAILED',
            sessionKey:         $response['sessionkey'] ?? null,
            gatewayPageUrl:     $response['GatewayPageURL'] ?? null,
            redirectGatewayUrl: $response['redirectGatewayURL'] ?? null,
            directPaymentUrl:   $response['directPaymentURL'] ?? null,
            storeBanner:        $response['storeBanner'] ?? null,
            storeLogo:          $response['storeLogo'] ?? null,
            failedReason:       $response['failedreason'] ?? null,
            gateways:           $response['desc'] ?? [],
            rawResponse:        $response,
        );
    }

    /**
     * Check if the payment session was created successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'SUCCESS' && ! empty($this->gatewayPageUrl);
    }
}
