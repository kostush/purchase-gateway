<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

abstract class BundleChargeInformation implements ChargeInformation
{
    /**
     * @return Amount
     */
    abstract public function initialAmount(): Amount;

    /**
     * @return Duration
     */
    abstract public function validFor(): Duration;

    /**
     * @return TaxBreakdown|null
     */
    abstract public function initialTaxBreakDown(): ?TaxBreakdown;
}
