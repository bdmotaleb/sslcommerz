<?php

namespace Sslcommerz\Laravel\DTOs;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Refund Response DTO
 */
final readonly class RefundResponseDTO implements ArrayAccess, JsonSerializable, Arrayable, Jsonable
{
    use ArrayableDTO;

    public function __construct(
        public string  $apiConnect,
        public ?string $bankTranId,
        public ?string $transId,
        public ?string $refundRefId,
        public string  $status,
        public ?string $errorReason,
        public array   $rawResponse = [],
    ) {
    }

    public static function fromApiResponse(array $r): self
    {
        return new self(
            apiConnect:  $r['APIConnect'] ?? 'FAILED',
            bankTranId:  $r['bank_tran_id'] ?? null,
            transId:     $r['trans_id'] ?? null,
            refundRefId: $r['refund_ref_id'] ?? null,
            status:      $r['status'] ?? 'failed',
            errorReason: $r['errorReason'] ?? null,
            rawResponse: $r,
        );
    }

    /**
     * Create a failed refund response DTO with an error message.
     */
    public static function failed(string $reason): self
    {
        return new self(
            apiConnect: 'FAILED',
            bankTranId: null,
            transId: null,
            refundRefId: null,
            status: 'failed',
            errorReason: $reason
        );
    }

    public function isSuccessful(): bool
    {
        return $this->apiConnect === 'DONE' && $this->status === 'success';
    }
}
