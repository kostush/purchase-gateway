<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

interface ServiceVerifier
{
    /**
     * @return bool
     */
    public function isOpen() : bool;
}
