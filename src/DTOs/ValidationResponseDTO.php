<?php

namespace Sslcommerz\Laravel\DTOs;

/**
 * Validation Response DTO
 *
 * Wraps the response from the SSLCOMMERZ Order Validation API.
 */
final readonly class ValidationResponseDTO
{
    public function __construct(
        public string  $status,
        public ?string $tranDate,
        public ?string $tranId,
        public ?string $valId,
        public ?string $amount,
        public ?string $storeAmount,
        public ?string $currency,
        public ?string $bankTranId,
        public ?string $cardType,
        public ?string $cardNo,
        public ?string $cardIssuer,
        public ?string $cardBrand,
        public ?string $cardIssuerCountry,
        public ?string $cardIssuerCountryCode,
        public ?string $currencyType,
        public ?string $currencyAmount,
        public ?string $riskLevel,
        public ?string $riskTitle,
        public ?string $apiConnect,
        public ?string $validatedOn,
        public ?string $gwVersion,
        public array   $rawResponse = [],
    ) {
    }

    public static function fromApiResponse(array $r): self
    {
        return new self(
            status: $r['status'] ?? 'UNKNOWN',
            tranDate: $r['tran_date'] ?? null,
            tranId: $r['tran_id'] ?? null,
            valId: $r['val_id'] ?? null,
            amount: $r['amount'] ?? null,
            storeAmount: $r['store_amount'] ?? null,
            currency: $r['currency'] ?? null,
            bankTranId: $r['bank_tran_id'] ?? null,
            cardType: $r['card_type'] ?? null,
            cardNo: $r['card_no'] ?? null,
            cardIssuer: $r['card_issuer'] ?? null,
            cardBrand: $r['card_brand'] ?? null,
            cardIssuerCountry: $r['card_issuer_country'] ?? null,
            cardIssuerCountryCode: $r['card_issuer_country_code'] ?? null,
            currencyType: $r['currency_type'] ?? null,
            currencyAmount: $r['currency_amount'] ?? null,
            riskLevel: $r['risk_level'] ?? null,
            riskTitle: $r['risk_title'] ?? null,
            apiConnect: $r['APIConnect'] ?? null,
            validatedOn: $r['validated_on'] ?? null,
            gwVersion: $r['gw_version'] ?? null,
            rawResponse: $r,
        );
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, ['VALID', 'VALIDATED'], true);
    }

    public function isApiConnected(): bool
    {
        return $this->apiConnect === 'DONE';
    }

    public function isRisky(): bool
    {
        return $this->riskLevel === '1';
    }
}
