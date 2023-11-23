<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class AttemptTransactionData
{
    /**
     * @var CurrencyCode
     */
    private $currency;

    /**
     * @var UserInfo
     */
    private $userInfo;

    /**
     * @var PaymentInfo
     */
    private $paymentInfo;

    /**
     * AttemptTransactionData constructor.
     * @param CurrencyCode $currency    Currency
     * @param UserInfo     $userInfo    User info
     * @param PaymentInfo  $paymentInfo Payment info
     */
    private function __construct(
        CurrencyCode $currency,
        UserInfo $userInfo,
        PaymentInfo $paymentInfo
    ) {
        $this->currency    = $currency;
        $this->userInfo    = $userInfo;
        $this->paymentInfo = $paymentInfo;
    }

    /**
     * @param CurrencyCode $currency    Currency
     * @param UserInfo     $userInfo    User info
     * @param PaymentInfo  $paymentInfo Payment info
     * @return AttemptTransactionData
     */
    public static function create(
        CurrencyCode $currency,
        UserInfo $userInfo,
        PaymentInfo $paymentInfo
    ): self {
        return new static(
            $currency,
            $userInfo,
            $paymentInfo
        );
    }

    /**
     * @return CurrencyCode
     */
    public function currency(): CurrencyCode
    {
        return $this->currency;
    }

    /**
     * @return UserInfo
     */
    public function userInfo(): UserInfo
    {
        return $this->userInfo;
    }

    /**
     * @return PaymentInfo
     */
    public function paymentInfo(): PaymentInfo
    {
        return $this->paymentInfo;
    }
}
