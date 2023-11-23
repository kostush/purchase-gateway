<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;

class OtherPaymentTypeInfo extends PaymentInfo
{
    /**
     * Other payment types
     */
    public const PAYMENT_TYPES = [
        'banktransfer',
        ChequePaymentInfo::PAYMENT_TYPE,
        'cryptocurrency',
        'ewallet',
        'giftcards',
        'prepaywallet',
        'other'
    ];

    /**
     * OtherPaymentTypeInfo constructor.
     *
     * @param string      $paymentType   Payment type
     * @param null|string $paymentMethod Payment method
     *
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws LoggerException
     */
    protected function __construct(string $paymentType, ?string $paymentMethod)
    {
        $this->initPaymentType($paymentType);
        $this->initPaymentMethod($paymentMethod);
    }

    /**
     * @param string      $paymentType   Payment type
     * @param null|string $paymentMethod Payment method
     *
     * @return PaymentInfo
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws LoggerException
     */
    public static function build(
        string $paymentType,
        ?string $paymentMethod
    ): PaymentInfo {
        return new self($paymentType, $paymentMethod);
    }

    /**
     * @param string $paymentType Payment type
     *
     * @return void
     * @throws UnsupportedPaymentTypeException
     */
    private function initPaymentType(string $paymentType): void
    {
        if (!in_array($paymentType, self::PAYMENT_TYPES)) {
            throw new UnsupportedPaymentTypeException($paymentType);
        }

        $this->paymentType = $paymentType;
    }
}
