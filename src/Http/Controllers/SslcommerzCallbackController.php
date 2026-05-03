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
use Sslcommerz\Laravel\Models\SslcommerzTransaction;

/**
 * Default SSLCOMMERZ Callback Controller
 *
 * Handles payment callbacks from the SSLCOMMERZ gateway.
 * Users can override this by binding their own CallbackHandlerInterface
 * implementation or by publishing and modifying the routes.
 */
class SslcommerzCallbackController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {
    }

    /**
     * Handle successful payment callback.
     */
    public function success(Request $request)
    {
        $callback = CallbackDTO::fromRequest($request);

        $this->logCallback('success', $callback);

        // Find the transaction record
        $transaction = SslcommerzTransaction::where('tran_id', $callback->tranId)->first();

        // Prevent duplicate processing
        if ($transaction && $transaction->isAlreadyProcessed()) {
            return redirect(config('sslcommerz.redirect.success'))
                ->with('sslcommerz_tran_id', $callback->tranId)
                ->with('sslcommerz_message', 'Transaction already processed.');
        }

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

        // Update transaction record
        if ($transaction) {
            $transaction->update([
                'status'              => $validation?->status ?? $callback->status,
                'val_id'              => $callback->valId,
                'bank_tran_id'        => $callback->bankTranId,
                'store_amount'        => $callback->storeAmount,
                'card_type'           => $callback->cardType,
                'card_no'             => $callback->cardNo,
                'card_brand'          => $callback->cardBrand,
                'card_issuer'         => $callback->cardIssuer,
                'risk_level'          => (int) ($callback->riskLevel ?? 0),
                'callback_payload'    => $callback->rawData,
                'validation_payload'  => $validation?->rawResponse,
                'validated_at'        => $validation ? now() : null,
            ]);
        }

        // Dispatch event
        if ($validation) {
            event(new PaymentSucceeded($callback, $validation));
        }

        return redirect(config('sslcommerz.redirect.success'))
            ->with('sslcommerz_tran_id', $callback->tranId)
            ->with('sslcommerz_status', $validation?->status ?? $callback->status);
    }

    /**
     * Handle failed payment callback.
     */
    public function fail(Request $request)
    {
        $callback = CallbackDTO::fromRequest($request);

        $this->logCallback('fail', $callback);

        $transaction = SslcommerzTransaction::where('tran_id', $callback->tranId)->first();

        if ($transaction && ! $transaction->isAlreadyProcessed()) {
            $transaction->update([
                'status'           => 'FAILED',
                'bank_tran_id'     => $callback->bankTranId,
                'callback_payload' => $callback->rawData,
            ]);
        }

        event(new PaymentFailed($callback));

        return redirect(config('sslcommerz.redirect.fail'))
            ->with('sslcommerz_tran_id', $callback->tranId)
            ->with('sslcommerz_status', 'FAILED');
    }

    /**
     * Handle cancelled payment callback.
     */
    public function cancel(Request $request)
    {
        $callback = CallbackDTO::fromRequest($request);

        $this->logCallback('cancel', $callback);

        $transaction = SslcommerzTransaction::where('tran_id', $callback->tranId)->first();

        if ($transaction && ! $transaction->isAlreadyProcessed()) {
            $transaction->update([
                'status'           => 'CANCELLED',
                'callback_payload' => $callback->rawData,
            ]);
        }

        event(new PaymentCancelled($callback));

        return redirect(config('sslcommerz.redirect.cancel'))
            ->with('sslcommerz_tran_id', $callback->tranId)
            ->with('sslcommerz_status', 'CANCELLED');
    }

    /**
     * Handle IPN (Instant Payment Notification).
     */
    public function ipn(Request $request)
    {
        $callback = CallbackDTO::fromRequest($request);

        $this->logCallback('ipn', $callback);

        // Verify hash integrity
        if (! $this->gateway->verifyHash($request->all())) {
            Log::channel(config('sslcommerz.logging.channel', 'stack'))
                ->warning('SSLCOMMERZ IPN hash verification failed', [
                    'tran_id' => $callback->tranId,
                ]);

            return response('INVALID HASH', 403);
        }

        $transaction = SslcommerzTransaction::where('tran_id', $callback->tranId)->first();

        // Prevent duplicate processing
        if ($transaction && $transaction->isAlreadyProcessed()) {
            return response('ALREADY PROCESSED', 200);
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

        // Update transaction record
        if ($transaction) {
            $updateData = [
                'status'           => $validation?->status ?? $callback->status,
                'val_id'           => $callback->valId,
                'bank_tran_id'     => $callback->bankTranId,
                'store_amount'     => $callback->storeAmount,
                'card_type'        => $callback->cardType,
                'card_no'          => $callback->cardNo,
                'card_brand'       => $callback->cardBrand,
                'card_issuer'      => $callback->cardIssuer,
                'risk_level'       => (int) ($callback->riskLevel ?? 0),
                'callback_payload' => $callback->rawData,
            ];

            if ($validation) {
                $updateData['validation_payload'] = $validation->rawResponse;
                $updateData['validated_at'] = now();
            }

            $transaction->update($updateData);
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
        if (! config('sslcommerz.logging.enabled', true)) {
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
