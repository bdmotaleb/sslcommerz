<?php

namespace Sslcommerz\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Sslcommerz\Laravel\Contracts\PaymentGatewayInterface;
use Sslcommerz\Laravel\DTOs\CallbackDTO;
use Sslcommerz\Laravel\Events\IpnReceived;
use Sslcommerz\Laravel\Events\PaymentCancelled;
use Sslcommerz\Laravel\Events\PaymentFailed;
use Sslcommerz\Laravel\Events\PaymentSucceeded;

/**
 * Default SSLCOMMERZ Callback Controller
 * Handles payment callbacks from the SSLCOMMERZ gateway.
 * Users can override this by binding their own CallbackHandlerInterface
 * implementation or by publishing and modifying the routes.
 */
class SslcommerzCallbackController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    )
    {
    }

    /**
     * Handle successful payment callback.
     */
    public function success(Request $request)
    {
        $callback = CallbackDTO::fromRequest($request);

        $this->logCallback('success', $callback);


        // Validate the transaction with SSLCOMMERZ API
        $validation = null;
        if ($callback->valId) {
            try {
                $validation = $this->gateway->validate($callback->valId);
            } catch (\Exception $e) {
                Log::channel(config('sslcommerz.logging.channel', 'stack'))
                    ->error('SSLCOMMERZ validation failed in success callback', [
                        'tran_id' => $callback->tranId,
                        'error'   => $e->getMessage(),
                    ]);
            }
        }


        // Dispatch event
        if ($validation) {
            event(new PaymentSucceeded($callback, $validation));
        }

        // dd($callback); // Uncomment for debugging if needed

        return response()->json([
            'message' => 'Payment successful',
            'tran_id' => $callback->tranId,
            'amount'  => $callback->amount,
        ]);
    }

    /**
     * Handle failed payment callback.
     */
    public function fail(Request $request)
    {
        $callback = CallbackDTO::fromRequest($request);

        $this->logCallback('fail', $callback);


        event(new PaymentFailed($callback));

        return redirect('/payment/fail');
    }

    /**
     * Handle cancelled payment callback.
     */
    public function cancel(Request $request)
    {
        $callback = CallbackDTO::fromRequest($request);

        $this->logCallback('cancel', $callback);


        event(new PaymentCancelled($callback));

        return redirect('/payment/cancel');
    }

    /**
     * Handle IPN (Instant Payment Notification).
     */
    public function ipn(Request $request)
    {
        $callback = CallbackDTO::fromRequest($request);

        $this->logCallback('ipn', $callback);

        // Verify hash integrity
        if (!$this->gateway->verifyHash($request->all())) {
            Log::channel(config('sslcommerz.logging.channel', 'stack'))
                ->warning('SSLCOMMERZ IPN hash verification failed', [
                    'tran_id' => $callback->tranId,
                ]);

            return response('INVALID HASH', 403);
        }


        // Validate with SSLCOMMERZ API
        $validation = null;
        if ($callback->valId && $callback->isValid()) {
            try {
                $validation = $this->gateway->validate($callback->valId);
            } catch (\Exception $e) {
                Log::channel(config('sslcommerz.logging.channel', 'stack'))
                    ->error('SSLCOMMERZ validation failed in IPN', [
                        'tran_id' => $callback->tranId,
                        'error'   => $e->getMessage(),
                    ]);
            }
        }


        // Dispatch event
        event(new IpnReceived($callback, $validation));

        return response('IPN RECEIVED', 200);
    }

    /**
     * Log callback data if logging is enabled.
     */
    private function logCallback(string $type, CallbackDTO $callback): void
    {
        if (!config('sslcommerz.logging.enabled', true)) {
            return;
        }

        Log::channel(config('sslcommerz.logging.channel', 'stack'))
            ->info("SSLCOMMERZ callback [{$type}]", [
                'tran_id' => $callback->tranId,
                'status'  => $callback->status,
                'amount'  => $callback->amount,
            ]);
    }
}
