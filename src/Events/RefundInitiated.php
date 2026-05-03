<?php

namespace Sslcommerz\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Sslcommerz\Laravel\DTOs\RefundResponseDTO;

class RefundInitiated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly RefundResponseDTO $refund,
    ) {
    }
}
