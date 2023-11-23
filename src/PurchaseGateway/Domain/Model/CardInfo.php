<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class CardInfo extends CCPaymentInfo
{
    /**
     * @var string
     */
    protected $first6;

    /**
     * @var string
     */
    protected $last4;

    /**
     * @var string
     */
    protected $expirationMonth;

    /**
     * @var string
     */
    protected $expirationYear;

    /**
     * Payment constructor.
     *
     * @param string      $first6          First six
     * @param string      $last4           Last four
     * @param string      $expirationMonth Expiration month
     * @param string      $expirationYear  Expiration year
     * @param string|null $paymentMethod   Payment method
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws Exception\UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function __construct(
        string $first6,
        string $last4,
        string $expirationMonth,
        string $expirationYear,
        ?string $paymentMethod
    ) {
        parent::__construct(self::PAYMENT_TYPE, $paymentMethod);
        $this->first6          = $first6;
        $this->last4           = $last4;
        $this->expirationMonth = $expirationMonth;
        $this->expirationYear  = $expirationYear;
    }

    /**
     * @param string      $first6          First six
     * @param string      $last4           Last four
     * @param string      $expirationMonth Expiration month
     * @param string      $expirationYear  Expiration year
     * @param string|null $paymentMethod   Payment method
     * @return CardInfo
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws Exception\UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(
        string $first6,
        string $last4,
        string $expirationMonth,
        string $expirationYear,
        ?string $paymentMethod
    ): self {
        return new static(
            $first6,
            $last4,
            $expirationMonth,
            $expirationYear,
            $paymentMethod
        );
    }

    /**
     * @return string
     */
    public function first6(): string
    {
        return $this->first6;
    }

    /**
     * @return string
     */
    public function last4(): string
    {
        return $this->last4;
    }

    /**
     * @return string
     */
    public function expirationMonth(): string
    {
        return $this->expirationMonth;
    }

    /**
     * @return string
     */
    public function expirationYear(): string
    {
        return $this->expirationYear;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'first6'          => $this->first6(),
            'last4'           => $this->last4(),
            'expirationMonth' => $this->expirationMonth(),
            'expirationYear'  => $this->expirationYear(),
        ];
    }
}
