<?php

namespace Sslcommerz\Laravel\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Sslcommerz\Laravel\Contracts\PaymentGatewayInterface;
use Sslcommerz\Laravel\DTOs\PaymentRequestDTO;
use Sslcommerz\Laravel\DTOs\RefundRequestDTO;
use Sslcommerz\Laravel\Exceptions\PaymentInitiationException;
use Sslcommerz\Laravel\Services\SslcommerzService;
use Sslcommerz\Laravel\Tests\TestCase;

class SslcommerzServiceTest extends TestCase
{
    private SslcommerzService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(PaymentGatewayInterface::class);
    }

    /** @test */
    public function it_resolves_from_container(): void
    {
        $this->assertInstanceOf(SslcommerzService::class, $this->service);
    }

    /** @test */
    public function it_can_initiate_payment_successfully(): void
    {
        Http::fake([
            'sandbox.sslcommerz.com/*' => Http::response([
                'status'             => 'SUCCESS',
                'sessionkey'         => 'ABC123SESSION',
                'GatewayPageURL'     => 'https://sandbox.sslcommerz.com/gwprocess/v4/gw.php?Q=PAY&SESSIONKEY=ABC123SESSION',
                'redirectGatewayURL' => 'https://sandbox.sslcommerz.com/gwprocess/v4/bankgw/indexhtml.php',
                'failedreason'       => '',
                'storeBanner'        => 'https://sandbox.sslcommerz.com/banner.png',
                'storeLogo'          => 'https://sandbox.sslcommerz.com/logo.png',
                'desc'               => [],
            ], 200),
        ]);

        $request = $this->makePaymentRequest();
        $response = $this->service->initiate($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('SUCCESS', $response->status);
        $this->assertEquals('ABC123SESSION', $response->sessionKey);
        $this->assertNotEmpty($response->gatewayPageUrl);
    }

    /** @test */
    public function it_throws_exception_on_initiation_failure(): void
    {
        Http::fake([
            'sandbox.sslcommerz.com/*' => Http::response([
                'status'       => 'FAILED',
                'failedreason' => 'Invalid Store ID',
            ], 200),
        ]);

        $this->expectException(PaymentInitiationException::class);
        $this->expectExceptionMessage('Invalid Store ID');

        $this->service->initiate($this->makePaymentRequest());
    }

    /** @test */
    public function it_throws_exception_on_connection_failure(): void
    {
        Http::fake([
            'sandbox.sslcommerz.com/*' => Http::response('Server Error', 500),
        ]);

        $this->expectException(PaymentInitiationException::class);

        $this->service->initiate($this->makePaymentRequest());
    }

    /** @test */
    public function it_can_validate_transaction(): void
    {
        Http::fake([
            'sandbox.sslcommerz.com/validator/*' => Http::response([
                'status'      => 'VALID',
                'tran_id'     => 'TEST_TXN_001',
                'val_id'      => 'VAL123',
                'amount'      => '100.00',
                'store_amount' => '97.00',
                'currency'    => 'BDT',
                'bank_tran_id' => 'BANK_TXN_001',
                'card_type'   => 'VISA-Brac bank',
                'risk_level'  => '0',
                'risk_title'  => 'Safe',
                'APIConnect'  => 'DONE',
            ], 200),
        ]);

        $result = $this->service->validate('VAL123');

        $this->assertTrue($result->isSuccessful());
        $this->assertTrue($result->isApiConnected());
        $this->assertFalse($result->isRisky());
        $this->assertEquals('VALID', $result->status);
        $this->assertEquals('100.00', $result->amount);
    }

    /** @test */
    public function it_can_query_transaction(): void
    {
        Http::fake([
            'sandbox.sslcommerz.com/validator/*' => Http::response([
                'APIConnect'        => 'DONE',
                'no_of_trans_found' => 1,
                'element'           => [
                    [
                        'status'    => 'VALID',
                        'tran_id'   => 'TEST_TXN_001',
                        'val_id'    => 'VAL123',
                        'amount'    => '100.00',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->queryTransaction('TEST_TXN_001');

        $this->assertTrue($result->isApiConnected());
        $this->assertTrue($result->hasTransactions());
        $this->assertEquals(1, $result->numberOfTransactions);
        $this->assertNotNull($result->getLatestSuccessful());
    }

    /** @test */
    public function it_can_initiate_refund(): void
    {
        Http::fake([
            'sandbox.sslcommerz.com/validator/*' => Http::response([
                'APIConnect'    => 'DONE',
                'bank_tran_id'  => 'BANK_TXN_001',
                'trans_id'      => 'TEST_TXN_001',
                'refund_ref_id' => 'REF_001',
                'status'        => 'success',
                'errorReason'   => '',
            ], 200),
        ]);

        $request = RefundRequestDTO::fromArray([
            'bank_tran_id'  => 'BANK_TXN_001',
            'refund_amount' => 50.00,
            'refund_remarks' => 'Customer requested refund',
        ]);

        $result = $this->service->refund($request);

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('REF_001', $result->refundRefId);
    }

    /** @test */
    public function it_can_verify_valid_hash(): void
    {
        $storePassword = 'qwerty';
        $data = [
            'amount'       => '100.00',
            'status'       => 'VALID',
            'tran_id'      => 'TEST_001',
            'store_id'     => 'testbox',
            'verify_key'   => 'amount,status,store_id,tran_id',
        ];

        // Build expected hash
        $fields = explode(',', $data['verify_key']);
        sort($fields);
        $hashString = '';
        foreach ($fields as $field) {
            $hashString .= $field . '=' . ($data[$field] ?? '') . '&';
        }
        $hashString .= 'store_passwd=' . md5($storePassword);
        $data['verify_sign'] = md5($hashString);

        $this->assertTrue($this->service->verifyHash($data));
    }

    /** @test */
    public function it_rejects_invalid_hash(): void
    {
        $data = [
            'amount'      => '100.00',
            'status'      => 'VALID',
            'tran_id'     => 'TEST_001',
            'verify_sign' => 'invalid_hash_value',
            'verify_key'  => 'amount,status,tran_id',
        ];

        $this->assertFalse($this->service->verifyHash($data));
    }

    // ---------------------------------------------------------------
    //  Helpers
    // ---------------------------------------------------------------

    private function makePaymentRequest(): PaymentRequestDTO
    {
        return PaymentRequestDTO::fromArray([
            'tran_id'      => 'TEST_TXN_001',
            'total_amount' => 100.00,
            'currency'     => 'BDT',
            'cus_name'     => 'Test Customer',
            'cus_email'    => 'test@example.com',
            'cus_phone'    => '01711111111',
            'cus_add1'     => 'Dhaka',
            'cus_city'     => 'Dhaka',
            'cus_postcode' => '1000',
            'cus_country'  => 'Bangladesh',
            'product_name' => 'Test Product',
        ]);
    }
}
