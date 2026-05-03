<?php

namespace Sslcommerz\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Sslcommerz\Laravel\Events\IpnReceived;
use Sslcommerz\Laravel\Events\PaymentCancelled;
use Sslcommerz\Laravel\Events\PaymentFailed;
use Sslcommerz\Laravel\Events\PaymentSucceeded;
use Sslcommerz\Laravel\Tests\TestCase;

class CallbackRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    /** @test */
    public function success_route_processes_valid_payment(): void
    {
        // Mock the validation API call
        Http::fake([
            'sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response([
                'status'      => 'VALID',
                'tran_id'     => 'TXN_SUCCESS_001',
                'val_id'      => 'VAL_001',
                'amount'      => '100.00',
                'store_amount' => '97.00',
                'APIConnect'  => 'DONE',
            ]),
        ]);


        $response = $this->post(route('sslcommerz.success'), [
            'status'       => 'VALID',
            'tran_id'      => 'TXN_SUCCESS_001',
            'val_id'       => 'VAL_001',
            'amount'       => '100.00',
            'store_amount' => '97.00',
            'bank_tran_id' => 'BANK_001',
            'card_type'    => 'VISA',
        ]);

        $response->assertRedirect(config('sslcommerz.redirect.success'));


        Event::assertDispatched(PaymentSucceeded::class);
    }

    /** @test */
    public function fail_route_dispatches_event(): void
    {

        $response = $this->post(route('sslcommerz.fail'), [
            'status'  => 'FAILED',
            'tran_id' => 'TXN_FAIL_001',
        ]);

        $response->assertRedirect(config('sslcommerz.redirect.fail'));


        Event::assertDispatched(PaymentFailed::class);
    }

    /** @test */
    public function cancel_route_dispatches_event(): void
    {

        $response = $this->post(route('sslcommerz.cancel'), [
            'status'  => 'CANCELLED',
            'tran_id' => 'TXN_CANCEL_001',
        ]);

        $response->assertRedirect(config('sslcommerz.redirect.cancel'));


        Event::assertDispatched(PaymentCancelled::class);
    }

    /** @test */
    public function ipn_route_accepts_post_without_csrf(): void
    {
        Http::fake([
            'sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response([
                'status'     => 'VALID',
                'tran_id'    => 'TXN_IPN_001',
                'val_id'     => 'VAL_IPN_001',
                'amount'     => '200.00',
                'APIConnect' => 'DONE',
            ]),
        ]);


        // Build valid hash data
        $data = $this->buildIpnData('TXN_IPN_001', '200.00', 'VALID');

        $response = $this->post(route('sslcommerz.ipn'), $data);

        $response->assertStatus(200);

        Event::assertDispatched(IpnReceived::class);
    }

    /** @test */
    public function ipn_route_rejects_invalid_hash(): void
    {
        $response = $this->post(route('sslcommerz.ipn'), [
            'status'      => 'VALID',
            'tran_id'     => 'TXN_FAKE',
            'val_id'      => 'VAL_FAKE',
            'verify_sign' => 'invalid_hash',
            'verify_key'  => 'status,tran_id,val_id',
        ]);

        $response->assertStatus(403);

        Event::assertNotDispatched(IpnReceived::class);
    }


    // ---------------------------------------------------------------
    //  Helper
    // ---------------------------------------------------------------

    private function buildIpnData(string $tranId, string $amount, string $status): array
    {
        $storePassword = 'qwerty';
        $data = [
            'amount'   => $amount,
            'status'   => $status,
            'tran_id'  => $tranId,
            'val_id'   => 'VAL_IPN_001',
            'store_id' => 'testbox',
        ];

        $keys = array_keys($data);
        sort($keys);
        $hashString = '';
        foreach ($keys as $key) {
            $hashString .= $key . '=' . $data[$key] . '&';
        }
        $hashString .= 'store_passwd=' . md5($storePassword);

        $data['verify_key'] = implode(',', array_keys($data));
        $data['verify_sign'] = md5($hashString);

        return $data;
    }
}
