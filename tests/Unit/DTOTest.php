<?php

namespace Sslcommerz\Laravel\Tests\Unit;

use InvalidArgumentException;
use Sslcommerz\Laravel\DTOs\CallbackDTO;
use Sslcommerz\Laravel\DTOs\PaymentRequestDTO;
use Sslcommerz\Laravel\DTOs\PaymentResponseDTO;
use Sslcommerz\Laravel\DTOs\RefundRequestDTO;
use Sslcommerz\Laravel\DTOs\RefundResponseDTO;
use Sslcommerz\Laravel\DTOs\TransactionQueryDTO;
use Sslcommerz\Laravel\DTOs\ValidationResponseDTO;
use Sslcommerz\Laravel\Tests\TestCase;

class DTOTest extends TestCase
{
    /** @test */
    public function payment_request_dto_from_array(): void
    {
        $dto = PaymentRequestDTO::fromArray([
            'tran_id'      => 'TXN001',
            'total_amount' => 500,
            'cus_name'     => 'John Doe',
            'cus_email'    => 'john@example.com',
            'cus_phone'    => '01711111111',
            'cus_add1'     => 'Dhaka',
            'cus_city'     => 'Dhaka',
            'cus_postcode' => '1000',
            'cus_country'  => 'Bangladesh',
            'product_name' => 'Widget',
            'value_a'      => 'order_123',
        ]);

        $this->assertEquals('TXN001', $dto->tranId);
        $this->assertEquals(500.0, $dto->totalAmount);
        $this->assertEquals('order_123', $dto->valueA);
        $this->assertNull($dto->valueB);
    }

    /** @test */
    public function payment_request_dto_throws_on_missing_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cus_name');

        PaymentRequestDTO::fromArray(['total_amount' => 100]);
    }

    /** @test */
    public function payment_request_dto_auto_generates_tran_id(): void
    {
        $dto = PaymentRequestDTO::fromArray([
            'total_amount' => 100,
            'cus_name'     => 'John',
            'cus_email'    => 'john@example.com',
            'cus_phone'    => '01700000000',
            'cus_add1'     => 'Dhaka',
            'cus_city'     => 'Dhaka',
            'cus_postcode' => '1200',
        ]);

        $this->assertNotNull($dto->tranId);
        $this->assertStringStartsWith('SSL', $dto->tranId);
        $this->assertEquals('Bangladesh', $dto->cusCountry);
        $this->assertEquals('Payment', $dto->productName);
    }

    /** @test */
    public function payment_request_dto_to_api_payload(): void
    {
        $dto = PaymentRequestDTO::fromArray([
            'tran_id'      => 'TXN001',
            'total_amount' => 100,
            'cus_name'     => 'Jane',
            'cus_email'    => 'jane@example.com',
            'cus_phone'    => '01700000000',
            'cus_add1'     => 'Chittagong',
            'cus_city'     => 'Chittagong',
            'cus_postcode' => '4000',
            'cus_country'  => 'Bangladesh',
            'product_name' => 'Service',
        ]);

        $payload = $dto->toApiPayload();

        $this->assertEquals('TXN001', $payload['tran_id']);
        $this->assertEquals(100.0, $payload['total_amount']);
        $this->assertArrayNotHasKey('value_a', $payload); // null values excluded
    }

    /** @test */
    public function payment_request_dto_supports_v4_specialized_profiles(): void
    {
        $dto = PaymentRequestDTO::fromArray([
            'tran_id'      => 'TXN001',
            'total_amount' => 100,
            'cus_name'     => 'Jane',
            'cus_email'    => 'jane@example.com',
            'cus_phone'    => '01700000000',
            'cus_add1'     => 'Dhaka',
            'cus_city'     => 'Dhaka',
            'cus_postcode' => '1000',
            'cus_country'  => 'Bangladesh',
            'product_name' => 'Tickets',
            // v4 specialized fields
            'pnr' => 'PNR123',
            'hours_till_departure' => '24',
            'hotel_name' => 'Grand Hotel',
            'topup_number' => '01700000000',
            'logistic_pickup_id' => 'PICKUP99',
        ]);

        $payload = $dto->toApiPayload();

        $this->assertEquals('PNR123', $payload['pnr']);
        $this->assertEquals('24', $payload['hours_till_departure']);
        $this->assertEquals('Grand Hotel', $payload['hotel_name']);
        $this->assertEquals('01700000000', $payload['topup_number']);
        $this->assertEquals('PICKUP99', $payload['logistic_pickup_id']);
    }

    /** @test */
    public function payment_response_dto_success(): void
    {
        $dto = PaymentResponseDTO::fromApiResponse([
            'status'         => 'SUCCESS',
            'sessionkey'     => 'SESS123',
            'GatewayPageURL' => 'https://example.com/pay',
        ]);

        $this->assertTrue($dto->isSuccessful());
        $this->assertEquals('SESS123', $dto->sessionKey);
    }

    /** @test */
    public function payment_response_dto_failure(): void
    {
        $dto = PaymentResponseDTO::fromApiResponse([
            'status'       => 'FAILED',
            'failedreason' => 'Invalid credentials',
        ]);

        $this->assertFalse($dto->isSuccessful());
        $this->assertEquals('Invalid credentials', $dto->failedReason);
    }

    /** @test */
    public function validation_response_dto(): void
    {
        $dto = ValidationResponseDTO::fromApiResponse([
            'status'     => 'VALID',
            'amount'     => '100.00',
            'risk_level' => '0',
            'APIConnect' => 'DONE',
        ]);

        $this->assertTrue($dto->isSuccessful());
        $this->assertTrue($dto->isApiConnected());
        $this->assertFalse($dto->isRisky());
    }

    /** @test */
    public function refund_request_dto_throws_on_missing_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RefundRequestDTO::fromArray(['refund_amount' => 50]);
    }

    /** @test */
    public function refund_response_dto(): void
    {
        $dto = RefundResponseDTO::fromApiResponse([
            'APIConnect'    => 'DONE',
            'status'        => 'success',
            'refund_ref_id' => 'REF001',
        ]);

        $this->assertTrue($dto->isSuccessful());
    }

    /** @test */
    public function transaction_query_dto(): void
    {
        $dto = TransactionQueryDTO::fromApiResponse([
            'APIConnect'        => 'DONE',
            'no_of_trans_found' => 2,
            'element'           => [
                ['status' => 'FAILED', 'tran_id' => 'T1'],
                ['status' => 'VALID', 'tran_id' => 'T1'],
            ],
        ]);

        $this->assertTrue($dto->isApiConnected());
        $this->assertEquals(2, $dto->numberOfTransactions);
        $this->assertNotNull($dto->getLatestSuccessful());
        $this->assertEquals('VALID', $dto->getLatestSuccessful()['status']);
    }
}
