<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class QyssoBiller implements Biller, BillerAvailablePaymentMethods
{
    public const BILLER_NAME         = 'qysso';
    public const BILLER_ID           = '23427';
    public const MAX_SUBMITS         = 1;
    public const REPLY_CODE_APPROVED = '000';

    /**
     * @var array
     */
    private $availablePaymentMethods = [];

    /**
     * @return string
     */
    public function id(): string
    {
        return self::BILLER_ID;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return self::BILLER_NAME;
    }

    /**
     * @return int
     */
    public function maxSubmits(): int
    {
        return self::MAX_SUBMITS;
    }

    /**
     * @return bool
     */
    public function isThirdParty(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isThreeDSupported(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return self::BILLER_NAME;
    }

    /**
     * @return array
     */
    public function availablePaymentMethods(): array
    {
        return $this->availablePaymentMethods;
    }

    /**
     * @param string|null $paymentMethod Payment method
     * @return void
     */
    public function addPaymentMethod(?string $paymentMethod): void
    {
        if (null === $paymentMethod) {
            return;
        }
        
        $this->availablePaymentMethods[] = $paymentMethod;
    }
}
