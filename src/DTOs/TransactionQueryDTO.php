<?php

namespace Sslcommerz\Laravel\DTOs;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Transaction Query DTO
 *
 * Wraps the response from querying transaction status by tran_id or sessionkey.
 */
final readonly class TransactionQueryDTO implements ArrayAccess, JsonSerializable, Arrayable, Jsonable
{
    use ArrayableDTO;

    public function __construct(
        public string $apiConnect,
        public int    $numberOfTransactions,
        public array  $elements,
        public array  $rawResponse = [],
    ) {
    }

    public static function fromApiResponse(array $r): self
    {
        return new self(
            apiConnect:           $r['APIConnect'] ?? 'FAILED',
            numberOfTransactions: (int) ($r['no_of_trans_found'] ?? 0),
            elements:             $r['element'] ?? [],
            rawResponse:          $r,
        );
    }

    public function isApiConnected(): bool
    {
        return $this->apiConnect === 'DONE';
    }

    public function hasTransactions(): bool
    {
        return $this->numberOfTransactions > 0;
    }

    /**
     * Get the latest successful transaction element (if any).
     */
    public function getLatestSuccessful(): ?array
    {
        foreach ($this->elements as $element) {
            if (in_array($element['status'] ?? '', ['VALID', 'VALIDATED'], true)) {
                return $element;
            }
        }
        return null;
    }
}
