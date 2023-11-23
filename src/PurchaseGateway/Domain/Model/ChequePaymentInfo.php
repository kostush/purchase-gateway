<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\ObfuscatedData;

/**
 * Class ChequePaymentInfo
 * @package ProBillerNG\PurchaseGateway\Domain\Model
 */
class ChequePaymentInfo extends OtherPaymentTypeInfo
{
    /** @var string Payment Type */
    public const PAYMENT_TYPE = 'checks';

    /** @var string Payment Method */
    public const PAYMENT_METHOD = 'checks';

    /**
     * Payment info
     */
        /** @var string */
        protected $routingNumber;

        /** @var string */
        protected $accountNumber;

        /** @var bool */
        protected $savingAccount;

        /** @var string */
        protected $socialSecurityLast4;

    /**
     * ChequePaymentInfo constructor.
     *
     * @param string        $routingNumber          routingNumber
     * @param string        $accountNumber          accountNumber
     * @param bool          $savingAccount          Is saving account
     * @param string        $socialSecurityLast4    socialSecurity last 4 digits
     * @param string        $paymentType            paymentType
     * @param string|null   $paymentMethod          paymentMethod
     *
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function __construct(
        string  $routingNumber,
        string  $accountNumber,
        bool    $savingAccount,
        string  $socialSecurityLast4,
        string  $paymentType,
        ?string $paymentMethod
    )
    {

        parent::__construct(
            $this->verifyPaymentType($paymentType),
            $this->verifyPaymentMethod($paymentMethod)
        );

        $this->initRoutingNumber($routingNumber);
        $this->initAccountNumber($accountNumber);
        $this->initSavingAccount($savingAccount);
        $this->initSocialSecurityLast4($socialSecurityLast4);
    }

    /**
     * @param string $paymentType
     *
     * @return string
     * @throws UnsupportedPaymentTypeException
     */
    public function verifyPaymentType(string $paymentType): string
    {
        if($paymentType === self::PAYMENT_TYPE) {
            return $paymentType;
        }

        throw new UnsupportedPaymentTypeException($paymentType);
    }

    /**
     * @param string|null $paymentMethod
     *
     * @return string
     * @throws UnsupportedPaymentMethodException
     * @throws InvalidPaymentInfoException
     */
    public function verifyPaymentMethod(?string $paymentMethod): string
    {
        if($paymentMethod === self::PAYMENT_METHOD && !empty($paymentMethod)) {
            return $paymentMethod;
        }

        if(empty($paymentMethod)) {
            throw new InvalidPaymentInfoException('paymentMethod');
        }

        throw new UnsupportedPaymentMethodException($paymentMethod);
    }

    /**
     * @param string      $routingNumber       routingNumber
     * @param string      $accountNumber       accountNumber
     * @param bool        $savingAccount       Is saving account
     * @param string      $socialSecurityLast4 socialSecurity last 4 digits
     * @param string      $paymentType         paymentType
     * @param string|null $paymentMethod       paymentMethod
     *
     * @return PaymentInfo
     *
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(
        string  $routingNumber,
        string  $accountNumber,
        bool    $savingAccount,
        string  $socialSecurityLast4,
        string  $paymentType,
        ?string $paymentMethod
    ): PaymentInfo {
        return new self(
            $routingNumber,
            $accountNumber,
            $savingAccount,
            $socialSecurityLast4,
            $paymentType,
            $paymentMethod
        );
    }

    /**
     * @param string $routingNumber
     * @throws InvalidPaymentInfoException
     */
    private function initRoutingNumber(string $routingNumber): void
    {
        if (empty($routingNumber) || preg_match('/^\d+$/', $routingNumber) == false) {
            throw new InvalidPaymentInfoException('routingNumber');
        }

        $this->routingNumber = $routingNumber;
    }

    /**
     * @param string $accountNumber
     * @throws InvalidPaymentInfoException
     */
    private function initAccountNumber(string $accountNumber): void
    {
        if (empty($accountNumber) || preg_match('/^\d+$/',  $accountNumber) == false) {
            throw new InvalidPaymentInfoException('accountNumber');
        }

        $this->accountNumber = $accountNumber;
    }

    /**
     * @param bool $savingAccount
     */
    private function initSavingAccount(bool $savingAccount): void
    {
        $this->savingAccount = $savingAccount;
    }

    /**
     * @param string $socialSecurityLast4
     * @throws InvalidPaymentInfoException
     */
    private function initSocialSecurityLast4(string $socialSecurityLast4): void
    {
        if (empty($socialSecurityLast4) || preg_match('/^\d{4}$/', $socialSecurityLast4) == false) {
            throw new InvalidPaymentInfoException('socialSecurityLast4');
        }

        $this->socialSecurityLast4 = $socialSecurityLast4;
    }

    /**
     * @return string
     */
    public function routingNumber(): string
    {
        return $this->routingNumber;
    }

    /**
     * @return string
     */
    public function accountNumber(): string
    {
        return $this->accountNumber;
    }

    /**
     * @return bool
     */
    public function savingAccount(): bool
    {
        return $this->savingAccount;
    }

    /**
     * @return string
     */
    public function socialSecurityLast4(): string
    {
        return $this->socialSecurityLast4;
    }

    /**
     * @return string
     */
    public function paymentType(): string
    {
        return $this->paymentType;
    }

    /**
     * @return string
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
            'routingNumber'       => $this->routingNumber(),
            'accountNumber'       => $this->accountNumberObfuscated(),
            'savingAccount'       => $this->savingAccount(),
            'socialSecurityLast4' => $this->socialSecurityLast4()
        ];
    }

    /**
     * Obfuscate account numbers as truncating showing only last for.
     * @param string $accountNumber Account Number.
     * @return string
     */
    public static function obfuscateAccountNumber(string $accountNumber): string
    {
        $last4  = substr($accountNumber, -4);
        return ObfuscatedData::OBFUSCATED_STRING . $last4;
    }

    /**
     * @return string
     */
    public function accountNumberObfuscated(): string
    {
        return self::obfuscateAccountNumber($this->accountNumber);
    }
}
