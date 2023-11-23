<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

interface ChargeInformation
{
    /**
     * @return array
     */
    public function fullTaxBreakDownArray(): array;

    /**
     * @return Amount
     */
     public function initialAmount(): Amount;
}
