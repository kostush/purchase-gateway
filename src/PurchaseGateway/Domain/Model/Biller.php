<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

interface Biller
{
    public const ALL_BILLERS = 'all';

    /**
     * @return string
     */
    public function id(): string;

    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return int
     */
    public function maxSubmits(): int;

    /**
     * @return bool
     */
    public function isThirdParty(): bool;

    /**
     * @return bool
     */
    public function isThreeDSupported(): bool;
}
