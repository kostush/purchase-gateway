<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;

class CCPaymentInfo extends PaymentInfo
{
    /**
     * Payment type: CC
     */
    public const PAYMENT_TYPE = 'cc';

    /**
     * CCPaymentInfo constructor.
     * @param string      $paymentType   Payment type
     * @param null|string $paymentMethod Payment method
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function __construct(string $paymentType, ?string $paymentMethod)
    {
        $this->initPaymentType($paymentType);
        $this->initPaymentMethod($paymentMethod);
    }

    /**
     * @param string      $paymentType   Payment type
     * @param null|string $paymentMethod Payment method
     * @return PaymentInfo
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function build(
        string $paymentType,
        ?string $paymentMethod
    ): PaymentInfo {
        return new self($paymentType, $paymentMethod);
    }

    /**
     * @param string $paymentType Payment Type
     * @throws UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    private function initPaymentType(string $paymentType): void
    {
        if ($paymentType !== self::PAYMENT_TYPE) {
            throw new UnsupportedPaymentTypeException($paymentType);
        }

        $this->paymentType = $paymentType;
    }
}
