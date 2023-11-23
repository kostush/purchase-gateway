<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use Carbon\Carbon;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ExpiredCreditCardExpirationDate;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCreditCardExpirationDate;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\ObfuscatedData;

class NewCCPaymentInfo extends CCPaymentInfo
{
    /**
     * @var string
     */
    protected $ccNumber;

    /**
     * @var string
     */
    protected $cvv;

    /**
     * @var string
     */
    protected $expirationMonth;

    /**
     * @var string
     */
    protected $expirationYear;

    /**
     * NewCCPaymentInfo constructor.
     * @param string      $ccNumber        Credit Card Number
     * @param string      $cvv             CVV
     * @param string      $expirationMonth Expiration Month
     * @param string      $expirationYear  Expiration Year
     * @param string|null $paymentMethod   Payment Method
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws Exception\UnsupportedPaymentTypeException
     * @throws InvalidCreditCardExpirationDate
     * @throws InvalidPaymentInfoException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    protected function __construct(
        string $ccNumber,
        string $cvv,
        string $expirationMonth,
        string $expirationYear,
        ?string $paymentMethod
    ) {
        parent::__construct(self::PAYMENT_TYPE, $paymentMethod);

        $this->initCcNumber($ccNumber);
        $this->initCvv($cvv);
        $this->initExpirationMonth($expirationMonth);
        $this->initExpirationYear($expirationYear);


        if ($this->isExpired()) {
            throw new ExpiredCreditCardExpirationDate();
        }
    }

    /**
     * @param string      $ccNumber        Credit Card Number
     * @param string      $cvv             CVV
     * @param string      $expirationMonth Expiration Month
     * @param string      $expirationYear  Expiration Year
     * @param string|null $paymentMethod   Payment Method
     * @return NewCCPaymentInfo
     * @throws InvalidCreditCardExpirationDate
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public static function create(
        string $ccNumber,
        string $cvv,
        string $expirationMonth,
        string $expirationYear,
        ?string $paymentMethod
    ): PaymentInfo {
        return new static($ccNumber, $cvv, $expirationMonth, $expirationYear, $paymentMethod);
    }

    /**
     * @return string
     */
    public function ccNumber(): string
    {
        return $this->ccNumber;
    }

    /**
     * @return string
     */
    public function cvv(): string
    {
        return $this->cvv;
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
     * @return bool
     * @throws \Throwable
     */
    public function isExpired(): bool
    {
        try {
            $date = Carbon::createFromDate(
                $this->expirationYear(),
                $this->expirationMonth(),
                1
            )->endOfMonth();

            return Carbon::now()->greaterThan($date);
        } catch (\Throwable $e) {
            throw new InvalidCreditCardExpirationDate();
        }
    }

    /**
     * @param string $ccNumber CC Number
     * @throws InvalidPaymentInfoException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    private function initCcNumber(string $ccNumber): void
    {
        if (empty($ccNumber)) {
            throw new InvalidPaymentInfoException('ccNumber');
        }

        $this->ccNumber = $ccNumber;
    }

    /**
     * @param string $cvv CVV Number
     * @throws InvalidPaymentInfoException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    private function initCvv(string $cvv): void
    {
        if (empty($cvv)) {
            throw new InvalidPaymentInfoException('cvv');
        }

        $this->cvv = $cvv;
    }

    /**
     * @param string $expirationMonth Expiration Month
     * @throws InvalidPaymentInfoException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    private function initExpirationMonth(string $expirationMonth): void
    {
        if (empty($expirationMonth)) {
            throw new InvalidPaymentInfoException('expirationMonth');
        }

        $this->expirationMonth = $expirationMonth;
    }

    /**
     * @param string $expirationYear Expiration Year
     * @throws InvalidPaymentInfoException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    private function initExpirationYear(string $expirationYear): void
    {
        if (empty($expirationYear)) {
            throw new InvalidPaymentInfoException('expirationYear');
        }

        $this->expirationYear = $expirationYear;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'ccNumber'        => ObfuscatedData::OBFUSCATED_STRING,
            'cvv'             => ObfuscatedData::OBFUSCATED_STRING,
            'expirationMonth' => $this->expirationMonth(),
            'expirationYear'  => $this->expirationYear()
        ];
    }
}
