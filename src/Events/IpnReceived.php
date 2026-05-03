<?php

namespace Sslcommerz\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Sslcommerz\Laravel\DTOs\CallbackDTO;
use Sslcommerz\Laravel\DTOs\ValidationResponseDTO;

class IpnReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CallbackDTO $payment,
        public readonly ?ValidationResponseDTO $validation = null,
    ) {
    }
}
