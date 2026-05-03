<?php

namespace Sslcommerz\Laravel\DTOs;

use InvalidArgumentException;

/**
 * Refund Request DTO
 */
final readonly class RefundRequestDTO
{
    public function __construct(
        public string  $bankTranId,
        public float   $refundAmount,
        public string  $refundRemarks,
        public ?string $refeId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        foreach (['bank_tran_id', 'refund_amount', 'refund_remarks'] as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("The field [{$field}] is required for refund.");
            }
        }

        return new self(
            bankTranId:    $data['bank_tran_id'],
            refundAmount:  (float) $data['refund_amount'],
            refundRemarks: $data['refund_remarks'],
            refeId:        $data['refe_id'] ?? null,
        );
    }

    public function toApiPayload(): array
    {
        $payload = [
            'bank_tran_id'  => $this->bankTranId,
            'refund_amount' => $this->refundAmount,
            'refund_remarks' => $this->refundRemarks,
        ];

        if ($this->refeId !== null) {
            $payload['refe_id'] = $this->refeId;
        }

        return $payload;
    }
}
