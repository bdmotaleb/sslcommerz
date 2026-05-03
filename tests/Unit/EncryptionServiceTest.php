<?php

namespace Sslcommerz\Laravel\Tests\Unit;

use Sslcommerz\Laravel\Services\EncryptionService;
use Sslcommerz\Laravel\Tests\TestCase;

class EncryptionServiceTest extends TestCase
{
    private string $saltKey = '898c8b19e6cef9fe954b7a557458715e'; // Example from PDF

    /** @test */
    public function it_can_encrypt_and_decrypt_data(): void
    {
        $service = new EncryptionService($this->saltKey);
        $data = json_encode(['refer' => 'REF1234', 'type' => 'daily']);

        $encrypted = $service->encrypt($data);
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($data, $encrypted);

        $decrypted = $service->decrypt($encrypted);
        $this->assertEquals($data, $decrypted);
    }

    /** @test */
    public function it_returns_false_on_invalid_decryption(): void
    {
        $service = new EncryptionService($this->saltKey);
        
        $this->assertFalse($service->decrypt('invalid_base64'));
        $this->assertFalse($service->decrypt(base64_encode('no_separator')));
    }
}
