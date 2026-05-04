<?php

namespace Sslcommerz\Laravel\DTOs;

use InvalidArgumentException;

/**
 * Payment Request DTO
 *
 * Encapsulates all parameters required to initiate a payment
 * session with the SSLCOMMERZ gateway.
 */
final readonly class PaymentRequestDTO
{
    /**
     * @param string      $tranId          Unique transaction ID from your system
     * @param float       $totalAmount     Total amount to charge
     * @param string      $currency        Currency code (BDT, USD, etc.)
     * @param string      $cusName         Customer name
     * @param string      $cusEmail        Customer email
     * @param string      $cusPhone        Customer phone number
     * @param string      $cusAdd1         Customer address line 1
     * @param string      $cusCity         Customer city
     * @param string      $cusPostcode     Customer postcode
     * @param string      $cusCountry      Customer country
     * @param string      $productName     Product name
     * @param string      $productCategory Product category
     * @param string      $productProfile  Product profile (general, physical-goods, etc.)
     * @param string|null $cusAdd2         Customer address line 2
     * @param string|null $cusState        Customer state
     * @param string|null $cusFax          Customer fax
     * @param string      $shippingMethod  Shipping method (YES, NO, Courier, etc.)
     * @param int         $numOfItem       Number of items
     * @param string|null $shipName        Shipping recipient name
     * @param string|null $shipAdd1        Shipping address line 1
     * @param string|null $shipAdd2        Shipping address line 2
     * @param string|null $shipCity        Shipping city
     * @param string|null $shipState       Shipping state
     * @param string|null $shipPostcode    Shipping postcode
     * @param string|null $shipCountry     Shipping country
     * @param string|null $multiCardName   Comma-separated card names to display
     * @param string|null $allowedBin      Comma-separated allowed BIN numbers
     * @param int         $emiOption       EMI option (0 = disabled, 1 = enabled)
     * @param int|null    $emiMaxInstOption EMI max installment option
     * @param int|null    $emiSelectedInst EMI selected installment
     * @param int         $emiAllowOnly    Allow only EMI (0 = no, 1 = yes)
     * @param string|null $valueA          Custom value A (reference)
     * @param string|null $valueB          Custom value B (reference)
     * @param string|null $valueC          Custom value C (reference)
     * @param string|null $valueD          Custom value D (reference)
     * @param string|null $cart            JSON encoded cart data
     * @param float|null  $productAmount   Product subtotal amount
     * @param float|null  $vat             VAT amount
     * @param float|null  $discountAmount  Discount amount
     * @param float|null  $convenienceFee  Convenience fee
     * @param string|null $successUrl      Override success callback URL
     * @param string|null $failUrl         Override fail callback URL
     * @param string|null $cancelUrl       Override cancel callback URL
     * @param string|null $ipnUrl          Override IPN callback URL
     * @param string|null $hoursTillDeparture Airline: Hours till departure
     * @param string|null $flightType      Airline: Flight type
     * @param string|null $pnr             Airline: PNR
     * @param string|null $journeyFromTo   Airline: Journey from-to
     * @param string|null $thirdPartyBooking Airline: Third party booking
     * @param string|null $hotelName       Travel: Hotel name
     * @param string|null $lengthOfStay    Travel: Length of stay
     * @param string|null $checkInTime     Travel: Check-in time
     * @param string|null $hotelCity       Travel: Hotel city
     * @param string|null $productType     Telecom: Product type
     * @param string|null $topupNumber     Telecom: Top-up number
     * @param string|null $countryTopup    Telecom: Country top-up
     * @param string|null $logisticPickupId Logistics: Pickup ID
     * @param string|null $logisticDeliveryType Logistics: Delivery type
     * @param string|null $schedule        Recurring: Encrypted JSON schedule data
     */
    public function __construct(
        public string  $tranId,
        public float   $totalAmount,
        public string  $currency,
        public ?string $cusName = null,
        public ?string $cusEmail = null,
        public ?string $cusPhone = null,
        public ?string $cusAdd1 = null,
        public ?string $cusCity = null,
        public ?string $cusPostcode = null,
        public ?string $cusCountry = 'Bangladesh',
        public ?string $productName = 'Payment',
        public string  $productCategory = 'general',
        public string  $productProfile = 'general',
        public ?string $cusAdd2 = null,
        public ?string $cusState = null,
        public ?string $cusFax = null,
        public string  $shippingMethod = 'NO',
        public int     $numOfItem = 1,
        public ?string $shipName = null,
        public ?string $shipAdd1 = null,
        public ?string $shipAdd2 = null,
        public ?string $shipCity = null,
        public ?string $shipState = null,
        public ?string $shipPostcode = null,
        public ?string $shipCountry = null,
        public ?string $multiCardName = null,
        public ?string $allowedBin = null,
        public int     $emiOption = 0,
        public ?int    $emiMaxInstOption = null,
        public ?int    $emiSelectedInst = null,
        public int     $emiAllowOnly = 0,
        public ?string $valueA = null,
        public ?string $valueB = null,
        public ?string $valueC = null,
        public ?string $valueD = null,
        public ?string $cart = null,
        public ?float  $productAmount = null,
        public ?float  $vat = null,
        public ?float  $discountAmount = null,
        public ?float  $convenienceFee = null,
        public ?string $successUrl = null,
        public ?string $failUrl = null,
        public ?string $cancelUrl = null,
        public ?string $ipnUrl = null,
        public ?string $hoursTillDeparture = null,
        public ?string $flightType = null,
        public ?string $pnr = null,
        public ?string $journeyFromTo = null,
        public ?string $thirdPartyBooking = null,
        public ?string $hotelName = null,
        public ?string $lengthOfStay = null,
        public ?string $checkInTime = null,
        public ?string $hotelCity = null,
        public ?string $productType = null,
        public ?string $topupNumber = null,
        public ?string $countryTopup = null,
        public ?string $logisticPickupId = null,
        public ?string $logisticDeliveryType = null,
        public ?string $schedule = null,
    ) {
    }

    /**
     * Create a PaymentRequestDTO from an associative array.
     *
     * @param array $data
     * @return self
     *
     * @throws InvalidArgumentException If required fields are missing
     */
    public static function fromArray(array $data): self
    {
        // Define minimum required fields for a basic transaction
        $required = ['total_amount', 'cus_name'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("The field [{$field}] is required for payment initiation.");
            }
        }

        return new self(
            tranId:           $data['tran_id'] ?? uniqid('SSL'),
            totalAmount:      (float) $data['total_amount'],
            currency:         $data['currency'] ?? config('sslcommerz.currency', 'BDT'),
            cusName:          $data['cus_name'] ?? null,
            cusEmail:         $data['cus_email'] ?? null,
            cusPhone:         $data['cus_phone'] ?? null,
            cusAdd1:          $data['cus_add1'] ?? null,
            cusCity:          $data['cus_city'] ?? null,
            cusPostcode:      $data['cus_postcode'] ?? null,
            cusCountry:       $data['cus_country'] ?? 'Bangladesh',
            productName:      $data['product_name'] ?? 'Payment',
            productCategory:  $data['product_category'] ?? config('sslcommerz.product.category', 'general'),
            productProfile:   $data['product_profile'] ?? config('sslcommerz.product.profile', 'general'),
            cusAdd2:          $data['cus_add2'] ?? null,
            cusState:         $data['cus_state'] ?? null,
            cusFax:           $data['cus_fax'] ?? null,
            shippingMethod:   $data['shipping_method'] ?? 'NO',
            numOfItem:        (int) ($data['num_of_item'] ?? 1),
            shipName:         $data['ship_name'] ?? null,
            shipAdd1:         $data['ship_add1'] ?? null,
            shipAdd2:         $data['ship_add2'] ?? null,
            shipCity:         $data['ship_city'] ?? null,
            shipState:        $data['ship_state'] ?? null,
            shipPostcode:     $data['ship_postcode'] ?? null,
            shipCountry:      $data['ship_country'] ?? null,
            multiCardName:    $data['multi_card_name'] ?? null,
            allowedBin:       $data['allowed_bin'] ?? null,
            emiOption:        (int) ($data['emi_option'] ?? 0),
            emiMaxInstOption: isset($data['emi_max_inst_option']) ? (int) $data['emi_max_inst_option'] : null,
            emiSelectedInst:  isset($data['emi_selected_inst']) ? (int) $data['emi_selected_inst'] : null,
            emiAllowOnly:     (int) ($data['emi_allow_only'] ?? 0),
            valueA:           $data['value_a'] ?? null,
            valueB:           $data['value_b'] ?? null,
            valueC:           $data['value_c'] ?? null,
            valueD:           $data['value_d'] ?? null,
            cart:             $data['cart'] ?? null,
            productAmount:    isset($data['product_amount']) ? (float) $data['product_amount'] : null,
            vat:              isset($data['vat']) ? (float) $data['vat'] : null,
            discountAmount:   isset($data['discount_amount']) ? (float) $data['discount_amount'] : null,
            convenienceFee:   isset($data['convenience_fee']) ? (float) $data['convenience_fee'] : null,
            successUrl:       $data['success_url'] ?? null,
            failUrl:          $data['fail_url'] ?? null,
            cancelUrl:        $data['cancel_url'] ?? null,
            ipnUrl:           $data['ipn_url'] ?? null,
            hoursTillDeparture: $data['hours_till_departure'] ?? null,
            flightType:       $data['flight_type'] ?? null,
            pnr:              $data['pnr'] ?? null,
            journeyFromTo:    $data['journey_from_to'] ?? null,
            thirdPartyBooking: $data['third_party_booking'] ?? null,
            hotelName:        $data['hotel_name'] ?? null,
            lengthOfStay:     $data['length_of_stay'] ?? null,
            checkInTime:      $data['check_in_time'] ?? null,
            hotelCity:        $data['hotel_city'] ?? null,
            productType:      $data['product_type'] ?? null,
            topupNumber:      $data['topup_number'] ?? null,
            countryTopup:     $data['country_topup'] ?? null,
            logisticPickupId: $data['logistic_pickup_id'] ?? null,
            logisticDeliveryType: $data['logistic_delivery_type'] ?? null,
            schedule:         $data['schedule'] ?? null,
        );
    }

    /**
     * Convert the DTO to an array suitable for the SSLCOMMERZ API.
     *
     * @return array
     */
    public function toApiPayload(): array
    {
        $payload = [
            'tran_id'          => $this->tranId,
            'total_amount'     => $this->totalAmount,
            'currency'         => $this->currency,
            'product_category' => $this->productCategory,
            'product_profile'  => $this->productProfile,
            'shipping_method'  => $this->shippingMethod,
            'num_of_item'      => $this->numOfItem,
            'emi_option'       => $this->emiOption,
            'emi_allow_only'   => $this->emiAllowOnly,
        ];

        // Include non-null mandatory-like fields
        $mandatoryLike = [
            'cusName'     => 'cus_name',
            'cusEmail'    => 'cus_email',
            'cusPhone'    => 'cus_phone',
            'cusAdd1'     => 'cus_add1',
            'cusCity'     => 'cus_city',
            'cusPostcode' => 'cus_postcode',
            'cusCountry'  => 'cus_country',
            'productName' => 'product_name',
        ];

        foreach ($mandatoryLike as $property => $apiKey) {
            if ($this->{$property} !== null) {
                $payload[$apiKey] = $this->{$property};
            }
        }

        // Optional fields — only include if set
        $optionalMappings = [
            'cusAdd2'          => 'cus_add2',
            'cusState'         => 'cus_state',
            'cusFax'           => 'cus_fax',
            'shipName'         => 'ship_name',
            'shipAdd1'         => 'ship_add1',
            'shipAdd2'         => 'ship_add2',
            'shipCity'         => 'ship_city',
            'shipState'        => 'ship_state',
            'shipPostcode'     => 'ship_postcode',
            'shipCountry'      => 'ship_country',
            'multiCardName'    => 'multi_card_name',
            'allowedBin'       => 'allowed_bin',
            'emiMaxInstOption' => 'emi_max_inst_option',
            'emiSelectedInst'  => 'emi_selected_inst',
            'valueA'           => 'value_a',
            'valueB'           => 'value_b',
            'valueC'           => 'value_c',
            'valueD'           => 'value_d',
            'cart'             => 'cart',
            'productAmount'    => 'product_amount',
            'vat'              => 'vat',
            'discountAmount'   => 'discount_amount',
            'convenienceFee'   => 'convenience_fee',
            'successUrl'       => 'success_url',
            'failUrl'          => 'fail_url',
            'cancelUrl'        => 'cancel_url',
            'ipnUrl'           => 'ipn_url',
            'hoursTillDeparture' => 'hours_till_departure',
            'flightType'       => 'flight_type',
            'pnr'              => 'pnr',
            'journeyFromTo'    => 'journey_from_to',
            'thirdPartyBooking' => 'third_party_booking',
            'hotelName'        => 'hotel_name',
            'lengthOfStay'     => 'length_of_stay',
            'checkInTime'      => 'check_in_time',
            'hotelCity'        => 'hotel_city',
            'productType'      => 'product_type',
            'topupNumber'      => 'topup_number',
            'countryTopup'     => 'country_topup',
            'logisticPickupId' => 'logistic_pickup_id',
            'logisticDeliveryType' => 'logistic_delivery_type',
            'schedule'         => 'schedule',
        ];

        foreach ($optionalMappings as $property => $apiKey) {
            if ($this->{$property} !== null) {
                $payload[$apiKey] = $this->{$property};
            }
        }

        return $payload;
    }
}
