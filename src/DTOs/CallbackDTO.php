<?php

namespace Sslcommerz\Laravel\DTOs;

use Illuminate\Http\Request;

/**
 * Callback DTO
 *
 * Encapsulates all data received from SSLCOMMERZ via callback
 * (success, fail, cancel) or IPN POST notifications.
 */
final readonly class CallbackDTO
{
    public function __construct(
        public string  $status,
        public string  $tranId,
        public ?string $valId,
        public ?string $amount,
        public ?string $storeAmount,
        public ?string $cardType,
        public ?string $cardNo,
        public ?string $bankTranId,
        public ?string $currency,
        public ?string $tranDate,
        public ?string $cardIssuer,
        public ?string $cardBrand,
        public ?string $cardIssuerCountry,
        public ?string $cardIssuerCountryCode,
        public ?string $currencyType,
        public ?string $currencyAmount,
        public ?string $currencyRate,
        public ?string $baseFair,
        public ?string $verifySign,
        public ?string $verifyKey,
        public ?string $riskLevel,
        public ?string $riskTitle,
        public ?string $valueA,
        public ?string $valueB,
        public ?string $valueC,
        public ?string $valueD,
        public array   $rawData = [],
    ) {
    }

    /**
     * Create from a Laravel HTTP Request (callback or IPN).
     */
    public static function fromRequest(Request $request): self
    {
        $data = $request->all();

        return new self(
            status:               $data['status'] ?? 'UNKNOWN',
            tranId:               $data['tran_id'] ?? '',
            valId:                $data['val_id'] ?? null,
            amount:               $data['amount'] ?? null,
            storeAmount:          $data['store_amount'] ?? null,
            cardType:             $data['card_type'] ?? null,
            cardNo:               $data['card_no'] ?? null,
            bankTranId:           $data['bank_tran_id'] ?? null,
            currency:             $data['currency'] ?? null,
            tranDate:             $data['tran_date'] ?? null,
            cardIssuer:           $data['card_issuer'] ?? null,
            cardBrand:            $data['card_brand'] ?? null,
            cardIssuerCountry:    $data['card_issuer_country'] ?? null,
            cardIssuerCountryCode: $data['card_issuer_country_code'] ?? null,
            currencyType:         $data['currency_type'] ?? null,
            currencyAmount:       $data['currency_amount'] ?? null,
            currencyRate:         $data['currency_rate'] ?? null,
            baseFair:             $data['base_fair'] ?? null,
            verifySign:           $data['verify_sign'] ?? null,
            verifyKey:            $data['verify_key'] ?? null,
            riskLevel:            $data['risk_level'] ?? null,
            riskTitle:            $data['risk_title'] ?? null,
            valueA:               $data['value_a'] ?? null,
            valueB:               $data['value_b'] ?? null,
            valueC:               $data['value_c'] ?? null,
            valueD:               $data['value_d'] ?? null,
            rawData:              $data,
        );
    }

    /**
     * Check if the callback indicates a successful payment.
     */
    public function isValid(): bool
    {
        return in_array($this->status, ['VALID', 'VALIDATED'], true);
    }

    /**
     * Check if the payment is considered risky.
     */
    public function isRisky(): bool
    {
        return $this->riskLevel === '1';
    }
}
