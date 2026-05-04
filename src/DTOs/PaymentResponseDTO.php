<?php

namespace Sslcommerz\Laravel\DTOs;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Payment Response DTO
 *
 * Wraps the SSLCOMMERZ session initiation API response,
 * providing typed access to the gateway redirect URL and metadata.
 */
final readonly class PaymentResponseDTO implements ArrayAccess, JsonSerializable, Arrayable, Jsonable
{
    use ArrayableDTO;

    public function __construct(
        public string  $status,
        public ?string $sessionKey,
        public ?string $gatewayPageUrl,
        public ?string $redirectGatewayUrl,
        public ?string $directPaymentUrl,
        public ?string $storeBanner,
        public ?string $storeLogo,
        public ?string $failedReason,
        public ?string $subscriptionId = null,
        public ?string $subscriptionStatus = null,
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
            subscriptionId:     $response['subscription_id'] ?? null,
            subscriptionStatus: $response['subscription_status'] ?? null,
            gateways:           $response['desc'] ?? [],
            rawResponse:        $response,
        );
    }

    /**
     * Create a failed response DTO with an error message.
     */
    public static function failed(string $reason): self
    {
        return new self(
            status: 'FAILED',
            sessionKey: null,
            gatewayPageUrl: null,
            redirectGatewayUrl: null,
            directPaymentUrl: null,
            storeBanner: null,
            storeLogo: null,
            failedReason: $reason
        );
    }

    /**
     * Check if the payment session was created successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'SUCCESS' && ! empty($this->gatewayPageUrl);
    }

    /**
     * Get a redirect response to the gateway page.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception If the response was not successful
     */
    public function redirect()
    {
        if (! $this->isSuccessful()) {
            throw new \Exception("Cannot redirect: payment initiation failed. Reason: " . ($this->failedReason ?? 'Unknown'));
        }

        return redirect()->away($this->gatewayPageUrl);
    }
}
