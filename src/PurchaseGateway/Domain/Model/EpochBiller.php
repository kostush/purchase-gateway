<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class EpochBiller implements Biller
{
    public const BILLER_NAME = 'epoch';
    public const BILLER_ID   = '23425';
    public const MAX_SUBMITS = 1;

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
}
