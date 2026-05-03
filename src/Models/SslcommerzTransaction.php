<?php

namespace Sslcommerz\Laravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * SSLCOMMERZ Transaction Model
 *
 * Tracks all payment transactions processed through the gateway.
 *
 * @property int         $id
 * @property string      $tran_id
 * @property string|null $session_key
 * @property string|null $val_id
 * @property string|null $bank_tran_id
 * @property float       $amount
 * @property float|null  $store_amount
 * @property string      $currency
 * @property string      $status
 * @property string|null $card_type
 * @property string|null $card_no
 * @property string|null $card_brand
 * @property string|null $card_issuer
 * @property int         $risk_level
 * @property string|null $gateway_page_url
 * @property array|null  $callback_payload
 * @property array|null  $validation_payload
 * @property string|null $value_a
 * @property string|null $value_b
 * @property string|null $value_c
 * @property string|null $value_d
 * @property \Carbon\Carbon|null $validated_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class SslcommerzTransaction extends Model
{
    protected $table = 'sslcommerz_transactions';

    protected $fillable = [
        'tran_id',
        'session_key',
        'val_id',
        'bank_tran_id',
        'amount',
        'store_amount',
        'currency',
        'status',
        'card_type',
        'card_no',
        'card_brand',
        'card_issuer',
        'risk_level',
        'gateway_page_url',
        'callback_payload',
        'validation_payload',
        'value_a',
        'value_b',
        'value_c',
        'value_d',
        'validated_at',
    ];

    protected $casts = [
        'amount'              => 'decimal:2',
        'store_amount'        => 'decimal:2',
        'risk_level'          => 'integer',
        'callback_payload'    => 'array',
        'validation_payload'  => 'array',
        'validated_at'        => 'datetime',
    ];

    // ---------------------------------------------------------------
    //  Scopes
    // ---------------------------------------------------------------

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereIn('status', ['VALID', 'VALIDATED']);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'FAILED');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'CANCELLED');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'INITIATED');
    }

    public function scopeRisky(Builder $query): Builder
    {
        return $query->where('risk_level', 1);
    }

    // ---------------------------------------------------------------
    //  Helpers
    // ---------------------------------------------------------------

    public function isSuccessful(): bool
    {
        return in_array($this->status, ['VALID', 'VALIDATED'], true);
    }

    public function isRisky(): bool
    {
        return $this->risk_level === 1;
    }

    public function isPending(): bool
    {
        return $this->status === 'INITIATED';
    }

    public function isAlreadyProcessed(): bool
    {
        return in_array($this->status, ['VALID', 'VALIDATED', 'FAILED', 'CANCELLED'], true);
    }
}
