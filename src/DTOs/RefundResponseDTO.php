<?php

namespace Sslcommerz\Laravel\DTOs;

/**
 * Refund Response DTO
 */
final readonly class RefundResponseDTO
{
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

    public function isSuccessful(): bool
    {
        return $this->apiConnect === 'DONE' && $this->status === 'success';
    }
}
