<?php

namespace Sslcommerz\Laravel\Tests\Unit;

use Sslcommerz\Laravel\Exceptions\InvalidHashException;
use Sslcommerz\Laravel\Services\HashValidator;
use Sslcommerz\Laravel\Tests\TestCase;

class HashValidatorTest extends TestCase
{
    private HashValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new HashValidator('qwerty');
    }

    /** @test */
    public function it_validates_correct_hash(): void
    {
        $data = $this->buildSignedData([
            'amount'   => '100.00',
            'status'   => 'VALID',
            'tran_id'  => 'TEST_001',
            'store_id' => 'testbox',
        ]);

        $this->assertTrue($this->validator->verify($data));
    }

    /** @test */
    public function it_rejects_tampered_data(): void
    {
        $data = $this->buildSignedData([
            'amount'   => '100.00',
            'status'   => 'VALID',
            'tran_id'  => 'TEST_001',
        ]);

        // Tamper with the amount
        $data['amount'] = '999.00';

        $this->assertFalse($this->validator->verify($data));
    }

    /** @test */
    public function it_rejects_invalid_verify_sign(): void
    {
        $data = [
            'amount'      => '100.00',
            'status'      => 'VALID',
            'tran_id'     => 'TEST_001',
            'verify_sign' => 'completely_wrong_hash',
            'verify_key'  => 'amount,status,tran_id',
        ];

        $this->assertFalse($this->validator->verify($data));
    }

    /** @test */
    public function it_returns_false_when_verify_sign_missing(): void
    {
        $data = [
            'amount'     => '100.00',
            'status'     => 'VALID',
            'verify_key' => 'amount,status',
        ];

        $this->assertFalse($this->validator->verify($data));
    }

    /** @test */
    public function it_returns_false_when_verify_key_missing(): void
    {
        $data = [
            'amount'      => '100.00',
            'verify_sign' => 'some_hash',
        ];

        $this->assertFalse($this->validator->verify($data));
    }

    /** @test */
    public function verify_or_fail_throws_on_invalid_hash(): void
    {
        $this->expectException(InvalidHashException::class);

        $this->validator->verifyOrFail([
            'verify_sign' => 'bad',
            'verify_key'  => 'amount',
            'amount'      => '100',
        ]);
    }

    /** @test */
    public function it_handles_many_fields_correctly(): void
    {
        $data = $this->buildSignedData([
            'amount'       => '100.00',
            'bank_tran_id' => 'BANK001',
            'card_brand'   => 'VISA',
            'card_type'    => 'VISA-Brac bank',
            'currency'     => 'BDT',
            'status'       => 'VALID',
            'store_id'     => 'testbox',
            'tran_id'      => 'TEST_001',
            'val_id'       => 'VAL001',
        ]);

        $this->assertTrue($this->validator->verify($data));
    }

    // ---------------------------------------------------------------
    //  Helper
    // ---------------------------------------------------------------

    private function buildSignedData(array $fields): array
    {
        $keys = array_keys($fields);
        sort($keys);

        $hashString = '';
        foreach ($keys as $key) {
            $hashString .= $key . '=' . $fields[$key] . '&';
        }
        $hashString .= 'store_passwd=' . md5('qwerty');

        $fields['verify_key'] = implode(',', array_keys($fields));
        $fields['verify_sign'] = md5($hashString);

        return $fields;
    }
}
