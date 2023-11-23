<?php

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;

class PaymentInfo
{
    public const PAYMENT_METHODS = [
        'alipay',
        'americanexpress',
        'bccard',
        'bancontact',
        'cartebleue',
        'checks',
        'cryptocurrency',
        'dinacard',
        'dinersclub',
        'directdebit',
        'discover',
        'ec',
        'elv',
        'eps',
        'epsssl',
        'euteller',
        'giftcards',
        'giropay',
        'interaconline',
        'jcb',
        'maestro',
        'mastercard',
        'mastercarddebit',
        'neosurf',
        'neteller',
        'onlineuberweisung',
        'poli',
        'paypal',
        'paysafecard',
        'postepay',
        'przelewy24',
        'qiwi',
        'sepadirectdebit',
        'safetypay',
        'skrill',
        'sofortbanking',
        'sofortuberweisung',
        'switch',
        'trustpay',
        'unionpay',
        'visa',
        'visadebit',
        'wechat',
        'epaybg',
        'ideal',
        'toditocash',
        'zelle',
        'mir',
        'ccunionpay',
    ];

    /**
     * @var string
     */
    protected $paymentType;

    /**
     * @var null|string
     */
    protected $paymentMethod;

    /**
     * @return string
     */
    public function paymentType(): string
    {
        return $this->paymentType;
    }

    /**
     * @return null|string
     */
    public function paymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'paymentType'   => $this->paymentType(),
            'paymentMethod' => $this->paymentMethod(),
        ];
    }

    /**
     * @param string|null $paymentMethod Payment method
     * @return void
     * @throws UnsupportedPaymentMethodException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function initPaymentMethod(?string $paymentMethod): void
    {
        if (!empty($paymentMethod) && !in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            throw new UnsupportedPaymentMethodException($paymentMethod);
        }

        $this->paymentMethod = $paymentMethod;
    }
}
